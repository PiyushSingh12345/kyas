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
     * Store statewise AAP remarks. Amounts are saved from the SLS bifurcation modal.
     */
    public function storeStatewiseAllocation(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'financial_year' => 'nullable|string',
                'remarks' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $remarks = $request->remarks ?? [];
            if (empty($remarks)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No remarks to save',
                ], 422);
            }

            $financialYear = $request->get('financial_year', '2026-27');
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);

            DB::beginTransaction();

            try {
                $updatedStates = 0;
                foreach ($remarks as $stateId => $remark) {
                    $updated = StatewiseAapAllocation::where('state_id', (int) $stateId)
                        ->whereIn('financial_year', $yearVariants)
                        ->update(['remark' => $remark !== '' ? $remark : null]);

                    if ($updated > 0) {
                        $updatedStates++;
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Remarks saved successfully',
                    'count' => $updatedStates,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save remarks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get existing statewise AAP allocation data (PD totals = sum of SLS rows)
     */
    public function getStatewiseAllocation(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2026-27');
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);

            $rows = StatewiseAapAllocation::whereIn('financial_year', $yearVariants)
                ->select(
                    'state_id',
                    'pd_id',
                    DB::raw('SUM(COALESCE(amount, 0)) as amount'),
                    DB::raw('SUM(COALESCE(tentative_amount, 0)) as tentative_amount'),
                    DB::raw('COUNT(*) as sls_count')
                )
                ->groupBy('state_id', 'pd_id')
                ->get();

            $allocations = [];
            foreach ($rows as $row) {
                $stateId = (string) $row->state_id;
                $pdId = (string) $row->pd_id;
                $allocations[$stateId][$pdId] = [
                    'amount' => $this->formatAllocationDecimal($row->amount),
                    'tentative_amount' => $this->formatAllocationDecimal($row->tentative_amount),
                    'has_sls_data' => ((int) $row->sls_count) > 0,
                    'sls_count' => (int) $row->sls_count,
                ];
            }

            $remarks = StatewiseAapAllocation::whereIn('financial_year', $yearVariants)
                ->whereNotNull('remark')
                ->where('remark', '!=', '')
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
     * SLS list + saved tentative/final amounts for one state × PD cell.
     */
    public function getStatewiseSlsBifurcation(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'financial_year' => 'required|string',
                'state_id' => 'required|integer',
                'pd_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $stateId = (int) $request->get('state_id');
            $pdId = (int) $request->get('pd_id');
            $yearVariants = $this->normalizeFinancialYearVariants($request->get('financial_year'));

            $stateName = DB::table('states')->where('id', $stateId)->value('name');
            $pdName = DB::table('md_program_divisions')->where('division_id', $pdId)->value('division_name');

            $slsComponents = $this->getSlsComponentsForStatePd($stateId, $pdId);
            $savedRows = StatewiseAapAllocation::where('state_id', $stateId)
                ->where('pd_id', $pdId)
                ->whereIn('financial_year', $yearVariants)
                ->orderBy('p_sub_id')
                ->get();

            $savedList = $savedRows->values();
            $slsRows = [];

            foreach ($slsComponents as $index => $sls) {
                $saved = $savedList->get($index);
                $slsRows[] = [
                    'sls_id' => (int) $sls->id,
                    'name' => $sls->name,
                    'full_sls_name' => $sls->full_sls_name,
                    'sls_code' => $sls->sls_code,
                    'sharing_patter_center' => $sls->sharing_patter_center,
                    'sharing_patter_state' => $sls->sharing_patter_state,
                    'tentative_amount' => $this->formatAllocationDecimal($saved->tentative_amount ?? 0),
                    'amount' => $this->formatAllocationDecimal($saved->amount ?? 0),
                ];
            }

            if ($savedList->count() > $slsComponents->count()) {
                for ($i = $slsComponents->count(); $i < $savedList->count(); $i++) {
                    $saved = $savedList->get($i);
                    $label = ((int) $saved->p_sub_id) > 0
                        ? 'SLS-' . $saved->p_sub_id
                        : 'PD total';
                    $slsRows[] = [
                        'sls_id' => null,
                        'name' => $label,
                        'full_sls_name' => $label,
                        'sls_code' => null,
                        'sharing_patter_center' => null,
                        'sharing_patter_state' => null,
                        'tentative_amount' => $this->formatAllocationDecimal($saved->tentative_amount ?? 0),
                        'amount' => $this->formatAllocationDecimal($saved->amount ?? 0),
                    ];
                }
            }

            if (empty($slsRows)) {
                $saved = $savedList->first();
                $slsRows[] = [
                    'sls_id' => null,
                    'name' => 'No SLS mapped — PD total',
                    'full_sls_name' => 'No SLS mapped for this State and Program Division',
                    'sls_code' => null,
                    'sharing_patter_center' => null,
                    'sharing_patter_state' => null,
                    'tentative_amount' => $this->formatAllocationDecimal($saved->tentative_amount ?? 0),
                    'amount' => $this->formatAllocationDecimal($saved->amount ?? 0),
                ];
            }

            return response()->json([
                'success' => true,
                'state_id' => $stateId,
                'pd_id' => $pdId,
                'state_name' => $stateName,
                'pd_name' => $pdName,
                'has_saved_data' => $savedRows->isNotEmpty(),
                'sls' => $slsRows,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve SLS bifurcation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save SLS-wise tentative/final amounts for one state × PD cell.
     */
    public function storeStatewiseSlsBifurcation(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'financial_year' => 'required|string',
                'state_id' => 'required|integer',
                'pd_id' => 'required|integer',
                'remark' => 'nullable|string|max:255',
                'sls' => 'required|array|min:1',
                'sls.*.tentative_amount' => 'required|numeric|min:0',
                'sls.*.amount' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $stateId = (int) $request->get('state_id');
            $pdId = (int) $request->get('pd_id');
            $yearVariants = $this->normalizeFinancialYearVariants($request->get('financial_year'));
            $canonicalYear = StatewiseAapAllocation::whereIn('financial_year', $yearVariants)
                ->value('financial_year') ?? $request->get('financial_year');
            $slsItems = array_values($request->get('sls', []));
            $slsCount = count($slsItems);

            $orderId = StatewiseAapAllocation::where('state_id', $stateId)
                ->whereIn('financial_year', $yearVariants)
                ->min('order_id');
            if ($orderId === null) {
                $orderId = (int) (StatewiseAapAllocation::whereIn('financial_year', $yearVariants)->max('order_id') ?? 0) + 1;
            }

            $existingRemark = StatewiseAapAllocation::where('state_id', $stateId)
                ->whereIn('financial_year', $yearVariants)
                ->whereNotNull('remark')
                ->where('remark', '!=', '')
                ->value('remark');
            $remark = $request->has('remark') ? $request->get('remark') : $existingRemark;

            DB::beginTransaction();

            try {
                $existing = StatewiseAapAllocation::where('state_id', $stateId)
                    ->where('pd_id', $pdId)
                    ->whereIn('financial_year', $yearVariants)
                    ->orderBy('p_sub_id')
                    ->get()
                    ->values();

                $usedIds = [];
                foreach ($slsItems as $index => $item) {
                    $pSubId = $slsCount === 1 ? 0 : ($index + 1);
                    $record = $existing->get($index);

                    $payload = [
                        'financial_year' => $record ? $record->financial_year : $canonicalYear,
                        'state_id' => $stateId,
                        'pd_id' => $pdId,
                        'amount' => $item['amount'],
                        'tentative_amount' => $item['tentative_amount'],
                        'status' => 1,
                        'remark' => $remark,
                        'p_sub_id' => $pSubId,
                        'order_id' => $orderId,
                    ];

                    if ($record) {
                        $record->update($payload);
                        $usedIds[] = $record->id;
                    } else {
                        $created = StatewiseAapAllocation::create($payload);
                        $usedIds[] = $created->id;
                    }
                }

                StatewiseAapAllocation::where('state_id', $stateId)
                    ->where('pd_id', $pdId)
                    ->whereIn('financial_year', $yearVariants)
                    ->whereNotIn('id', $usedIds)
                    ->get()
                    ->each(fn (StatewiseAapAllocation $row) => $row->delete());

                if ($request->has('remark')) {
                    StatewiseAapAllocation::where('state_id', $stateId)
                        ->whereIn('financial_year', $yearVariants)
                        ->update(['remark' => $remark]);
                }

                DB::commit();

                $tentativeTotal = 0.0;
                $finalTotal = 0.0;
                foreach ($slsItems as $item) {
                    $tentativeTotal += (float) $item['tentative_amount'];
                    $finalTotal += (float) $item['amount'];
                }

                return response()->json([
                    'success' => true,
                    'message' => 'SLS bifurcation saved successfully',
                    'tentative_amount' => $this->formatAllocationDecimal($tentativeTotal),
                    'amount' => $this->formatAllocationDecimal($finalTotal),
                    'has_sls_data' => true,
                    'sls_count' => $slsCount,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save SLS bifurcation',
                'error' => $e->getMessage(),
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
     * NER re-appropriation amounts for PD-wise BE/RE/FE display.
     * Amounts are keyed by destination (to) budget head — 3601 (NER states) / 2435 (NER agencies from 2552).
     */
    public function getNerReappropriationAllocationData(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2025-26');
            $data = $this->buildNerReappropriationAllocationByPhase($financialYear);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching NER reappropriation allocation data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch NER reappropriation allocation data',
                'error' => $e->getMessage(),
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
     * Format a numeric value to 5 decimal places without rounding.
     */
    private function formatAllocationDecimal($value): string
    {
        if ($value === null || $value === '') {
            return '0.00000';
        }

        $amountStr = (string) $value;
        if (strpos($amountStr, '.') !== false) {
            $parts = explode('.', $amountStr);
            $integerPart = $parts[0];
            $decimalPart = isset($parts[1]) ? substr($parts[1], 0, 5) : '';
            $decimalPart = str_pad($decimalPart, 5, '0', STR_PAD_RIGHT);

            return $integerPart . '.' . $decimalPart;
        }

        return $amountStr . '.00000';
    }

    /**
     * SLS rows for a state + program division from pd_and_sls_comp.
     * SLS-1 is typically CSS (state share > 0); SLS-2 is 100% central.
     */
    private function getSlsComponentsForStatePd(int $stateId, int $pdId)
    {
        $rows = DB::table('pd_and_sls_comp')
            ->where('state_id', $stateId)
            ->where('status', 1)
            ->where('pd_id', $pdId)
            ->orderByRaw('CAST(IFNULL(sharing_patter_state, 0) AS UNSIGNED) DESC')
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'full_sls_name',
                'sls_code',
                'slsPD',
                'sharing_patter_center',
                'sharing_patter_state',
                'pd_id',
            ]);

        if ($rows->isNotEmpty()) {
            return $rows;
        }

        $pdName = trim((string) DB::table('md_program_divisions')
            ->where('division_id', $pdId)
            ->value('division_name'));

        if ($pdName === '') {
            return $rows;
        }

        return DB::table('pd_and_sls_comp')
            ->where('state_id', $stateId)
            ->where('status', 1)
            ->whereRaw('TRIM(slsPD) = ?', [$pdName])
            ->orderByRaw('CAST(IFNULL(sharing_patter_state, 0) AS UNSIGNED) DESC')
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'full_sls_name',
                'sls_code',
                'slsPD',
                'sharing_patter_center',
                'sharing_patter_state',
                'pd_id',
            ]);
    }

    /**
     * Sum tentative/final amounts per state + PD (SLS rows rolled up).
     */
    private function getStatePdAllocationTotals(array $yearVariants, ?int $stateId = null)
    {
        $query = StatewiseAapAllocation::query()
            ->whereIn('financial_year', $yearVariants)
            ->where('status', 1)
            ->whereNotNull('state_id')
            ->whereNotNull('pd_id')
            ->select(
                'state_id',
                'pd_id',
                DB::raw('SUM(COALESCE(amount, 0)) as amount'),
                DB::raw('SUM(COALESCE(tentative_amount, 0)) as tentative_amount')
            )
            ->groupBy('state_id', 'pd_id');

        if ($stateId) {
            $query->where('state_id', $stateId);
        }

        return $query->get();
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
     * NER state IDs (states.description = NER): Arunachal, Assam, Manipur, Meghalaya, Mizoram, Nagaland, Sikkim, Tripura.
     *
     * @return array<int, int>
     */
    private function getNerStateIds(): array
    {
        return DB::table('states')
            ->whereRaw("TRIM(description) = 'NER'")
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Decode selected_entity_ids from reappropriations (array or JSON string).
     *
     * @param mixed $value
     * @return array<int, int>
     */
    private function decodeSelectedEntityIds($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('intval', $value)));
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('intval', $decoded)));
            }
        }

        return [];
    }

    /**
     * Build NER re-appropriation totals by budget phase → to_bh_id → pd_id.
     * - 2552 → 3601 for State/UT when any selected state is NER
     * - 2552 → 2435 for Agency (NER agency path from 2552)
     *
     * @return array<string, array<string, array<string, float>>>
     */
    private function buildNerReappropriationAllocationByPhase(string $financialYear): array
    {
        $yearVariants = $this->normalizeFinancialYearVariants($financialYear);
        $nerStateIds = $this->getNerStateIds();
        $nerStateIdSet = array_fill_keys($nerStateIds, true);

        $result = [
            'BE' => [],
            'RE' => [],
            'FE' => [],
        ];

        if (empty($yearVariants)) {
            return $result;
        }

        $rows = DB::table('reappropriations as r')
            ->join('budget_heads as fh', 'fh.id', '=', 'r.from_budget_head_id')
            ->join('budget_heads as th', 'th.id', '=', 'r.to_budget_head_id')
            ->whereIn('r.financial_year', $yearVariants)
            ->whereNotNull('r.program_division_id')
            ->whereNotNull('r.to_budget_head_id')
            ->where('r.reappropriation_amount', '>', 0)
            ->select(
                'r.budget_phase',
                'r.program_division_id as pd_id',
                'r.to_budget_head_id as to_bh_id',
                'r.reappropriation_amount',
                'r.entity_type',
                'r.selected_entity_ids',
                DB::raw('TRIM(fh.budget) as from_budget'),
                DB::raw('TRIM(th.budget) as to_budget')
            )
            ->get();

        foreach ($rows as $row) {
            $fromDigits = preg_replace('/[^0-9]/', '', (string) $row->from_budget) ?: (string) $row->from_budget;
            $toDigits = preg_replace('/[^0-9]/', '', (string) $row->to_budget) ?: (string) $row->to_budget;

            if (!str_starts_with($fromDigits, '2552')) {
                continue;
            }

            $phase = strtoupper(trim((string) ($row->budget_phase ?? '')));
            if (!isset($result[$phase])) {
                continue;
            }

            $isNer3601 = str_starts_with($toDigits, '3601')
                && strcasecmp(trim((string) ($row->entity_type ?? '')), 'State/UT') === 0
                && $this->selectedEntitiesIncludeNer($row->selected_entity_ids, $nerStateIdSet);

            // Agency releases for NER use 2552 → 2435 re-appropriation (same path as is_ner TSA)
            $isNer2435 = str_starts_with($toDigits, '2435')
                && strcasecmp(trim((string) ($row->entity_type ?? '')), 'Agency') === 0;

            if (!$isNer3601 && !$isNer2435) {
                continue;
            }

            $bhId = (string) $row->to_bh_id;
            $pdId = (string) $row->pd_id;
            $amount = floatval($row->reappropriation_amount ?? 0);
            if ($amount <= 0) {
                continue;
            }

            if (!isset($result[$phase][$bhId])) {
                $result[$phase][$bhId] = [];
            }
            if (!isset($result[$phase][$bhId][$pdId])) {
                $result[$phase][$bhId][$pdId] = 0;
            }
            $result[$phase][$bhId][$pdId] += $amount;
        }

        return $result;
    }

    /**
     * @param mixed $selectedEntityIds
     * @param array<int, bool> $nerStateIdSet
     */
    private function selectedEntitiesIncludeNer($selectedEntityIds, array $nerStateIdSet): bool
    {
        if (empty($nerStateIdSet)) {
            return false;
        }

        foreach ($this->decodeSelectedEntityIds($selectedEntityIds) as $entityId) {
            if (isset($nerStateIdSet[$entityId])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get agency release/expenditure totals for the PD-wise budget allocation release report.
     * TSA amount contributes to release; TSA expenditure contributes to expenditure.
     * LOA and Administrative Expenditure amounts contribute to both release and expenditure.
     * NER TSA / Administrative Expenditure (is_ner=1) is also returned separately for 2552/2435 MIS checkbox logic.
     *
     * @return array{
     *   release_by_bh: array<string, array<string, float>>,
     *   expenditure_by_bh: array<string, array<string, float>>,
     *   ner_release_by_bh: array<string, array<string, float>>,
     *   ner_expenditure_by_bh: array<string, array<string, float>>
     * }
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

        // NER agency releases: TSA rows flagged is_ner=1 (re-appropriated 2552 → 2435 for NER agencies)
        $tsaNerRelease = $agencyBaseQuery('agency_release_tsa')
            ->where('is_ner', 1)
            ->select(
                DB::raw('TRIM(budget_head) as budget_head'),
                'program_division_id as pd_id',
                DB::raw('SUM(COALESCE(amount, 0)) as total')
            )
            ->groupBy(DB::raw('TRIM(budget_head)'), 'program_division_id')
            ->get();

        $tsaNerExpenditure = $agencyBaseQuery('agency_release_tsa')
            ->where('is_ner', 1)
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

        // NER agency admin expenditure: rows flagged is_ner=1 (re-appropriated 2552 → 2435 for NER agencies)
        $adminExpNerAmounts = $agencyBaseQuery('agency_release_administrative_expenditure')
            ->where('is_ner', 1)
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
        $nerReleaseByBh = [];
        $nerExpenditureByBh = [];

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

        $addTo($nerReleaseByBh, $tsaNerRelease);
        $addTo($nerReleaseByBh, $adminExpNerAmounts);
        $addTo($nerExpenditureByBh, $tsaNerExpenditure);
        $addTo($nerExpenditureByBh, $adminExpNerAmounts);

        return [
            'release_by_bh' => $releaseByBh,
            'expenditure_by_bh' => $expenditureByBh,
            'ner_release_by_bh' => $nerReleaseByBh,
            'ner_expenditure_by_bh' => $nerExpenditureByBh,
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
            $nerFormattedData = [];
            $tmsCache = [];
            $nerTmsCache = [];
            $nerStateIds = $this->getNerStateIds();

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
                $bhId = (string) $pair->bh_id;
                $pdId = (string) $pair->pd_id;

                if ($amount > 0) {
                    if (!isset($formattedData[$bhId])) {
                        $formattedData[$bhId] = [];
                    }
                    $formattedData[$bhId][$pdId] = $amount;
                }

                // NER release under 3601: TMS for NER states only (re-appropriated from 2552)
                if (!empty($nerStateIds) && str_starts_with(preg_replace('/[^0-9]/', '', $budgetHead) ?: $budgetHead, '3601')) {
                    if (!isset($nerTmsCache[$cacheKey])) {
                        $nerTotal = 0.0;
                        foreach ($nerStateIds as $nerStateId) {
                            $nerTotal += $this->msTotals->totalMs(
                                $budgetHead,
                                $pdComponent,
                                $financialYear,
                                (int) $nerStateId
                            );
                        }
                        $nerTmsCache[$cacheKey] = $nerTotal;
                    }

                    $nerAmount = floatval($nerTmsCache[$cacheKey]);
                    if ($nerAmount > 0) {
                        if (!isset($nerFormattedData[$bhId])) {
                            $nerFormattedData[$bhId] = [];
                        }
                        $nerFormattedData[$bhId][$pdId] = $nerAmount;
                    }
                }
            }

            Log::info('Formatted Mother Sanction Release Data (TMS)', [
                'pair_count' => count($uniqueByBhPd),
                'total_budget_heads' => count($formattedData),
                'ner_budget_heads' => count($nerFormattedData),
                'sample' => array_slice($formattedData, 0, 3, true),
            ]);

            // Add agency release amounts (TSA amount + LOA amount + Admin Exp amount) to existing release data
            $agencyData = $this->getAgencyReleaseDataForPdWiseReport($financialYear, $request);
            $formattedData = $this->mergeAgencyAmountsIntoFormattedData(
                $formattedData,
                $agencyData['release_by_bh']
            );
            // NER agency TSA (is_ner=1) — typically under 2435 after 2552→2435 re-appropriation
            $nerFormattedData = $this->mergeAgencyAmountsIntoFormattedData(
                $nerFormattedData,
                $agencyData['ner_release_by_bh']
            );

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'ner_data' => $nerFormattedData,
                'debug' => [
                    'raw_count' => count($uniqueByBhPd),
                    'formatted_count' => count($formattedData),
                    'ner_formatted_count' => count($nerFormattedData),
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
                    'ds.state_id',
                    'ds.center_share_amount'
                )
                ->get();
            
            $nerStateIds = $this->getNerStateIds();
            $nerStateIdSet = array_fill_keys($nerStateIds, true);

            // Attribute each daily sanction to the PD of its linked mother sanction (same MS no + BH)
            $groupedData = [];
            $nerGroupedData = [];
            foreach ($expenditureData as $record) {
                $key = trim((string) $record->mother_sanction) . '|' . trim((string) $record->budget_head);
                $pdComponent = $msPdMapping[$key] ?? null;

                if ($pdComponent === null || !$programDivisions->has($pdComponent)) {
                    continue;
                }

                $pd = $programDivisions[$pdComponent];
                $bhId = (string) $record->bh_id;
                $pdId = (string) $pd->division_id;
                $amount = floatval($record->center_share_amount ?? 0);

                if (!isset($groupedData[$bhId])) {
                    $groupedData[$bhId] = [];
                }
                if (!isset($groupedData[$bhId][$pdId])) {
                    $groupedData[$bhId][$pdId] = 0;
                }

                $groupedData[$bhId][$pdId] += $amount;

                // NER expenditure under 3601: DS for NER states (re-appropriated from 2552)
                $budgetDigits = preg_replace('/[^0-9]/', '', trim((string) $record->budget_head)) ?: trim((string) $record->budget_head);
                $isNerState = isset($nerStateIdSet[(int) ($record->state_id ?? 0)]);
                if ($isNerState && str_starts_with($budgetDigits, '3601')) {
                    if (!isset($nerGroupedData[$bhId])) {
                        $nerGroupedData[$bhId] = [];
                    }
                    if (!isset($nerGroupedData[$bhId][$pdId])) {
                        $nerGroupedData[$bhId][$pdId] = 0;
                    }
                    $nerGroupedData[$bhId][$pdId] += $amount;
                }
            }
            
            $formattedData = $groupedData;
            $nerFormattedData = $nerGroupedData;

            // Add agency expenditure amounts (TSA expenditure + LOA amount + Administrative Expenditure amount)
            $agencyData = $this->getAgencyReleaseDataForPdWiseReport($financialYear, $request);
            $formattedData = $this->mergeAgencyAmountsIntoFormattedData(
                $formattedData,
                $agencyData['expenditure_by_bh']
            );
            // NER agency TSA expenditure (is_ner=1) — typically under 2435
            $nerFormattedData = $this->mergeAgencyAmountsIntoFormattedData(
                $nerFormattedData,
                $agencyData['ner_expenditure_by_bh']
            );

            Log::info('Daily Sanction Expenditure Data Query Result', [
                'raw_count' => $expenditureData->count(),
                'mapping_count' => count($msPdMapping),
                'formatted_count' => count($formattedData),
                'ner_formatted_count' => count($nerFormattedData),
                'sample' => array_slice($formattedData, 0, 3, true)
            ]);

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'ner_data' => $nerFormattedData,
                'debug' => [
                    'raw_count' => $expenditureData->count(),
                    'formatted_count' => count($formattedData),
                    'ner_formatted_count' => count($nerFormattedData),
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
     * Get statewise AAP allocation report data.
     * Tentative/Final from statewise_aap_allocation; Release/Expenditure via state→PD from pd_and_sls_comp.
     */
    public function getStatewiseAapAllocationReport(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2025-26');
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);

            // 1. Tentative + Final Allocation from statewise_aap_allocation (state_id + pd_id, SLS summed)
            $allocations = [];
            $allocationRows = $this->getStatePdAllocationTotals($yearVariants);

            foreach ($allocationRows as $row) {
                $stateId = (string) $row->state_id;
                $pdId = (string) $row->pd_id;

                if (!isset($allocations[$stateId])) {
                    $allocations[$stateId] = [];
                }

                $allocations[$stateId][$pdId] = [
                    'tentative_amount' => floatval($row->tentative_amount ?? 0),
                    'amount' => floatval($row->amount ?? 0),
                    'release' => 0.0,
                    'expenditure' => 0.0,
                ];
            }

            // 2. State → PD from pd_and_sls_comp; Release = Total MS per state+BH+PD; Expenditure via DS
            $statePdPairs = $this->getStatePdPairsFromSlsComp();
            $allowedStatePd = [];

            foreach ($statePdPairs as $pair) {
                $stateId = (string) $pair->state_id;
                $pdId = (string) $pair->pd_id;
                $allowedStatePd[$stateId . '|' . $pdId] = true;

                if (!isset($allocations[$stateId])) {
                    $allocations[$stateId] = [];
                }

                if (!isset($allocations[$stateId][$pdId])) {
                    $allocations[$stateId][$pdId] = [
                        'tentative_amount' => 0,
                        'amount' => 0,
                        'release' => 0.0,
                        'expenditure' => 0.0,
                    ];
                } else {
                    $allocations[$stateId][$pdId]['release'] = 0.0;
                    $allocations[$stateId][$pdId]['expenditure'] = 0.0;
                }
            }

            $this->applyStateWiseReleaseAmounts(
                $allocations,
                $allowedStatePd,
                $financialYear,
                $yearVariants,
                $request
            );

            $this->applyStateWiseExpenditureAmounts(
                $allocations,
                $allowedStatePd,
                $yearVariants,
                $request
            );

            return response()->json([
                'success' => true,
                'data' => $allocations,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching statewise AAP allocation report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch report data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * StateWise Release MIS report.
     * Returns Allocation, Release, and Expenditure keyed by state_id => pd_id.
     *
     * Flow:
     * 1. State → PD from pd_and_sls_comp (state_id, slsPD)
     * 2. Allocation = SUM of BH amounts for that PD from pdwise_aap_allocation
     * 3. Release / Expenditure for the same state + PD (via BH + MS / DS)
     */
    public function getStateWiseReleaseReport(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2026-27');
            $budgetPhase = $request->get('budget_phase', 'BE');
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);

            // State → PD pairs from pd_and_sls_comp (slsPD → program division)
            $statePdPairs = $this->getStatePdPairsFromSlsComp();

            // Allocation: sum of all BH amounts per PD (pdwise_aap_allocation)
            $pdAllocationSums = $this->getPdwiseBhAllocationSumByPd($yearVariants, $budgetPhase);

            $reportData = [];
            $allowedStatePd = [];

            foreach ($statePdPairs as $pair) {
                $stateId = (string) $pair->state_id;
                $pdId = (string) $pair->pd_id;
                $allowedStatePd[$stateId . '|' . $pdId] = true;

                if (!isset($reportData[$stateId])) {
                    $reportData[$stateId] = [];
                }
                $reportData[$stateId][$pdId] = [
                    'allocation' => floatval($pdAllocationSums[(int) $pair->pd_id] ?? $pdAllocationSums[$pdId] ?? 0),
                    'release' => 0.0,
                    'expenditure' => 0.0,
                ];
            }

            // Release — Total MS for (state, BH, PD), only for mapped state→PD pairs
            $this->applyStateWiseReleaseAmounts(
                $reportData,
                $allowedStatePd,
                $financialYear,
                $yearVariants,
                $request
            );

            // Expenditure — DS center share mapped via MS (MS no + BH → PD), same state→PD pairs
            $this->applyStateWiseExpenditureAmounts(
                $reportData,
                $allowedStatePd,
                $yearVariants,
                $request
            );

            return response()->json([
                'success' => true,
                'data' => $reportData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching StateWise Release report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch StateWise Release report data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Distinct state_id + PD (division_id) from pd_and_sls_comp.slsPD.
     *
     * @return \Illuminate\Support\Collection<int, object{state_id:int,pd_id:int,pd_name:string}>
     */
    private function getStatePdPairsFromSlsComp()
    {
        return DB::table('pd_and_sls_comp as psc')
            ->join('md_program_divisions as pd', function ($join) {
                $join->on(
                    DB::raw('TRIM(psc.slsPD) COLLATE utf8mb4_unicode_ci'),
                    '=',
                    DB::raw('TRIM(pd.division_name) COLLATE utf8mb4_unicode_ci')
                );
            })
            ->where('psc.status', 1)
            ->whereNotNull('psc.state_id')
            ->whereNotNull('psc.slsPD')
            ->whereRaw("TRIM(psc.slsPD) <> ''")
            ->select(
                'psc.state_id',
                'pd.division_id as pd_id',
                DB::raw('TRIM(pd.division_name) as pd_name')
            )
            ->distinct()
            ->get();
    }

    /**
     * Sum of BH amounts per PD from pdwise_aap_allocation for the FY (+ optional phase).
     *
     * @param  array<int, string>  $yearVariants
     * @return \Illuminate\Support\Collection<int|string, float|string>
     */
    private function getPdwiseBhAllocationSumByPd(array $yearVariants, ?string $budgetPhase = 'BE')
    {
        $query = DB::table('pdwise_aap_allocation')
            ->whereIn('financial_year', $yearVariants)
            ->where('status', 1)
            ->whereNotNull('pd_id')
            ->whereNotNull('bh_id');

        if ($budgetPhase && $budgetPhase !== '0') {
            $query->where('budget_phase', $budgetPhase);
        }

        return $query
            ->select('pd_id', DB::raw('SUM(COALESCE(amount, 0)) as total_allocation'))
            ->groupBy('pd_id')
            ->pluck('total_allocation', 'pd_id');
    }

    /**
     * Fill release amounts on $reportData for allowed state|pd keys.
     *
     * @param  array<string, array<string, array{allocation:float,release:float,expenditure:float}>>  $reportData
     * @param  array<string, bool>  $allowedStatePd
     * @param  array<int, string>  $yearVariants
     */
    private function applyStateWiseReleaseAmounts(
        array &$reportData,
        array $allowedStatePd,
        string $financialYear,
        array $yearVariants,
        Request $request
    ): void {
        $releasePairsQuery = DB::table('mother_sanction as ms')
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
            ->whereNotNull('ms.state_id')
            ->whereNotNull('ms.budget_head')
            ->whereNotNull('ms.pd_component')
            ->whereNotNull('ms.mother_sanction_amount');

        $this->applyDateRangeToQuery($releasePairsQuery, $request, 'ms.sanction_date');

        $releasePairs = $releasePairsQuery->select(
                'ms.state_id',
                'bh.id as bh_id',
                'pd.division_id as pd_id',
                DB::raw('TRIM(ms.budget_head) as budget_head'),
                DB::raw('TRIM(ms.pd_component) as pd_component')
            )
            ->distinct()
            ->get();

        $uniqueStateBhPd = [];
        foreach ($releasePairs as $pair) {
            if (!$pair->state_id || !$pair->bh_id || !$pair->pd_id) {
                continue;
            }
            $statePdKey = (string) $pair->state_id . '|' . (string) $pair->pd_id;
            if (!isset($allowedStatePd[$statePdKey])) {
                continue;
            }
            $key = (string) $pair->state_id . '|' . (string) $pair->bh_id . '|' . (string) $pair->pd_id;
            if (!isset($uniqueStateBhPd[$key])) {
                $uniqueStateBhPd[$key] = $pair;
            }
        }

        $tmsCache = [];
        foreach ($uniqueStateBhPd as $pair) {
            $budgetHead = trim((string) $pair->budget_head);
            $pdComponent = trim((string) $pair->pd_component);
            $stateIdInt = (int) $pair->state_id;
            $cacheKey = $stateIdInt . '|' . $budgetHead . '|' . $pdComponent;

            if (!isset($tmsCache[$cacheKey])) {
                $tmsCache[$cacheKey] = $this->msTotals->totalMs(
                    $budgetHead,
                    $pdComponent,
                    $financialYear,
                    $stateIdInt
                );
            }

            $amount = floatval($tmsCache[$cacheKey]);
            if ($amount <= 0) {
                continue;
            }

            $stateId = (string) $pair->state_id;
            $pdId = (string) $pair->pd_id;
            if (!isset($reportData[$stateId][$pdId])) {
                continue;
            }
            $reportData[$stateId][$pdId]['release'] += $amount;
        }
    }

    /**
     * Fill expenditure amounts on $reportData for allowed state|pd keys.
     *
     * @param  array<string, array<string, array{allocation:float,release:float,expenditure:float}>>  $reportData
     * @param  array<string, bool>  $allowedStatePd
     * @param  array<int, string>  $yearVariants
     */
    private function applyStateWiseExpenditureAmounts(
        array &$reportData,
        array $allowedStatePd,
        array $yearVariants,
        Request $request
    ): void {
        $msPdMapping = [];
        $msRows = DB::table('mother_sanction')
            ->where('status', 1)
            ->whereIn('financial_year', $yearVariants)
            ->whereNotNull('ky_ms_no')
            ->whereNotNull('budget_head')
            ->whereNotNull('pd_component')
            ->whereNotNull('state_id')
            ->select(
                'state_id',
                DB::raw('TRIM(ky_ms_no) as ky_ms_no'),
                DB::raw('TRIM(budget_head) as budget_head'),
                DB::raw('TRIM(pd_component) as pd_component')
            )
            ->distinct()
            ->get();

        foreach ($msRows as $row) {
            $key = (string) $row->state_id . '|' . $row->ky_ms_no . '|' . $row->budget_head;
            if (!isset($msPdMapping[$key])) {
                $msPdMapping[$key] = $row->pd_component;
            }
        }

        $programDivisions = DB::table('md_program_divisions')
            ->select('division_id', 'division_name')
            ->get()
            ->keyBy(function ($item) {
                return trim($item->division_name);
            });

        $expenditureQuery = DB::table('daily_sanction as ds')
            ->join('budget_heads as bh', function ($join) {
                $join->on(DB::raw('TRIM(ds.budget_head)'), '=', DB::raw('TRIM(bh.budget)'));
            })
            ->where('ds.status', 1)
            ->whereIn('ds.financial_year', $yearVariants)
            ->whereNotNull('ds.state_id')
            ->whereNotNull('ds.budget_head')
            ->whereNotNull('ds.mother_sanction')
            ->whereNotNull('ds.center_share_amount')
            ->where('ds.center_share_amount', '>', 0);

        $this->applyDateRangeToQuery($expenditureQuery, $request, 'ds.ds_date');

        $expenditureRows = $expenditureQuery->select(
                'ds.state_id',
                'ds.budget_head',
                'ds.mother_sanction',
                'ds.center_share_amount'
            )
            ->get();

        foreach ($expenditureRows as $record) {
            $stateId = (string) $record->state_id;
            $mapKey = $stateId . '|' . trim((string) $record->mother_sanction) . '|' . trim((string) $record->budget_head);
            $pdComponent = $msPdMapping[$mapKey] ?? null;

            if ($pdComponent === null || !$programDivisions->has($pdComponent)) {
                continue;
            }

            $pdId = (string) $programDivisions[$pdComponent]->division_id;
            $statePdKey = $stateId . '|' . $pdId;
            if (!isset($allowedStatePd[$statePdKey]) || !isset($reportData[$stateId][$pdId])) {
                continue;
            }

            $reportData[$stateId][$pdId]['expenditure'] += floatval($record->center_share_amount ?? 0);
        }
    }

    /**
     * PD-wise, State/UT-wise Allocation, Release & Expenditure Summary.
     *
     * Per state + PD:
     * - AAP Approved  = statewise_aap_allocation.amount
     * - BE Allocated  = 80% of AAP Approved
     * - MS 1 / MS 2   = net mother sanction amounts for ms_sequence_no 1 / 2
     * - Total Release = MS1 + MS2
     * - Expenditure   = daily sanction center share linked to MS for that state + PD
     * - % vs Release / % vs BE
     */
    public function getPdwiseStatewiseAllocationReport(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2026-27');
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);

            $reportData = [];
            $allowedStatePd = [];

            // 1. AAP Approved from statewise_aap_allocation (SLS summed)
            $allocationRows = $this->getStatePdAllocationTotals($yearVariants);

            foreach ($allocationRows as $row) {
                $stateId = (string) $row->state_id;
                $pdId = (string) $row->pd_id;
                $aapApproved = floatval($row->amount ?? 0);

                $allowedStatePd[$stateId . '|' . $pdId] = true;
                $reportData[$stateId][$pdId] = $this->emptyPdwiseStatewiseCell($aapApproved);
            }

            // Ensure state→PD pairs from SLS mapping exist even without allocation rows
            foreach ($this->getStatePdPairsFromSlsComp() as $pair) {
                $stateId = (string) $pair->state_id;
                $pdId = (string) $pair->pd_id;
                $allowedStatePd[$stateId . '|' . $pdId] = true;

                if (!isset($reportData[$stateId][$pdId])) {
                    $reportData[$stateId][$pdId] = $this->emptyPdwiseStatewiseCell(0.0);
                }
            }

            // 2. MS 1 / MS 2 by ms_sequence_no (net of carry-forward, same as Total MS logic)
            $this->applyPdwiseStatewiseMsInstallments(
                $reportData,
                $allowedStatePd,
                $yearVariants,
                $request
            );

            // 3. Expenditure from daily sanctions
            $this->applyStateWiseExpenditureAmounts(
                $reportData,
                $allowedStatePd,
                $yearVariants,
                $request
            );

            // 4. Derived fields
            foreach ($reportData as $stateId => $pdRows) {
                foreach ($pdRows as $pdId => $cell) {
                    $aap = floatval($cell['aap_approved'] ?? 0);
                    $be = round($aap * 0.80, 5);
                    $ms1 = floatval($cell['ms1'] ?? 0);
                    $ms2 = floatval($cell['ms2'] ?? 0);
                    $release = $ms1 + $ms2;
                    $expenditure = floatval($cell['expenditure'] ?? 0);

                    $reportData[$stateId][$pdId]['be_allocated'] = $be;
                    $reportData[$stateId][$pdId]['total_release'] = $release;
                    $reportData[$stateId][$pdId]['pct_exp_against_release'] = $release > 0
                        ? ($expenditure / $release) * 100
                        : null;
                    $reportData[$stateId][$pdId]['pct_exp_against_be'] = $be > 0
                        ? ($expenditure / $be) * 100
                        : null;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $reportData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching PD-wise State/UT-wise allocation report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch PD-wise State/UT-wise allocation report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @return array{
     *   aap_approved: float,
     *   be_allocated: float,
     *   ms1: float,
     *   ms2: float,
     *   total_release: float,
     *   expenditure: float,
     *   pct_exp_against_release: ?float,
     *   pct_exp_against_be: ?float
     * }
     */
    private function emptyPdwiseStatewiseCell(float $aapApproved): array
    {
        return [
            'aap_approved' => $aapApproved,
            'be_allocated' => round($aapApproved * 0.80, 5),
            'ms1' => 0.0,
            'ms2' => 0.0,
            'total_release' => 0.0,
            'expenditure' => 0.0,
            'pct_exp_against_release' => null,
            'pct_exp_against_be' => null,
        ];
    }

    /**
     * Sum net MS amounts into ms1 / ms2 by ms_sequence_no for allowed state|pd keys.
     *
     * @param  array<string, array<string, array<string, mixed>>>  $reportData
     * @param  array<string, bool>  $allowedStatePd
     * @param  array<int, string>  $yearVariants
     */
    private function applyPdwiseStatewiseMsInstallments(
        array &$reportData,
        array &$allowedStatePd,
        array $yearVariants,
        Request $request
    ): void {
        $msQuery = DB::table('mother_sanction as ms')
            ->join('md_program_divisions as pd', function ($join) {
                $join->on(
                    DB::raw('TRIM(ms.pd_component) COLLATE utf8mb4_unicode_ci'),
                    '=',
                    DB::raw('TRIM(pd.division_name) COLLATE utf8mb4_unicode_ci')
                );
            })
            ->whereIn('ms.financial_year', $yearVariants)
            ->whereNotNull('ms.state_id')
            ->whereNotNull('ms.pd_component')
            ->whereNotNull('ms.mother_sanction_amount')
            ->whereIn('ms.ms_sequence_no', ['1', '2', 1, 2])
            ->whereRaw("UPPER(COALESCE(ms.action_type, '')) <> 'CLOSED'");

        $this->applyDateRangeToQuery($msQuery, $request, 'ms.sanction_date');

        $msRecords = $msQuery->select(
                'ms.id',
                'ms.state_id',
                'ms.ms_sequence_no',
                'ms.mother_sanction_amount',
                'ms.carry_forward_amount',
                'ms.action_type',
                'pd.division_id as pd_id'
            )
            ->get();

        if ($msRecords->isEmpty()) {
            return;
        }

        $creationNetById = $this->msTotals->loadCreationNetAmountsByRecordIdPublic(
            $msRecords->pluck('id')->unique()->filter()->values()->all()
        );

        foreach ($msRecords as $record) {
            $stateId = (string) $record->state_id;
            $pdId = (string) $record->pd_id;
            $statePdKey = $stateId . '|' . $pdId;

            if (!isset($allowedStatePd[$statePdKey])) {
                // Include MS rows even when no SLS/allocation mapping exists yet
                $allowedStatePd[$statePdKey] = true;
            }

            if (!isset($reportData[$stateId][$pdId])) {
                $reportData[$stateId][$pdId] = $this->emptyPdwiseStatewiseCell(0.0);
            }

            $sequence = (string) intval($record->ms_sequence_no);
            $net = $this->msTotals->netAmountForRecord($record, $creationNetById);

            if ($sequence === '1') {
                $reportData[$stateId][$pdId]['ms1'] += $net;
            } elseif ($sequence === '2') {
                $reportData[$stateId][$pdId]['ms2'] += $net;
            }
        }
    }

    /**
     * SOM Status - Krishonnati Yojana report.
     *
     * Grouped by major head (from states.description):
     * - State                  → Major Head 3601
     * - NER                    → Major Head 2552 (+ Agency/Others)
     * - UT with legislature    → Major Head 3602
     * - UT without Legislature → Major Head 2435 (+ Agency/Others)
     *
     * Columns:
     * - PAC Approved Allocation = SUM(statewise_aap_allocation.amount) per state
     * - 1st Mother Sanction     = total net mother sanction per state
     * - Expenditure             = SUM(daily_sanction.center_share_amount) per state
     * - %                       = (Expenditure / 1st Mother Sanction) * 100
     */
    public function getSomStatusKyReport(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2026-27');
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);

            $pacByState = StatewiseAapAllocation::whereIn('financial_year', $yearVariants)
                ->where('status', 1)
                ->whereNotNull('state_id')
                ->select('state_id', DB::raw('SUM(COALESCE(amount, 0)) as total'))
                ->groupBy('state_id')
                ->pluck('total', 'state_id');

            $msByState = $this->getSomMotherSanctionTotalsByState($yearVariants, $request);
            $expByState = $this->getSomExpenditureTotalsByState($yearVariants, $request);
            $agencyByMajorHead = $this->getSomAgencyTotalsByMajorHead($financialYear, $request);

            $states = DB::table('states')
                ->select('id', 'name', 'description')
                ->orderBy('name')
                ->get();

            $sectionDefs = [
                '3601' => [
                    'major_head' => '3601',
                    'label' => 'Major Head 3601',
                    'match' => fn (string $d) => strcasecmp($d, 'State') === 0,
                    'include_agency' => false,
                ],
                '2552' => [
                    'major_head' => '2552',
                    'label' => 'Major Head 2552 (For NER states)',
                    'match' => fn (string $d) => strcasecmp($d, 'NER') === 0,
                    'include_agency' => true,
                ],
                '3602' => [
                    'major_head' => '3602',
                    'label' => 'Major Head 3602 (UT with legislature)',
                    'match' => fn (string $d) => strcasecmp($d, 'UT with legislature') === 0,
                    'include_agency' => false,
                ],
                '2435' => [
                    'major_head' => '2435',
                    'label' => 'Major Head 2435',
                    'match' => fn (string $d) => strcasecmp($d, 'UT without Legislature') === 0,
                    'include_agency' => true,
                ],
            ];

            $sections = [];
            $grand = $this->emptySomTotals();

            foreach ($sectionDefs as $def) {
                $rows = [];
                $slNo = 1;

                foreach ($states as $state) {
                    $description = trim((string) ($state->description ?? ''));
                    if (!$def['match']($description)) {
                        continue;
                    }

                    $stateId = (int) $state->id;
                    $pac = floatval($pacByState[$stateId] ?? 0);
                    $ms = floatval($msByState[$stateId] ?? 0);
                    $exp = floatval($expByState[$stateId] ?? 0);

                    $rows[] = [
                        'sl_no' => $slNo++,
                        'state_id' => $stateId,
                        'state_name' => $state->name,
                        'is_agency' => false,
                        'pac_approved' => $pac,
                        'mother_sanction' => $ms,
                        'expenditure' => $exp,
                        'pct' => $ms > 0 ? ($exp / $ms) * 100 : null,
                    ];
                }

                if ($def['include_agency']) {
                    $agency = $agencyByMajorHead[$def['major_head']] ?? [
                        'pac_approved' => 0.0,
                        'mother_sanction' => 0.0,
                        'expenditure' => 0.0,
                    ];
                    $agencyMs = floatval($agency['mother_sanction']);
                    $agencyExp = floatval($agency['expenditure']);

                    $rows[] = [
                        'sl_no' => $slNo,
                        'state_id' => null,
                        'state_name' => 'Agency/ Others',
                        'is_agency' => true,
                        'pac_approved' => floatval($agency['pac_approved']),
                        'mother_sanction' => $agencyMs,
                        'expenditure' => $agencyExp,
                        'pct' => $agencyMs > 0 ? ($agencyExp / $agencyMs) * 100 : null,
                    ];
                }

                $totals = $this->emptySomTotals();
                foreach ($rows as $row) {
                    $totals['pac_approved'] += $row['pac_approved'];
                    $totals['mother_sanction'] += $row['mother_sanction'];
                    $totals['expenditure'] += $row['expenditure'];
                }
                $totals['pct'] = $totals['mother_sanction'] > 0
                    ? ($totals['expenditure'] / $totals['mother_sanction']) * 100
                    : null;

                $grand['pac_approved'] += $totals['pac_approved'];
                $grand['mother_sanction'] += $totals['mother_sanction'];
                $grand['expenditure'] += $totals['expenditure'];

                $sections[] = [
                    'major_head' => $def['major_head'],
                    'label' => $def['label'],
                    'rows' => $rows,
                    'totals' => $totals,
                ];
            }

            $grand['pct'] = $grand['mother_sanction'] > 0
                ? ($grand['expenditure'] / $grand['mother_sanction']) * 100
                : null;

            return response()->json([
                'success' => true,
                'as_on' => now()->format('d.m.Y'),
                'financial_year' => $financialYear,
                'sections' => $sections,
                'grand_total' => $grand,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching SOM Status-KY report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch SOM Status-KY report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * State wise Fund Allocation, Release and Expenditure Report.
     *
     * Per state:
     * - AAP Allocation = SUM(statewise_aap_allocation.amount)
     * - BE Allocated   = 80% of AAP Allocation
     * - Mother Sanction Amount (Fund released) = net MS for all tranches / BHs of the state
     * - Total Daily Sanction Amount (Expenditure) = SUM(daily_sanction.center_share_amount)
     * - Release % = (MS / AAP) * 100
     * - Expenditure % vs release = (Expenditure / MS) * 100
     * - Expenditure % vs BE = (Expenditure / BE) * 100
     */
    public function getStatewiseFundAllocationReleaseExpenditureReport(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2026-27');
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);

            $aapByState = StatewiseAapAllocation::whereIn('financial_year', $yearVariants)
                ->where('status', 1)
                ->whereNotNull('state_id')
                ->select('state_id', DB::raw('SUM(COALESCE(amount, 0)) as total'))
                ->groupBy('state_id')
                ->pluck('total', 'state_id');

            $msByState = $this->getSomMotherSanctionTotalsByState($yearVariants, $request);
            $expByState = $this->getSomExpenditureTotalsByState($yearVariants, $request);

            $stateIds = collect($aapByState->keys())
                ->merge(array_keys($msByState))
                ->merge(array_keys($expByState))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->filter()
                ->values()
                ->all();

            $states = empty($stateIds)
                ? collect()
                : DB::table('states')
                    ->select('id', 'name')
                    ->whereIn('id', $stateIds)
                    ->orderBy('name')
                    ->get();

            $rows = [];
            $slNo = 1;
            $totals = [
                'be_allocated' => 0.0,
                'aap_allocation' => 0.0,
                'mother_sanction' => 0.0,
                'expenditure' => 0.0,
            ];

            foreach ($states as $state) {
                $stateId = (int) $state->id;
                $aap = floatval($aapByState[$stateId] ?? 0);
                $be = round($aap * 0.80, 5);
                $ms = floatval($msByState[$stateId] ?? 0);
                $exp = floatval($expByState[$stateId] ?? 0);

                $rows[] = [
                    'sl_no' => $slNo++,
                    'state_id' => $stateId,
                    'state_name' => $state->name,
                    'be_allocated' => $be,
                    'aap_allocation' => $aap,
                    'mother_sanction' => $ms,
                    'expenditure' => $exp,
                    'release_pct' => $aap > 0 ? ($ms / $aap) * 100 : null,
                    'exp_vs_release_pct' => $ms > 0 ? ($exp / $ms) * 100 : null,
                    'exp_vs_be_pct' => $be > 0 ? ($exp / $be) * 100 : null,
                ];

                $totals['be_allocated'] += $be;
                $totals['aap_allocation'] += $aap;
                $totals['mother_sanction'] += $ms;
                $totals['expenditure'] += $exp;
            }

            $totals['release_pct'] = $totals['aap_allocation'] > 0
                ? ($totals['mother_sanction'] / $totals['aap_allocation']) * 100
                : null;
            $totals['exp_vs_release_pct'] = $totals['mother_sanction'] > 0
                ? ($totals['expenditure'] / $totals['mother_sanction']) * 100
                : null;
            $totals['exp_vs_be_pct'] = $totals['be_allocated'] > 0
                ? ($totals['expenditure'] / $totals['be_allocated']) * 100
                : null;

            return response()->json([
                'success' => true,
                'financial_year' => $financialYear,
                'rows' => $rows,
                'totals' => $totals,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching State wise Fund Allocation, Release and Expenditure report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch State wise Fund Allocation, Release and Expenditure report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PD-wise Fund Allocation, Release and Expenditure Report for a selected state.
     *
     * Per PD (component) for the state:
     * - AAP Allocation = statewise_aap_allocation.amount
     * - Central Share Released (SNA SPARSH) = sum of all-tranche net Mother Sanction amounts
     * - Exp. against Mother Sanction = sum of Daily Sanction center share linked to those MS
     * - Expenditure (%) = (Exp / Central Share Released) * 100
     */
    public function getPdwiseFundAllocationReleaseExpenditureReport(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2026-27');
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);
            $stateId = (int) $request->get('state_id', 0);

            if ($stateId <= 0) {
                $defaultState = DB::table('states')
                    ->where('name', 'Andhra Pradesh')
                    ->select('id', 'name')
                    ->first();
                if (!$defaultState) {
                    return response()->json([
                        'success' => false,
                        'message' => 'State is required',
                    ], 422);
                }
                $stateId = (int) $defaultState->id;
                $stateName = $defaultState->name;
            } else {
                $state = DB::table('states')->select('id', 'name')->where('id', $stateId)->first();
                if (!$state) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid state selected',
                    ], 422);
                }
                $stateName = $state->name;
            }

            $pdNames = DB::table('md_program_divisions')
                ->pluck('division_name', 'division_id')
                ->mapWithKeys(fn ($name, $id) => [(string) $id => trim((string) $name)]);

            $reportData = [];
            $allowedStatePd = [];
            $stateKey = (string) $stateId;

            // 1. AAP Allocation from statewise_aap_allocation for selected state (SLS summed)
            $allocationRows = $this->getStatePdAllocationTotals($yearVariants, $stateId);

            foreach ($allocationRows as $row) {
                $pdId = (string) $row->pd_id;
                $allowedStatePd[$stateKey . '|' . $pdId] = true;
                $reportData[$stateKey][$pdId] = [
                    'aap_allocation' => floatval($row->amount ?? 0),
                    'release' => 0.0,
                    'expenditure' => 0.0,
                ];
            }

            // Ensure SLS-mapped PDs for this state appear even without AAP rows
            foreach ($this->getStatePdPairsFromSlsComp() as $pair) {
                if ((int) $pair->state_id !== $stateId) {
                    continue;
                }
                $pdId = (string) $pair->pd_id;
                $allowedStatePd[$stateKey . '|' . $pdId] = true;
                if (!isset($reportData[$stateKey][$pdId])) {
                    $reportData[$stateKey][$pdId] = [
                        'aap_allocation' => 0.0,
                        'release' => 0.0,
                        'expenditure' => 0.0,
                    ];
                }
            }

            // 2. Central Share Released = all-tranche net MS for state + PD
            $this->applyPdwiseFundMsReleaseAmounts(
                $reportData,
                $allowedStatePd,
                $stateId,
                $yearVariants,
                $request
            );

            // 3. Exp. against Mother Sanction = DS linked to MS for state + PD
            $this->applyStateWiseExpenditureAmounts(
                $reportData,
                $allowedStatePd,
                $yearVariants,
                $request
            );

            $pdIds = array_keys($reportData[$stateKey] ?? []);
            usort($pdIds, function ($a, $b) use ($pdNames) {
                return strcasecmp($pdNames[$a] ?? '', $pdNames[$b] ?? '');
            });

            $rows = [];
            $slNo = 1;
            $totals = [
                'aap_allocation' => 0.0,
                'central_share_released' => 0.0,
                'expenditure' => 0.0,
            ];

            foreach ($pdIds as $pdId) {
                $cell = $reportData[$stateKey][$pdId];
                $aap = floatval($cell['aap_allocation'] ?? 0);
                $release = floatval($cell['release'] ?? 0);
                $exp = floatval($cell['expenditure'] ?? 0);

                $rows[] = [
                    'sl_no' => $slNo++,
                    'pd_id' => (int) $pdId,
                    'component_name' => $pdNames[$pdId] ?? ('PD ' . $pdId),
                    'aap_allocation' => $aap,
                    'central_share_released' => $release,
                    'expenditure' => $exp,
                    'expenditure_pct' => $release > 0 ? ($exp / $release) * 100 : null,
                ];

                $totals['aap_allocation'] += $aap;
                $totals['central_share_released'] += $release;
                $totals['expenditure'] += $exp;
            }

            $totals['expenditure_pct'] = $totals['central_share_released'] > 0
                ? ($totals['expenditure'] / $totals['central_share_released']) * 100
                : null;

            return response()->json([
                'success' => true,
                'financial_year' => $financialYear,
                'state_id' => $stateId,
                'state_name' => $stateName,
                'rows' => $rows,
                'totals' => $totals,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching PD-wise Fund Allocation, Release and Expenditure report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch PD-wise Fund Allocation, Release and Expenditure report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sum all-tranche net mother sanction amounts into release by state|pd.
     *
     * @param  array<string, array<string, array<string, float>>>  $reportData
     * @param  array<string, bool>  $allowedStatePd
     * @param  array<int, string>  $yearVariants
     */
    private function applyPdwiseFundMsReleaseAmounts(
        array &$reportData,
        array &$allowedStatePd,
        int $stateId,
        array $yearVariants,
        Request $request
    ): void {
        $msQuery = DB::table('mother_sanction as ms')
            ->join('md_program_divisions as pd', function ($join) {
                $join->on(
                    DB::raw('TRIM(ms.pd_component) COLLATE utf8mb4_unicode_ci'),
                    '=',
                    DB::raw('TRIM(pd.division_name) COLLATE utf8mb4_unicode_ci')
                );
            })
            ->whereIn('ms.financial_year', $yearVariants)
            ->where('ms.state_id', $stateId)
            ->whereNotNull('ms.pd_component')
            ->whereNotNull('ms.mother_sanction_amount')
            ->whereRaw("UPPER(COALESCE(ms.action_type, '')) <> 'CLOSED'");

        $this->applyDateRangeToQuery($msQuery, $request, 'ms.sanction_date');

        $msRecords = $msQuery->select(
                'ms.id',
                'ms.state_id',
                'ms.mother_sanction_amount',
                'ms.carry_forward_amount',
                'ms.action_type',
                'pd.division_id as pd_id'
            )
            ->get();

        if ($msRecords->isEmpty()) {
            return;
        }

        $creationNetById = $this->msTotals->loadCreationNetAmountsByRecordIdPublic(
            $msRecords->pluck('id')->unique()->filter()->values()->all()
        );

        $stateKey = (string) $stateId;
        foreach ($msRecords as $record) {
            $pdId = (string) $record->pd_id;
            $statePdKey = $stateKey . '|' . $pdId;
            $allowedStatePd[$statePdKey] = true;

            if (!isset($reportData[$stateKey][$pdId])) {
                $reportData[$stateKey][$pdId] = [
                    'aap_allocation' => 0.0,
                    'release' => 0.0,
                    'expenditure' => 0.0,
                ];
            }

            $reportData[$stateKey][$pdId]['release'] += $this->msTotals->netAmountForRecord(
                $record,
                $creationNetById
            );
        }
    }

    /**
     * @return array{pac_approved: float, mother_sanction: float, expenditure: float, pct: ?float}
     */
    private function emptySomTotals(): array
    {
        return [
            'pac_approved' => 0.0,
            'mother_sanction' => 0.0,
            'expenditure' => 0.0,
            'pct' => null,
        ];
    }

    /**
     * Total net mother sanction amounts keyed by state_id.
     *
     * @param  array<int, string>  $yearVariants
     * @return array<int, float>
     */
    private function getSomMotherSanctionTotalsByState(array $yearVariants, Request $request): array
    {
        $msQuery = DB::table('mother_sanction as ms')
            ->whereIn('ms.financial_year', $yearVariants)
            ->whereNotNull('ms.state_id')
            ->whereNotNull('ms.mother_sanction_amount')
            ->whereRaw("UPPER(COALESCE(ms.action_type, '')) <> 'CLOSED'");

        $this->applyDateRangeToQuery($msQuery, $request, 'ms.sanction_date');

        $msRecords = $msQuery->select(
                'ms.id',
                'ms.state_id',
                'ms.mother_sanction_amount',
                'ms.carry_forward_amount',
                'ms.action_type'
            )
            ->get();

        if ($msRecords->isEmpty()) {
            return [];
        }

        $creationNetById = $this->msTotals->loadCreationNetAmountsByRecordIdPublic(
            $msRecords->pluck('id')->unique()->filter()->values()->all()
        );

        $totals = [];
        foreach ($msRecords as $record) {
            $stateId = (int) $record->state_id;
            if (!isset($totals[$stateId])) {
                $totals[$stateId] = 0.0;
            }
            $totals[$stateId] += $this->msTotals->netAmountForRecord($record, $creationNetById);
        }

        return $totals;
    }

    /**
     * Daily sanction expenditure (center share) keyed by state_id.
     *
     * @param  array<int, string>  $yearVariants
     * @return array<int, float>
     */
    private function getSomExpenditureTotalsByState(array $yearVariants, Request $request): array
    {
        $query = DB::table('daily_sanction as ds')
            ->where('ds.status', 1)
            ->whereIn('ds.financial_year', $yearVariants)
            ->whereNotNull('ds.state_id')
            ->whereNotNull('ds.center_share_amount')
            ->where('ds.center_share_amount', '>', 0);

        $this->applyDateRangeToQuery($query, $request, 'ds.ds_date');

        return $query
            ->select('ds.state_id', DB::raw('SUM(COALESCE(ds.center_share_amount, 0)) as total'))
            ->groupBy('ds.state_id')
            ->pluck('total', 'state_id')
            ->map(fn ($v) => floatval($v))
            ->all();
    }

    /**
     * Agency/Others totals by major head (2552 / 2435).
     * Mother Sanction column = TSA amount + LOA amount + Administrative Expenditure amount.
     * Expenditure column = TSA expenditure + LOA amount + Administrative Expenditure amount.
     *
     * @return array<string, array{pac_approved: float, mother_sanction: float, expenditure: float}>
     */
    private function getSomAgencyTotalsByMajorHead(string $financialYear, Request $request): array
    {
        [$startDate, $endDate] = $this->resolveDateOnlyRangeBounds($request, $financialYear);

        $result = [
            '2552' => ['pac_approved' => 0.0, 'mother_sanction' => 0.0, 'expenditure' => 0.0],
            '2435' => ['pac_approved' => 0.0, 'mother_sanction' => 0.0, 'expenditure' => 0.0],
        ];

        $add = function (string $table, string $amountColumn, string $targetKey) use (&$result, $startDate, $endDate) {
            $rows = DB::table($table)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->whereBetween('date', [$startDate, $endDate])
                ->whereNotNull('budget_head')
                ->select(
                    DB::raw('LEFT(TRIM(budget_head), 4) as major_head'),
                    DB::raw("SUM(COALESCE({$amountColumn}, 0)) as total")
                )
                ->groupBy(DB::raw('LEFT(TRIM(budget_head), 4)'))
                ->get();

            foreach ($rows as $row) {
                $mh = (string) $row->major_head;
                if (!isset($result[$mh])) {
                    continue;
                }
                $result[$mh][$targetKey] += floatval($row->total ?? 0);
            }
        };

        $add('agency_release_tsa', 'amount', 'mother_sanction');
        $add('agency_release_tsa', 'expenditure', 'expenditure');
        $add('agency_release_loa', 'amount', 'mother_sanction');
        $add('agency_release_loa', 'amount', 'expenditure');
        $add('agency_release_administrative_expenditure', 'amount', 'mother_sanction');
        $add('agency_release_administrative_expenditure', 'amount', 'expenditure');

        return $result;
    }

    /**
     * Statewise AAP Allocation MIS report from statewise_aap_allocation.
     * PD groups with SLS-name sub-columns (not SLS-1 / SLS-2 labels).
     */
    public function getVwStatewiseAapAllocationReport(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2026-27');
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);

            $allocationRows = StatewiseAapAllocation::query()
                ->whereIn('financial_year', $yearVariants)
                ->where('status', 1)
                ->whereNotNull('state_id')
                ->whereNotNull('pd_id')
                ->get(['state_id', 'pd_id', 'p_sub_id', 'amount', 'order_id']);

            if ($allocationRows->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'financial_year' => $financialYear,
                    'column_groups' => [],
                    'amount_keys' => [],
                    'rows' => [],
                    'totals' => ['final_allocation' => 0.0],
                ]);
            }

            $pdNames = DB::table('md_program_divisions')
                ->pluck('division_name', 'division_id');

            $stateNames = DB::table('states')->pluck('name', 'id');
            $slsNameMap = $this->getSlsHeaderNamesByPdSub();

            $pdSubIds = [];
            foreach ($allocationRows as $row) {
                $pdId = (int) $row->pd_id;
                $pdSubIds[$pdId][(int) $row->p_sub_id] = true;
            }

            $preferredPdOrder = [2, 6, 10, 3, 5, 4, 9, 7, 8, 12, 13, 11];
            $pdIds = array_keys($pdSubIds);
            usort($pdIds, function ($a, $b) use ($preferredPdOrder) {
                $ia = array_search($a, $preferredPdOrder, true);
                $ib = array_search($b, $preferredPdOrder, true);
                $ia = $ia === false ? 1000 + $a : $ia;
                $ib = $ib === false ? 1000 + $b : $ib;
                return $ia <=> $ib;
            });

            $columnGroups = [];
            $amountKeys = [];
            foreach ($pdIds as $pdId) {
                $subIds = array_map('intval', array_keys($pdSubIds[$pdId]));
                sort($subIds);

                $columns = [];
                foreach ($subIds as $pSubId) {
                    $key = 'pd_' . $pdId . '_sub_' . $pSubId;
                    $amountKeys[] = $key;
                    $isSplit = count($subIds) > 1 || $pSubId > 0;
                    $columns[] = [
                        'key' => $key,
                        'p_sub_id' => $pSubId,
                        'label' => $isSplit
                            ? ($slsNameMap[$pdId][$pSubId] ?? ('SLS-' . $pSubId))
                            : 'Final Allocation',
                    ];
                }

                $columnGroups[] = [
                    'key' => 'pd_' . $pdId,
                    'pd_id' => $pdId,
                    'label' => trim((string) ($pdNames[$pdId] ?? ('PD ' . $pdId))),
                    'columns' => $columns,
                ];
            }

            $amountsByState = [];
            $orderByState = [];
            foreach ($allocationRows as $row) {
                $stateId = (int) $row->state_id;
                $key = 'pd_' . (int) $row->pd_id . '_sub_' . (int) $row->p_sub_id;
                if (!isset($amountsByState[$stateId])) {
                    $amountsByState[$stateId] = [];
                }
                $amountsByState[$stateId][$key] = ($amountsByState[$stateId][$key] ?? 0) + floatval($row->amount ?? 0);
                $existingOrder = $orderByState[$stateId] ?? null;
                $rowOrder = (int) ($row->order_id ?? 0);
                if ($existingOrder === null || ($rowOrder > 0 && $rowOrder < $existingOrder)) {
                    $orderByState[$stateId] = $rowOrder > 0 ? $rowOrder : ($existingOrder ?? 9999);
                }
            }

            $stateIds = array_keys($amountsByState);
            usort($stateIds, function ($a, $b) use ($orderByState, $stateNames) {
                $oa = $orderByState[$a] ?? 9999;
                $ob = $orderByState[$b] ?? 9999;
                if ($oa !== $ob) {
                    return $oa <=> $ob;
                }
                return strcasecmp((string) ($stateNames[$a] ?? ''), (string) ($stateNames[$b] ?? ''));
            });

            $totals = array_fill_keys($amountKeys, 0.0);
            $totals['final_allocation'] = 0.0;
            $rows = [];
            $slNo = 1;

            foreach ($stateIds as $stateId) {
                $row = [
                    'sl_no' => $slNo++,
                    'state_id' => $stateId,
                    'state_name' => $stateNames[$stateId] ?? ('State ' . $stateId),
                ];
                $final = 0.0;
                foreach ($amountKeys as $key) {
                    $value = floatval($amountsByState[$stateId][$key] ?? 0);
                    $row[$key] = $value;
                    $totals[$key] += $value;
                    $final += $value;
                }
                $row['final_allocation'] = $final;
                $totals['final_allocation'] += $final;
                $rows[] = $row;
            }

            return response()->json([
                'success' => true,
                'financial_year' => $financialYear,
                'column_groups' => $columnGroups,
                'amount_keys' => $amountKeys,
                'rows' => $rows,
                'totals' => $totals,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching statewise AAP allocation MIS report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Statewise AAP Allocation report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Representative SLS names for MIS column headers, keyed by pd_id then p_sub_id.
     * p_sub_id 0 = unsplit PD (first/only SLS); 1,2,... = SLS index in the same order as the allocation modal.
     *
     * @return array<int, array<int, string>>
     */
    private function getSlsHeaderNamesByPdSub(): array
    {
        $stateNames = DB::table('states')
            ->pluck('name')
            ->map(function ($name) {
                $clean = preg_replace('/\s*\(.*$/', '', (string) $name);
                $clean = trim(str_replace(['[National Capital Territory (NCT)]', 'AND'], ['', 'and'], $clean));
                return $clean;
            })
            ->filter()
            ->sortByDesc(fn ($name) => strlen($name))
            ->values()
            ->all();

        $components = DB::table('pd_and_sls_comp')
            ->where('status', 1)
            ->whereNotNull('pd_id')
            ->where('pd_id', '>', 0)
            ->orderBy('pd_id')
            ->orderBy('state_id')
            ->orderByRaw('CAST(IFNULL(sharing_patter_state, 0) AS UNSIGNED) DESC')
            ->orderBy('id')
            ->get(['pd_id', 'state_id', 'name']);

        $indexNames = [];
        foreach ($components->groupBy(fn ($row) => (int) $row->pd_id . ':' . (int) $row->state_id) as $stateRows) {
            $list = $stateRows->values();
            $pdId = (int) $list->first()->pd_id;
            $count = $list->count();
            foreach ($list as $index => $sls) {
                $name = $this->normalizeSlsHeaderName((string) ($sls->name ?? ''), $stateNames);
                if ($name === '') {
                    continue;
                }
                $pSubId = $count === 1 ? 0 : ($index + 1);
                $indexNames[$pdId][$pSubId][] = $name;
                if ($count > 1 && $index === 0) {
                    $indexNames[$pdId][0][] = $name;
                }
            }
        }

        $result = [];
        foreach ($indexNames as $pdId => $bySub) {
            foreach ($bySub as $pSubId => $names) {
                $counts = array_count_values($names);
                arsort($counts);
                $result[$pdId][$pSubId] = (string) array_key_first($counts);
            }
        }

        return $result;
    }

    /**
     * Drop state-specific wording so a single SLS column header works across states.
     */
    private function normalizeSlsHeaderName(string $name, array $stateNames): string
    {
        $clean = trim($name);
        if ($clean === '') {
            return '';
        }

        foreach ($stateNames as $stateName) {
            if ($stateName === '') {
                continue;
            }
            $clean = preg_replace('/\b' . preg_quote($stateName, '/') . '\b/i', ' ', $clean) ?? $clean;
        }

        $clean = preg_replace('/\[[^\]]*\]/', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\b(UT|MN|AP|AS|BR|CG|GJ|HR|HP|JH|KA|KL|MH|MP|OD|PB|RJ|TN|TS|UP|UK|WB)\b/i', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;
        $clean = trim($clean, " \t\n\r\0\x0B-–,/");

        return $clean;
    }
}
