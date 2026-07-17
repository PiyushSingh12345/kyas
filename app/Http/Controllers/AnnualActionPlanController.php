<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\State;
use App\Models\ProgramDivision;
use App\Models\PdAndSlsComp;
use App\Models\BudgetHead;
use App\Models\StatewiseAapAllocation;
use App\Models\PdwiseAapAllocation;
use App\Models\BudgetPhase;
use App\Services\MotherSanctionTotalCalculator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB as DBFacade;
use Carbon\Carbon;

class AnnualActionPlanController extends Controller
{
    public function __construct(private MotherSanctionTotalCalculator $msTotals)
    {
    }

    /**
     * Store statewise AAP allocation data
     */
    public function storeStatewiseAllocation(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'allocations' => 'required|array|min:1',
                'allocations.*.financial_year' => 'required|string',
                'allocations.*.state_id' => 'required|integer',
                'allocations.*.pd_id' => 'required|integer',
                'allocations.*.amount' => 'required|numeric|min:0',
                'allocations.*.tentative_amount' => 'required|numeric|min:0',
                'allocations.*.status' => 'required|integer|in:0,1',
                'remarks' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Process each allocation - update if exists, insert if new
                foreach ($request->allocations as $allocation) {
                    // Check if record exists for the same state_id, financial_year, and pd_id
                    $existingRecord = StatewiseAapAllocation::where([
                        'state_id' => $allocation['state_id'],
                        'financial_year' => $allocation['financial_year'],
                        'pd_id' => $allocation['pd_id']
                    ])->first();

                    if ($existingRecord) {
                        // Update existing record
                        $existingRecord->update([
                            'amount' => $allocation['amount'],
                            'tentative_amount' => $allocation['tentative_amount'] ?? 0,
                            'status' => $allocation['status'],
                            'remark' => $request->remarks[$allocation['state_id']] ?? $existingRecord->remark
                        ]);
                    } else {
                        // Insert new record
                        StatewiseAapAllocation::create([
                            'financial_year' => $allocation['financial_year'],
                            'state_id' => $allocation['state_id'],
                            'pd_id' => $allocation['pd_id'],
                            'amount' => $allocation['amount'],
                            'tentative_amount' => $allocation['tentative_amount'] ?? 0,
                            'status' => $allocation['status'],
                            'remark' => $request->remarks[$allocation['state_id']] ?? null
                        ]);
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Allocation data saved successfully',
                    'count' => count($request->allocations)
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save allocation data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get existing statewise AAP allocation data
     */
    public function getStatewiseAllocation(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2025-26');

            $allocations = StatewiseAapAllocation::where('financial_year', $financialYear)
                ->get()
                ->groupBy('state_id')
                ->map(function ($stateAllocations) {
                    return $stateAllocations->keyBy('pd_id')->map(function ($allocation) {
                        // Format amount to exactly 5 decimal places without rounding
                        // Get raw value from database to preserve exact precision
                        $rawAmount = $allocation->getRawOriginal('amount') ?? $allocation->amount;
                        
                        // Convert to string to preserve precision, then format to 5 decimals
                        $amountStr = (string)$rawAmount;
                        if (strpos($amountStr, '.') !== false) {
                            $parts = explode('.', $amountStr);
                            $integerPart = $parts[0];
                            $decimalPart = isset($parts[1]) ? substr($parts[1], 0, 5) : '';
                            $decimalPart = str_pad($decimalPart, 5, '0', STR_PAD_RIGHT);
                            $amountStr = $integerPart . '.' . $decimalPart;
                        } else {
                            $amountStr = $amountStr . '.00000';
                        }
                        
                        $allocation->amount = $amountStr;
                        
                        // Format tentative_amount to exactly 5 decimal places without rounding
                        if ($allocation->tentative_amount !== null) {
                            $rawTentativeAmount = $allocation->getRawOriginal('tentative_amount') ?? $allocation->tentative_amount;
                            $tentativeAmountStr = (string)$rawTentativeAmount;
                            if (strpos($tentativeAmountStr, '.') !== false) {
                                $parts = explode('.', $tentativeAmountStr);
                                $integerPart = $parts[0];
                                $decimalPart = isset($parts[1]) ? substr($parts[1], 0, 5) : '';
                                $decimalPart = str_pad($decimalPart, 5, '0', STR_PAD_RIGHT);
                                $tentativeAmountStr = $integerPart . '.' . $decimalPart;
                            } else {
                                $tentativeAmountStr = $tentativeAmountStr . '.00000';
                            }
                            $allocation->tentative_amount = $tentativeAmountStr;
                        }
                        
                        return $allocation;
                    });
                });

            // Get remarks for each state
            $remarks = StatewiseAapAllocation::where('financial_year', $financialYear)
                ->whereNotNull('remark')
                ->pluck('remark', 'state_id')
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => $allocations,
                'remarks' => $remarks
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve allocation data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get states for dropdown
     */
    public function getStates(): JsonResponse
    {
        try {
            // Check if table exists first
            if (!DBFacade::getSchemaBuilder()->hasTable('states')) {
                return response()->json([
                    'success' => false,
                    'message' => 'States table does not exist',
                    'error' => 'Table not found'
                ], 404);
            }

            // Use DB facade directly to avoid any model issues
            $states = DBFacade::table('states')
                ->select('id as state_id', 'name as state_name')
                ->orderBy('name')
                ->get();

            return response()->json($states);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve states',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Get program divisions for dropdown
     */
    public function getProgramDivisions(): JsonResponse
    {
        try {
            // Check if table exists first
            if (!DBFacade::getSchemaBuilder()->hasTable('md_program_divisions')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Program divisions table does not exist',
                    'error' => 'Table not found'
                ], 404);
            }

            // Use DB facade directly to avoid any model issues
            $programDivisions = DBFacade::table('md_program_divisions')
                ->select('division_id', 'division_name')
                ->where('is_active', 1)
                ->where('is_pd', 1)
                ->orderBy('division_name')
                ->get();

            return response()->json($programDivisions);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve program divisions',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Get SLS components for a specific state
     */
    public function getSLSComponentsByState(Request $request): JsonResponse
    {
        try {
            $stateId = $request->get('state_id');
            
            if (!$stateId) {
                return response()->json([
                    'success' => false,
                    'message' => 'State ID is required'
                ], 400);
            }

            // Get SLS components for the selected state
            $slsComponents = DB::table('pd_and_sls_comp')
                ->select('id', 'name', 'full_sls_name', 'component', 'slsPD')
                ->where('state_id', $stateId)
                ->where('status', 1)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $slsComponents
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve SLS components',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get budget heads based on state's budgethead_fourdigits
     */
    public function getBudgetHeadsByState(Request $request): JsonResponse
    {
        try {
            $stateId = $request->get('state_id');
            
            if (!$stateId) {
                return response()->json([
                    'success' => false,
                    'message' => 'State ID is required'
                ], 400);
            }

            // Get the state's budgethead_fourdigits
            $state = DB::table('states')
                ->select('budgethead_fourdigits')
                ->where('id', $stateId)
                ->first();

            if (!$state || !$state->budgethead_fourdigits) {
                return response()->json([
                    'success' => false,
                    'message' => 'State not found or budgethead_fourdigits not set'
                ], 404);
            }

            // Get budget heads where the first 4 digits match the state's budgethead_fourdigits
            $budgetHeads = DB::table('budget_heads')
                ->select('id', 'budget', 'description', 'category')
                ->where('budget', 'LIKE', $state->budgethead_fourdigits . '%')
                ->where('status', 1)
                ->orderBy('budget')
                ->get();

            // Create a simple array format for easier frontend usage
            $budgetHeadsArray = $budgetHeads->map(function($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->budget,
                    'description' => $item->description,
                    'category' => $item->category
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => $budgetHeads,
                'budget_heads_array' => $budgetHeadsArray,
                'state_budgethead_fourdigits' => $state->budgethead_fourdigits,
                'count' => $budgetHeads->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve budget heads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store state release data
     */
    public function storeStateReleaseData(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'state_id' => 'required|integer|exists:states,id',
                'fy' => 'required|string',
                'amounts' => 'required|array',
                'amounts.*.sls_id' => 'required|integer|exists:pd_and_sls_comp,id',
                'amounts.*.budget_head_id' => 'required|integer',
                'amounts.*.amount' => 'required|numeric|min:0'
            ]);

            // Log the incoming data for debugging
            Log::info('State Release Data Submission', [
                'state_id' => $validated['state_id'],
                'fy' => $validated['fy'],
                'amounts_count' => count($validated['amounts']),
                'sample_amounts' => array_slice($validated['amounts'], 0, 5)
            ]);

            DB::beginTransaction();

            $savedCount = 0;
            $errors = [];

            // Process each amount entry
            foreach ($validated['amounts'] as $amountData) {
                try {
                    $slsId = $amountData['sls_id'];
                    $budgetHeadId = $amountData['budget_head_id'];
                    $amount = $amountData['amount'];

                    // Check if this budget head ID exists in state_release_generic table
                    $existingGenericBudgetHead = DB::table('state_release_generic')
                        ->where('id', $budgetHeadId)
                        ->where('status', 1)
                        ->first();
                    
                    if (!$existingGenericBudgetHead) {
                        $errors[] = "Budget head ID {$budgetHeadId} not found in state_release_generic table";
                        continue;
                    }

                    // Check if record already exists
                    $existingRecord = DB::table('state_release_data')
                        ->where('state_id', $validated['state_id'])
                        ->where('fy', $validated['fy'])
                        ->where('SLS_id', $slsId)
                        ->where('budget_head_id', $budgetHeadId)
                        ->first();

                    if ($existingRecord) {
                        // Update existing record
                        DB::table('state_release_data')
                            ->where('id', $existingRecord->id)
                            ->update([
                                'amount' => $amount,
                                'updated_at' => now()
                            ]);
                    } else {
                        // Create new record
                        DB::table('state_release_data')->insert([
                            'fy' => $validated['fy'],
                            'state_id' => $validated['state_id'],
                            'SLS_id' => $slsId,
                            'budget_head_id' => $budgetHeadId,
                            'amount' => $amount,
                            'flag' => 0,
                            'isactive' => 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                    $savedCount++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Error processing amount: " . $e->getMessage();
                    Log::error("Error processing amount", [
                        'amount_data' => $amountData,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                Log::error('State Release Data Errors', ['errors' => $errors]);
                return response()->json([
                    'success' => false,
                    'message' => 'Some errors occurred while saving data',
                    'errors' => $errors,
                    'savedCount' => 0
                ], 422);
            }

            DB::commit();

            Log::info('State Release Data Saved Successfully', [
                'saved_count' => $savedCount,
                'state_id' => $validated['state_id'],
                'fy' => $validated['fy']
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully saved {$savedCount} records",
                'savedCount' => $savedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('State Release Data Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to save state release data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store PD-wise AAP allocation data
     */
    public function storePdwiseAllocation(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'allocations' => 'required|array|min:1',
                'allocations.*.financial_year' => 'required|string',
                'allocations.*.budget_phase' => 'nullable|string|in:BE,RE,FE',
                'allocations.*.bh_id' => 'required|integer',
                'allocations.*.pd_id' => 'required|integer',
                'allocations.*.amount' => 'required|numeric|min:0',
                'allocations.*.status' => 'required|integer|in:0,1',
                'remarks' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Ensure sum of all PD amounts for each budget head does not exceed
            // the Budget Phase amount for the same financial year and budget phase.
            $programDivisionIds = array_map('intval', $request->input('program_division_ids', []));
            $exceededHeads = $this->validatePdTotalsAgainstBudgetPhase(
                $request->allocations,
                $programDivisionIds
            );
            if (!empty($exceededHeads)) {
                $messages = array_map(function ($item) {
                    return sprintf(
                        '%s: PD total %s exceeds Budget Phase (%s) amount of %s',
                        $item['label'],
                        number_format($item['pd_total'], 5, '.', ''),
                        $item['budget_phase'],
                        number_format($item['allowed'], 5, '.', '')
                    );
                }, $exceededHeads);

                return response()->json([
                    'success' => false,
                    'message' => 'PD allocation totals cannot exceed the Budget Phase amount for the same Financial Year and Budget Phase. ' . implode('; ', $messages),
                    'exceeded' => $exceededHeads,
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Process each allocation - update if exists, insert if new
                foreach ($request->allocations as $allocation) {
                    // Check if record exists for the same bh_id, financial_year, budget_phase, and pd_id
                    $whereConditions = [
                        'bh_id' => $allocation['bh_id'],
                        'financial_year' => $allocation['financial_year'],
                        'pd_id' => $allocation['pd_id']
                    ];
                    
                    // Handle budget_phase (can be null)
                    if (isset($allocation['budget_phase']) && $allocation['budget_phase'] !== null && $allocation['budget_phase'] !== '') {
                        $whereConditions['budget_phase'] = $allocation['budget_phase'];
                    } else {
                        $whereConditions['budget_phase'] = null;
                    }
                    
                    $existingRecord = PdwiseAapAllocation::where($whereConditions)->first();

                    if ($existingRecord) {
                        // Update existing record
                        $existingRecord->update([
                            'amount' => $allocation['amount'],
                            'status' => $allocation['status'],
                            'remark' => $request->remarks[$allocation['bh_id']] ?? $existingRecord->remark
                        ]);
                    } else {
                        // Insert new record
                        PdwiseAapAllocation::create([
                            'financial_year' => $allocation['financial_year'],
                            'budget_phase' => $allocation['budget_phase'],
                            'bh_id' => $allocation['bh_id'],
                            'pd_id' => $allocation['pd_id'],
                            'amount' => $allocation['amount'],
                            'status' => $allocation['status'],
                            'remark' => $request->remarks[$allocation['bh_id']] ?? null
                        ]);
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'PD-wise allocation data saved successfully',
                    'count' => count($request->allocations)
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save PD-wise allocation data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate that projected PD totals per budget head do not exceed
     * the Budget Phase budget_amount for the same FY and phase.
     *
     * @param  array  $allocations
     * @param  array  $programDivisionIds  Active PD ids from the grid (full-grid validation)
     * @return array List of exceeded heads (empty if all valid)
     */
    private function validatePdTotalsAgainstBudgetPhase(array $allocations, array $programDivisionIds = []): array
    {
        $programDivisionIds = array_values(array_unique(array_map('intval', $programDivisionIds)));

        // Group incoming amounts by (financial_year, budget_phase, bh_id) => [pd_id => amount]
        $incoming = [];
        foreach ($allocations as $allocation) {
            $fy = $allocation['financial_year'];
            $phase = $allocation['budget_phase'] ?? null;
            $phaseKey = ($phase === null || $phase === '') ? '__null__' : $phase;
            $bhId = (int) $allocation['bh_id'];
            $pdId = (int) $allocation['pd_id'];
            $key = $fy . '|' . $phaseKey . '|' . $bhId;

            if (!isset($incoming[$key])) {
                $incoming[$key] = [
                    'financial_year' => $fy,
                    'budget_phase' => ($phase === null || $phase === '') ? null : $phase,
                    'bh_id' => $bhId,
                    'pd_amounts' => [],
                ];
            }
            $incoming[$key]['pd_amounts'][$pdId] = (float) $allocation['amount'];
        }

        $exceeded = [];

        foreach ($incoming as $group) {
            $fy = $group['financial_year'];
            $phase = $group['budget_phase'];
            $bhId = $group['bh_id'];

            // Allowed amount from Budget Phase page
            $phaseQuery = BudgetPhase::where('budget_head_id', $bhId)
                ->where('financial_year', $fy)
                ->where('status', 1);

            if ($phase === null) {
                $phaseQuery->whereNull('budget_phase');
            } else {
                $phaseQuery->where('budget_phase', $phase);
            }

            $budgetPhase = $phaseQuery->first();
            $allowed = $budgetPhase ? (float) $budgetPhase->budget_amount : 0.0;

            $incomingPdAmounts = $group['pd_amounts'];

            // Full grid submission: total only the active PD columns shown on the page.
            // This matches the UI row total and treats cleared cells as 0 instead of
            // keeping stale DB values for PDs omitted from the payload.
            if (!empty($programDivisionIds)) {
                $pdTotal = 0.0;
                foreach ($programDivisionIds as $pdId) {
                    $pdTotal += (float) ($incomingPdAmounts[$pdId] ?? 0.0);
                }
            } else {
                // Fallback for partial updates: merge incoming over existing DB values.
                $existingQuery = PdwiseAapAllocation::where('bh_id', $bhId)
                    ->where('financial_year', $fy);

                if ($phase === null) {
                    $existingQuery->whereNull('budget_phase');
                } else {
                    $existingQuery->where('budget_phase', $phase);
                }

                $merged = [];
                foreach ($existingQuery->get(['pd_id', 'amount']) as $row) {
                    $merged[(int) $row->pd_id] = (float) $row->amount;
                }

                foreach ($incomingPdAmounts as $pdId => $amount) {
                    $merged[$pdId] = (float) $amount;
                }

                $pdTotal = array_sum($merged);
            }

            // Tiny tolerance for float precision
            if ($pdTotal > $allowed + 0.000001) {
                $budgetHead = BudgetHead::find($bhId);
                $label = $budgetHead
                    ? ($budgetHead->budget . ' - ' . $budgetHead->description)
                    : ('Budget head #' . $bhId);

                $exceeded[] = [
                    'bh_id' => $bhId,
                    'label' => $label,
                    'financial_year' => $fy,
                    'budget_phase' => $phase ?? 'N/A',
                    'pd_total' => $pdTotal,
                    'allowed' => $allowed,
                ];
            }
        }

        return $exceeded;
    }

    /**
     * Get existing PD-wise AAP allocation data
     */
    public function getPdwiseAllocation(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2025-26');
            $budgetPhase = $request->get('budget_phase');
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);

            // BE / RE / FE are FY + phase snapshots from pdwise_aap_allocation.
            // Do not apply date/time range here — that filter scopes Release/Expenditure only.
            $query = PdwiseAapAllocation::whereIn('financial_year', $yearVariants)
                ->where('status', 1);
            
            // Filter by budget phase if provided
            if ($budgetPhase && $budgetPhase !== '0') {
                $query->where('budget_phase', $budgetPhase);
            }

            $allocations = $query->get()
                ->groupBy('bh_id')
                ->map(function ($bhAllocations) {
                    return $bhAllocations->keyBy('pd_id')->map(function ($allocation) {
                        // Format amount to exactly 5 decimal places without rounding
                        // Get raw value from database to preserve exact precision
                        $rawAmount = $allocation->getRawOriginal('amount') ?? $allocation->amount;
                        
                        // Convert to string to preserve precision, then format to 5 decimals
                        $amountStr = (string)$rawAmount;
                        if (strpos($amountStr, '.') !== false) {
                            $parts = explode('.', $amountStr);
                            $integerPart = $parts[0];
                            $decimalPart = isset($parts[1]) ? substr($parts[1], 0, 5) : '';
                            $decimalPart = str_pad($decimalPart, 5, '0', STR_PAD_RIGHT);
                            $amountStr = $integerPart . '.' . $decimalPart;
                        } else {
                            $amountStr = $amountStr . '.00000';
                        }
                        
                        $allocation->amount = $amountStr;
                        return $allocation;
                    });
                });

            // Get remarks for each budget head
            $remarksQuery = PdwiseAapAllocation::whereIn('financial_year', $yearVariants)
                ->where('status', 1);
            if ($budgetPhase && $budgetPhase !== '0') {
                $remarksQuery->where('budget_phase', $budgetPhase);
            }
            $remarks = $remarksQuery->whereNotNull('remark')
                ->pluck('remark', 'bh_id')
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => $allocations,
                'remarks' => $remarks
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve PD-wise allocation data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get budget heads for dropdown
     */
    public function getBudgetHeads(Request $request): JsonResponse
    {
        try {
            // Check if table exists first
            if (!DBFacade::getSchemaBuilder()->hasTable('budget_heads')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Budget heads table does not exist',
                    'error' => 'Table not found'
                ], 404);
            }

            $phase = $request->query('phase'); // BE/FE/RE
            $year = $request->query('year');   // 2025-26

            // Use DB facade directly to avoid any model issues
            $budgetHeadsQuery = DBFacade::table('budget_heads')
                ->select('budget_heads.id as bh_id', 'budget_heads.budget as budget_code', 'budget_heads.description as budget_name')
                ->where('budget_heads.status', 1);

            // If phase is provided and not '0', filter by budget phase
            if ($phase && $phase !== '0' && $year) {
                $budgetHeadsQuery->join('budget_phase', function($join) use ($phase, $year) {
                    $join->on('budget_heads.id', '=', 'budget_phase.budget_head_id')
                         ->where('budget_phase.budget_phase', '=', $phase)
                         ->where('budget_phase.financial_year', '=', $year)
                         ->where('budget_phase.status', '=', 1);
                })
                ->distinct();
            }

            $budgetHeads = $budgetHeadsQuery->orderBy('budget_heads.budget')->get();

            return response()->json($budgetHeads);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve budget heads',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Get state release generic data
     */
    public function getStateReleaseGeneric(): JsonResponse
    {
        try {
            $genericData = DB::table('state_release_generic')
                ->select('id', 'allocation_name')
                ->where('status', 1)
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $genericData
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching state release generic data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch state release generic data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get existing state release data for a specific state and financial year
     */
    public function getStateReleaseData(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'state_id' => 'required|integer|exists:states,id',
                'fy' => 'required|string'
            ]);

            $releaseData = DB::table('state_release_data')
                ->select('SLS_id', 'budget_head_id', 'amount')
                ->where('state_id', $validated['state_id'])
                ->where('fy', $validated['fy'])
                ->where('isactive', 1)
                ->get();

            // Group data by SLS_id and budget_head_id for easy lookup
            $groupedData = [];
            foreach ($releaseData as $record) {
                $groupedData[$record->SLS_id][$record->budget_head_id] = $record->amount;
            }

            return response()->json([
                'success' => true,
                'data' => $groupedData,
                'count' => $releaseData->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching state release data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch state release data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse financial year string (e.g. "2025-26") into [startDate, endDate] for Indian FY (Apr-Mar).
     */
    private function getFinancialYearDateRange(string $financialYear): array
    {
        $parts = array_map('trim', explode('-', $financialYear));
        $startYear = isset($parts[0]) ? (int) $parts[0] : (int) date('Y');
        $startDate = $startYear . '-04-01';
        $endDate = ($startYear + 1) . '-03-31';
        return [$startDate, $endDate];
    }

    /**
     * Return both short (2026-27) and long (2026-2027) financial year formats.
     *
     * @return array<int, string>
     */
    private function normalizeFinancialYearVariants(?string $year): array
    {
        if (empty($year)) {
            return [];
        }

        $year = trim($year);
        $variants = [$year];

        if (preg_match('/^\d{4}-\d{4}$/', $year)) {
            [$start, $end] = explode('-', $year);
            $variants[] = $start . '-' . substr($end, -2);
        } elseif (preg_match('/^\d{4}-\d{2}$/', $year)) {
            [$start, $end] = explode('-', $year);
            $variants[] = $start . '-20' . $end;
        }

        return array_values(array_unique($variants));
    }

    /**
     * Parse optional date/time range from request (date_from, time_from, date_to, time_to).
     *
     * @return array{0: ?Carbon, 1: ?Carbon}|null
     */
    private function resolveDateTimeRangeFromRequest(Request $request): ?array
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$dateFrom && !$dateTo) {
            return null;
        }

        $start = null;
        $end = null;

        if ($dateFrom) {
            $timeFrom = $request->get('time_from', '00:00');
            $start = Carbon::parse(trim($dateFrom . ' ' . $this->normalizeTimeForParse($timeFrom, false)));
        }

        if ($dateTo) {
            $timeTo = $request->get('time_to', '23:59');
            $end = Carbon::parse(trim($dateTo . ' ' . $this->normalizeTimeForParse($timeTo, true)));
        }

        return [$start, $end];
    }

    private function normalizeTimeForParse(?string $time, bool $isEnd): string
    {
        $time = trim((string) $time);
        if ($time === '') {
            return $isEnd ? '23:59:59' : '00:00:00';
        }
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $isEnd ? $time . ':59' : $time . ':00';
        }
        return $time;
    }

    private function applyDateTimeRangeToQuery($query, Request $request, string $column): void
    {
        $range = $this->resolveDateTimeRangeFromRequest($request);
        if ($range === null) {
            return;
        }

        [$start, $end] = $range;
        if ($start) {
            $query->where($column, '>=', $start);
        }
        if ($end) {
            $query->where($column, '<=', $end);
        }
    }

    private function applyDateRangeToQuery($query, Request $request, string $column): void
    {
        $range = $this->resolveDateTimeRangeFromRequest($request);
        if ($range === null) {
            return;
        }

        [$start, $end] = $range;
        if ($start) {
            $query->whereDate($column, '>=', $start->toDateString());
        }
        if ($end) {
            $query->whereDate($column, '<=', $end->toDateString());
        }
    }

    /**
     * Resolve [startDate, endDate] strings for date-only columns (e.g. agency release date).
     */
    private function resolveDateOnlyRangeBounds(Request $request, string $financialYear): array
    {
        $range = $this->resolveDateTimeRangeFromRequest($request);
        if ($range !== null) {
            [$start, $end] = $range;
            return [
                $start ? $start->toDateString() : '1970-01-01',
                $end ? $end->toDateString() : '2099-12-31',
            ];
        }

        return $this->getFinancialYearDateRange($financialYear);
    }

    /**
     * Map budget codes (dotted or digit-only) to budget head IDs.
     *
     * @return array<string, string>
     */
    private function buildBudgetCodeToBhIdMap(): array
    {
        $map = [];
        $rows = DB::table('budget_heads')->where('status', 1)->get(['id', 'budget']);

        foreach ($rows as $row) {
            $trimmed = trim((string) $row->budget);
            if ($trimmed === '') {
                continue;
            }

            $bhId = (string) $row->id;
            $map[$trimmed] = $bhId;

            $digits = preg_replace('/[^0-9]/', '', $trimmed);
            if ($digits !== '') {
                $map[$digits] = $bhId;
            }
        }

        return $map;
    }

    /**
     * Add agency amounts into an existing bh_id => pd_id => amount structure.
     *
     * @param array<string, array<string, float>> $formattedData
     * @param array<string, array<string, float>> $agencyByBh
     * @return array<string, array<string, float>>
     */
    private function mergeAgencyAmountsIntoFormattedData(array $formattedData, array $agencyByBh): array
    {
        foreach ($agencyByBh as $bhId => $byPd) {
            if (!isset($formattedData[$bhId])) {
                $formattedData[$bhId] = [];
            }

            foreach ($byPd as $pdId => $amount) {
                if (!isset($formattedData[$bhId][$pdId])) {
                    $formattedData[$bhId][$pdId] = 0;
                }
                $formattedData[$bhId][$pdId] += floatval($amount);
            }
        }

        return $formattedData;
    }

    /**
     * Get agency release/expenditure totals for the PD-wise budget allocation release report.
     * TSA amount contributes to release; TSA expenditure contributes to expenditure.
     * LOA and Administrative Expenditure amounts contribute to both release and expenditure.
     *
     * @return array{release_by_bh: array<string, array<string, float>>, expenditure_by_bh: array<string, array<string, float>>}
     */
    private function getAgencyReleaseDataForPdWiseReport(string $financialYear, ?Request $request = null): array
    {
        [$startDate, $endDate] = $request
            ? $this->resolveDateOnlyRangeBounds($request, $financialYear)
            : $this->getFinancialYearDateRange($financialYear);

        $agencyBaseQuery = function (string $table) use ($startDate, $endDate) {
            return DB::table($table)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->whereBetween('date', [$startDate, $endDate])
                ->whereNotNull('program_division_id')
                ->whereNotNull('budget_head');
        };

        $tsaRelease = $agencyBaseQuery('agency_release_tsa')
            ->select(
                DB::raw('TRIM(budget_head) as budget_head'),
                'program_division_id as pd_id',
                DB::raw('SUM(COALESCE(amount, 0)) as total')
            )
            ->groupBy(DB::raw('TRIM(budget_head)'), 'program_division_id')
            ->get();

        $tsaExpenditure = $agencyBaseQuery('agency_release_tsa')
            ->select(
                DB::raw('TRIM(budget_head) as budget_head'),
                'program_division_id as pd_id',
                DB::raw('SUM(COALESCE(expenditure, 0)) as total')
            )
            ->groupBy(DB::raw('TRIM(budget_head)'), 'program_division_id')
            ->get();

        $loaAmounts = $agencyBaseQuery('agency_release_loa')
            ->select(
                DB::raw('TRIM(budget_head) as budget_head'),
                'program_division_id as pd_id',
                DB::raw('SUM(COALESCE(amount, 0)) as total')
            )
            ->groupBy(DB::raw('TRIM(budget_head)'), 'program_division_id')
            ->get();

        $adminExpAmounts = $agencyBaseQuery('agency_release_administrative_expenditure')
            ->select(
                DB::raw('TRIM(budget_head) as budget_head'),
                'program_division_id as pd_id',
                DB::raw('SUM(COALESCE(amount, 0)) as total')
            )
            ->groupBy(DB::raw('TRIM(budget_head)'), 'program_division_id')
            ->get();

        $budgetCodeToBhId = $this->buildBudgetCodeToBhIdMap();
        $releaseByBh = [];
        $expenditureByBh = [];

        $addTo = function (array &$target, $collection) use ($budgetCodeToBhId) {
            foreach ($collection as $row) {
                if ($row->pd_id === null || $row->budget_head === null) {
                    continue;
                }

                $budgetHead = trim((string) $row->budget_head);
                $bhId = $budgetCodeToBhId[$budgetHead] ?? null;
                if ($bhId === null) {
                    continue;
                }

                $pdId = (string) $row->pd_id;
                if (!isset($target[$bhId])) {
                    $target[$bhId] = [];
                }
                if (!isset($target[$bhId][$pdId])) {
                    $target[$bhId][$pdId] = 0;
                }
                $target[$bhId][$pdId] += floatval($row->total ?? 0);
            }
        };

        $addTo($releaseByBh, $tsaRelease);
        $addTo($releaseByBh, $loaAmounts);
        $addTo($releaseByBh, $adminExpAmounts);

        $addTo($expenditureByBh, $tsaExpenditure);
        $addTo($expenditureByBh, $loaAmounts);
        $addTo($expenditureByBh, $adminExpAmounts);

        return [
            'release_by_bh' => $releaseByBh,
            'expenditure_by_bh' => $expenditureByBh,
        ];
    }

    /**
     * Get mother sanction release data grouped by budget head and program division.
     * Release = Total MS (TMS: create/revise tranche nets, CF excluded; same as MS List)
     *         + TSA amount + LOA amount + Administrative Expenditure amount.
     */
    public function getMotherSanctionReleaseData(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2025-26');
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);

            // Distinct BH + PD pairs that have mother sanction rows for this FY
            $pairsQuery = DB::table('mother_sanction as ms')
                ->join('budget_heads as bh', function ($join) {
                    $join->on(DB::raw('TRIM(ms.budget_head)'), '=', DB::raw('TRIM(bh.budget)'));
                })
                ->join('md_program_divisions as pd', function ($join) {
                    $join->on(
                        DB::raw('TRIM(ms.pd_component) COLLATE utf8mb4_unicode_ci'),
                        '=',
                        DB::raw('TRIM(pd.division_name) COLLATE utf8mb4_unicode_ci')
                    );
                })
                ->whereIn('ms.financial_year', $yearVariants)
                ->whereNotNull('ms.budget_head')
                ->whereNotNull('ms.pd_component')
                ->whereNotNull('ms.mother_sanction_amount');

            $this->applyDateRangeToQuery($pairsQuery, $request, 'ms.sanction_date');

            $pairs = $pairsQuery->select(
                    'bh.id as bh_id',
                    'pd.division_id as pd_id',
                    DB::raw('TRIM(ms.budget_head) as budget_head'),
                    DB::raw('TRIM(ms.pd_component) as pd_component')
                )
                ->distinct()
                ->get();

            // One TMS calculation per bh_id + pd_id (avoids double-count from string variants)
            $uniqueByBhPd = [];
            foreach ($pairs as $pair) {
                if (!$pair->bh_id || !$pair->pd_id) {
                    continue;
                }
                $key = (string) $pair->bh_id . '|' . (string) $pair->pd_id;
                if (!isset($uniqueByBhPd[$key])) {
                    $uniqueByBhPd[$key] = $pair;
                }
            }

            $formattedData = [];
            $tmsCache = [];

            foreach ($uniqueByBhPd as $pair) {
                $budgetHead = trim((string) $pair->budget_head);
                $pdComponent = trim((string) $pair->pd_component);
                $cacheKey = $budgetHead . '|' . $pdComponent;

                if (!isset($tmsCache[$cacheKey])) {
                    $tmsCache[$cacheKey] = $this->msTotals->totalMs(
                        $budgetHead,
                        $pdComponent,
                        $financialYear
                    );
                }

                $amount = floatval($tmsCache[$cacheKey]);
                if ($amount <= 0) {
                    continue;
                }

                $bhId = (string) $pair->bh_id;
                $pdId = (string) $pair->pd_id;

                if (!isset($formattedData[$bhId])) {
                    $formattedData[$bhId] = [];
                }
                $formattedData[$bhId][$pdId] = $amount;
            }

            Log::info('Formatted Mother Sanction Release Data (TMS)', [
                'pair_count' => count($uniqueByBhPd),
                'total_budget_heads' => count($formattedData),
                'sample' => array_slice($formattedData, 0, 3, true),
            ]);

            // Add agency release amounts (TSA amount + LOA amount + Admin Exp amount) to existing release data
            $agencyData = $this->getAgencyReleaseDataForPdWiseReport($financialYear, $request);
            $formattedData = $this->mergeAgencyAmountsIntoFormattedData(
                $formattedData,
                $agencyData['release_by_bh']
            );

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'debug' => [
                    'raw_count' => count($uniqueByBhPd),
                    'formatted_count' => count($formattedData),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching mother sanction release data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch mother sanction release data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get daily sanction expenditure data grouped by budget head and program division.
     * Expenditure = daily_sanction center_share totals (PD from linked mother_sanction)
     *             + TSA expenditure + LOA amount + Administrative Expenditure amount.
     *
     * PD attribution: daily_sanction.mother_sanction + budget_head → mother_sanction.pd_component
     */
    public function getDailySanctionExpenditureData(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2025-26');
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);

            // Map mother sanction no + budget head → pd_component for the selected FY
            $msPdMapping = [];
            $msRows = DB::table('mother_sanction')
                ->where('status', 1)
                ->whereIn('financial_year', $yearVariants)
                ->whereNotNull('ky_ms_no')
                ->whereNotNull('budget_head')
                ->whereNotNull('pd_component')
                ->select(
                    DB::raw('TRIM(ky_ms_no) as ky_ms_no'),
                    DB::raw('TRIM(budget_head) as budget_head'),
                    DB::raw('TRIM(pd_component) as pd_component')
                )
                ->distinct()
                ->get();

            foreach ($msRows as $row) {
                $key = $row->ky_ms_no . '|' . $row->budget_head;
                if (!isset($msPdMapping[$key])) {
                    $msPdMapping[$key] = $row->pd_component;
                }
            }

            // Get all program divisions for efficient lookup
            $programDivisions = DB::table('md_program_divisions')
                ->select('division_id', 'division_name')
                ->get()
                ->keyBy(function ($item) {
                    return trim($item->division_name);
                });
            
            // Aggregate daily sanction expenditure for the selected FY
            $expenditureData = DB::table('daily_sanction as ds')
                ->join('budget_heads as bh', function ($join) {
                    $join->on(DB::raw('TRIM(ds.budget_head)'), '=', DB::raw('TRIM(bh.budget)'));
                })
                ->where('ds.status', 1)
                ->whereIn('ds.financial_year', $yearVariants)
                ->whereNotNull('ds.budget_head')
                ->whereNotNull('ds.mother_sanction')
                ->whereNotNull('ds.center_share_amount')
                ->where('ds.center_share_amount', '>', 0);

            $this->applyDateRangeToQuery($expenditureData, $request, 'ds.ds_date');

            $expenditureData = $expenditureData->select(
                    'bh.id as bh_id',
                    'ds.budget_head',
                    'ds.mother_sanction',
                    'ds.center_share_amount'
                )
                ->get();
            
            // Attribute each daily sanction to the PD of its linked mother sanction (same MS no + BH)
            $groupedData = [];
            foreach ($expenditureData as $record) {
                $key = trim((string) $record->mother_sanction) . '|' . trim((string) $record->budget_head);
                $pdComponent = $msPdMapping[$key] ?? null;

                if ($pdComponent === null || !$programDivisions->has($pdComponent)) {
                    continue;
                }

                $pd = $programDivisions[$pdComponent];
                $bhId = (string) $record->bh_id;
                $pdId = (string) $pd->division_id;

                if (!isset($groupedData[$bhId])) {
                    $groupedData[$bhId] = [];
                }
                if (!isset($groupedData[$bhId][$pdId])) {
                    $groupedData[$bhId][$pdId] = 0;
                }

                $groupedData[$bhId][$pdId] += floatval($record->center_share_amount ?? 0);
            }
            
            $formattedData = $groupedData;

            // Add agency expenditure amounts (TSA expenditure + LOA amount + Admin Exp amount)
            $agencyData = $this->getAgencyReleaseDataForPdWiseReport($financialYear, $request);
            $formattedData = $this->mergeAgencyAmountsIntoFormattedData(
                $formattedData,
                $agencyData['expenditure_by_bh']
            );

            Log::info('Daily Sanction Expenditure Data Query Result', [
                'raw_count' => $expenditureData->count(),
                'mapping_count' => count($msPdMapping),
                'formatted_count' => count($formattedData),
                'sample' => array_slice($formattedData, 0, 3, true)
            ]);

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'debug' => [
                    'raw_count' => $expenditureData->count(),
                    'formatted_count' => count($formattedData)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching daily sanction expenditure data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch daily sanction expenditure data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statewise AAP allocation report data
     * Returns allocation, release, and expenditure data grouped by state and PD
     */
    public function getStatewiseAapAllocationReport(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2025-26');
            
            // 1. Get allocation data from statewise_aap_allocation table
            $allocations = StatewiseAapAllocation::where('financial_year', $financialYear)
                ->get()
                ->groupBy('state_id')
                ->map(function ($stateAllocations) {
                    return $stateAllocations->keyBy('pd_id')->map(function ($allocation) {
                        return [
                            'tentative_amount' => floatval($allocation->tentative_amount ?? 0),
                            'amount' => floatval($allocation->amount ?? 0),
                            'release' => 0, // Will be populated below
                            'expenditure' => 0 // Will be populated below
                        ];
                    });
                })
                ->toArray();
            
            // 2. Get release data - sum of mother_sanction_amount grouped by state_id and pd_component
            $releaseData = DB::table('mother_sanction as ms')
                ->join('md_program_divisions as pd', function($join) {
                    $join->on(DB::raw('TRIM(ms.pd_component) COLLATE utf8mb4_unicode_ci'), '=', DB::raw('TRIM(pd.division_name) COLLATE utf8mb4_unicode_ci'));
                })
                ->where('ms.status', 1)
                ->whereNotNull('ms.state_id')
                ->whereNotNull('ms.pd_component')
                ->whereNotNull('ms.mother_sanction_amount')
                ->where('ms.mother_sanction_amount', '>', 0)
                ->select(
                    'ms.state_id',
                    'pd.division_id as pd_id',
                    DB::raw('SUM(COALESCE(ms.mother_sanction_amount, 0)) as total_release')
                )
                ->groupBy('ms.state_id', 'pd.division_id')
                ->get();
            
            // 3. Get expenditure data - sum of center_share_amount from daily_sanction
            // Map through mother_sanction to get pd_component for each state+budget_head combination
            $stateBudgetPdMapping = DB::table('mother_sanction')
                ->where('status', 1)
                ->whereNotNull('budget_head')
                ->whereNotNull('pd_component')
                ->whereNotNull('state_id')
                ->select(
                    'state_id',
                    DB::raw('TRIM(budget_head) as budget_head'),
                    'pd_component'
                )
                ->distinct()
                ->get()
                ->groupBy(function($item) {
                    return $item->state_id . '|' . trim($item->budget_head);
                });
            
            $programDivisions = DB::table('md_program_divisions')
                ->select('division_id', 'division_name')
                ->get()
                ->keyBy(function($item) {
                    return trim($item->division_name);
                });
            
            $expenditureData = DB::table('daily_sanction as ds')
                ->where('ds.status', 1)
                ->whereNotNull('ds.budget_head')
                ->whereNotNull('ds.center_share_amount')
                ->whereNotNull('ds.state_id')
                ->where('ds.center_share_amount', '>', 0)
                ->select(
                    'ds.budget_head',
                    'ds.state_id',
                    'ds.center_share_amount'
                )
                ->get();
            
            // Process expenditure data and group by state and PD
            $expenditureGrouped = [];
            foreach ($expenditureData as $record) {
                $key = $record->state_id . '|' . trim($record->budget_head);
                
                if ($stateBudgetPdMapping->has($key)) {
                    foreach ($stateBudgetPdMapping[$key] as $mapping) {
                        $pdComponent = trim($mapping->pd_component);
                        
                        if ($programDivisions->has($pdComponent)) {
                            $pd = $programDivisions[$pdComponent];
                            $stateId = (string)$record->state_id;
                            $pdId = (string)$pd->division_id;
                            
                            if (!isset($expenditureGrouped[$stateId])) {
                                $expenditureGrouped[$stateId] = [];
                            }
                            
                            if (!isset($expenditureGrouped[$stateId][$pdId])) {
                                $expenditureGrouped[$stateId][$pdId] = 0;
                            }
                            
                            $expenditureGrouped[$stateId][$pdId] += floatval($record->center_share_amount ?? 0);
                        }
                    }
                }
            }
            
            // Merge release and expenditure data into allocations
            foreach ($releaseData as $record) {
                $stateId = (string)$record->state_id;
                $pdId = (string)$record->pd_id;
                
                if (!isset($allocations[$stateId])) {
                    $allocations[$stateId] = [];
                }
                
                if (!isset($allocations[$stateId][$pdId])) {
                    $allocations[$stateId][$pdId] = [
                        'tentative_amount' => 0,
                        'amount' => 0,
                        'release' => 0,
                        'expenditure' => 0
                    ];
                }
                
                $allocations[$stateId][$pdId]['release'] = floatval($record->total_release ?? 0);
            }
            
            foreach ($expenditureGrouped as $stateId => $pdData) {
                foreach ($pdData as $pdId => $amount) {
                    if (!isset($allocations[$stateId])) {
                        $allocations[$stateId] = [];
                    }
                    
                    if (!isset($allocations[$stateId][$pdId])) {
                        $allocations[$stateId][$pdId] = [
                            'tentative_amount' => 0,
                            'amount' => 0,
                            'release' => 0,
                            'expenditure' => 0
                        ];
                    }
                    
                    $allocations[$stateId][$pdId]['expenditure'] = $amount;
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $allocations
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching statewise AAP allocation report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch report data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
