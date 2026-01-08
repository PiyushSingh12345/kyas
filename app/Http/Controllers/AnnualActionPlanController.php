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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB as DBFacade;

class AnnualActionPlanController extends Controller
{
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

            DB::beginTransaction();

            try {
                // Process each allocation - update if exists, insert if new
                foreach ($request->allocations as $allocation) {
                    // Check if record exists for the same bh_id, financial_year, and pd_id
                    $existingRecord = PdwiseAapAllocation::where([
                        'bh_id' => $allocation['bh_id'],
                        'financial_year' => $allocation['financial_year'],
                        'pd_id' => $allocation['pd_id']
                    ])->first();

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
     * Get existing PD-wise AAP allocation data
     */
    public function getPdwiseAllocation(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2025-26');

            $allocations = PdwiseAapAllocation::where('financial_year', $financialYear)
                ->get()
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
            $remarks = PdwiseAapAllocation::where('financial_year', $financialYear)
                ->whereNotNull('remark')
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
    public function getBudgetHeads(): JsonResponse
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

            // Use DB facade directly to avoid any model issues
            $budgetHeads = DBFacade::table('budget_heads')
                ->select('id as bh_id', 'budget as budget_code', 'description as budget_name')
                ->where('status', 1)
                ->orderBy('budget')
                ->get();

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
     * Get mother sanction release data grouped by budget head and program division
     */
    public function getMotherSanctionReleaseData(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2025-26');
            
            // First, get all mother sanctions with their budget heads and pd_components
            // Use inner joins to ensure we only get records where both budget_head and pd_component match
            $releaseData = DB::table('mother_sanction as ms')
                ->join('budget_heads as bh', function($join) {
                    $join->on(DB::raw('TRIM(ms.budget_head)'), '=', DB::raw('TRIM(bh.budget)'));
                })
                ->join('md_program_divisions as pd', function($join) {
                    $join->on(DB::raw('TRIM(ms.pd_component) COLLATE utf8mb4_unicode_ci'), '=', DB::raw('TRIM(pd.division_name) COLLATE utf8mb4_unicode_ci'));
                })
                ->where('ms.status', 1)
                ->whereNotNull('ms.budget_head')
                ->whereNotNull('ms.pd_component')
                ->whereNotNull('ms.mother_sanction_amount')
                ->where('ms.mother_sanction_amount', '>', 0)
                ->select(
                    'bh.id as bh_id',
                    'pd.division_id as pd_id',
                    'ms.budget_head',
                    'ms.pd_component',
                    DB::raw('SUM(COALESCE(ms.mother_sanction_amount, 0)) as total_release')
                )
                ->groupBy('bh.id', 'pd.division_id', 'ms.budget_head', 'ms.pd_component')
                ->get();

            Log::info('Mother Sanction Release Data Query Result', [
                'count' => $releaseData->count(),
                'sample' => $releaseData->take(5)->toArray()
            ]);

            // Format data as {bh_id: {pd_id: amount}}
            // Use string keys to match JavaScript object key behavior
            $formattedData = [];
            foreach ($releaseData as $record) {
                // Only include records where both bh_id and pd_id are not null
                if ($record->bh_id && $record->pd_id) {
                    $bhId = (string)$record->bh_id;
                    $pdId = (string)$record->pd_id;
                    
                    if (!isset($formattedData[$bhId])) {
                        $formattedData[$bhId] = [];
                    }
                    $amount = floatval($record->total_release ?? 0);
                    if (isset($formattedData[$bhId][$pdId])) {
                        $formattedData[$bhId][$pdId] += $amount;
                    } else {
                        $formattedData[$bhId][$pdId] = $amount;
                    }
                }
            }

            Log::info('Formatted Mother Sanction Release Data', [
                'total_budget_heads' => count($formattedData),
                'sample' => array_slice($formattedData, 0, 3, true)
            ]);

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'debug' => [
                    'raw_count' => $releaseData->count(),
                    'formatted_count' => count($formattedData)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching mother sanction release data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch mother sanction release data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get daily sanction expenditure data grouped by budget head and program division
     */
    public function getDailySanctionExpenditureData(Request $request): JsonResponse
    {
        try {
            $financialYear = $request->get('financial_year', '2025-26');
            
            // Aggregate daily sanction expenditure data by budget_head and pd_id (through slsComponent -> pd_component)
            // Use inner joins to ensure we only get records where all joins match
            $expenditureData = DB::table('daily_sanction as ds')
                ->join('pd_and_sls_comp as psc', function($join) {
                    $join->on(DB::raw('TRIM(ds.sls_name)'), '=', DB::raw('TRIM(psc.name)'));
                })
                ->join('md_program_divisions as pd', function($join) {
                    $join->on(DB::raw('TRIM(psc.slsPD) COLLATE utf8mb4_unicode_ci'), '=', DB::raw('TRIM(pd.division_name) COLLATE utf8mb4_unicode_ci'));
                })
                ->join('budget_heads as bh', function($join) {
                    $join->on(DB::raw('TRIM(ds.budget_head)'), '=', DB::raw('TRIM(bh.budget)'));
                })
                ->where('ds.status', 1)
                ->whereNotNull('ds.budget_head')
                ->whereNotNull('ds.center_share_amount')
                ->where('ds.center_share_amount', '>', 0)
                ->select(
                    'bh.id as bh_id',
                    'pd.division_id as pd_id',
                    'ds.budget_head',
                    'psc.slsPD',
                    DB::raw('SUM(COALESCE(ds.center_share_amount, 0)) as total_expenditure')
                )
                ->groupBy('bh.id', 'pd.division_id', 'ds.budget_head', 'psc.slsPD')
                ->get();

            Log::info('Daily Sanction Expenditure Data Query Result', [
                'count' => $expenditureData->count(),
                'sample' => $expenditureData->take(5)->toArray()
            ]);

            // Format data as {bh_id: {pd_id: amount}}
            // Use string keys to match JavaScript object key behavior
            $formattedData = [];
            foreach ($expenditureData as $record) {
                // Only include records where both bh_id and pd_id are not null
                if ($record->bh_id && $record->pd_id) {
                    $bhId = (string)$record->bh_id;
                    $pdId = (string)$record->pd_id;
                    
                    if (!isset($formattedData[$bhId])) {
                        $formattedData[$bhId] = [];
                    }
                    $amount = floatval($record->total_expenditure ?? 0);
                    if (isset($formattedData[$bhId][$pdId])) {
                        $formattedData[$bhId][$pdId] += $amount;
                    } else {
                        $formattedData[$bhId][$pdId] = $amount;
                    }
                }
            }

            Log::info('Formatted Daily Sanction Expenditure Data', [
                'total_budget_heads' => count($formattedData),
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
}
