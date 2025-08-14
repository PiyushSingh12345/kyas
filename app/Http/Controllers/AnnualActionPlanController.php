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
                    return $stateAllocations->keyBy('pd_id');
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
                ->select('id', 'name', 'component', 'slsPD')
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
                'data' => 'required|array',
                'data.*.sls_id' => 'required|integer|exists:pd_and_sls_comp,id',
                'amounts' => 'required|array',
                'amounts.*.budget_head_id' => 'required',
                'amounts.*.amount' => 'required|numeric|min:0'
            ]);

            // Log the incoming data for debugging
            Log::info('State Release Data Submission', [
                'state_id' => $validated['state_id'],
                'fy' => $validated['fy'],
                'data_count' => count($validated['data']),
                'amounts_count' => count($validated['amounts']),
                'sample_data' => $validated['data'][0] ?? null,
                'sample_amounts' => array_slice($validated['amounts'], 0, 5)
            ]);

            DB::beginTransaction();

            $savedCount = 0;
            $errors = [];

            // Get all budget heads from state_release_generic for mapping (only as reference)
            $genericBudgetHeads = DB::table('state_release_generic')
                ->select('id', 'allocation_name')
                ->where('status', 1)
                ->get()
                ->keyBy('allocation_name');

            Log::info('Generic Budget Heads Loaded', [
                'count' => $genericBudgetHeads->count(),
                'sample' => $genericBudgetHeads->take(5)->toArray()
            ]);

            // Process each row (SLS component)
            foreach ($validated['data'] as $rowIndex => $row) {
                // Calculate how many amounts per row (total amounts / number of rows)
                $amountsPerRow = count($validated['amounts']) / count($validated['data']);
                $startIndex = $rowIndex * $amountsPerRow;
                
                Log::info("Processing Row {$rowIndex}", [
                    'sls_id' => $row['sls_id'],
                    'amounts_per_row' => $amountsPerRow,
                    'start_index' => $startIndex
                ]);
                
                // Get amounts for this specific row
                for ($i = 0; $i < $amountsPerRow; $i++) {
                    $amountIndex = $startIndex + $i;
                    $amountData = $validated['amounts'][$amountIndex];
                    
                    try {
                        // Determine budget head ID and flag based on the budget_head_id sent from frontend
                        $budgetHeadId = null;
                        $flag = 0;
                        
                        // Check if this budget head ID exists in state_release_generic table
                        $existingGenericBudgetHead = DB::table('state_release_generic')
                            ->where('id', $amountData['budget_head_id'])
                            ->where('status', 1)
                            ->first();
                        
                        if ($existingGenericBudgetHead) {
                            // This is a generic budget head (Total Allocation, State Share, Center Share)
                            $budgetHeadId = $amountData['budget_head_id'];
                            $flag = 0; // From state_release_generic table
                        } else {
                            // Check if this budget head ID exists in budget_heads table
                            $budgetHeadExists = DB::table('budget_heads')
                                ->where('id', $amountData['budget_head_id'])
                                ->where('status', 1)
                                ->exists();
                            
                            if ($budgetHeadExists) {
                                $budgetHeadId = $amountData['budget_head_id'];
                                $flag = 1; // From budget_heads table
                            } else {
                                $errors[] = "Budget head ID {$amountData['budget_head_id']} not found in either state_release_generic or budget_heads table for column index {$i} in row {$rowIndex}";
                                continue;
                            }
                        }

                        Log::info("Column {$i} Processing", [
                            'column_index' => $i,
                            'budget_head_id' => $budgetHeadId,
                            'flag' => $flag,
                            'amount' => $amountData['amount']
                        ]);

                        if (!$budgetHeadId) {
                            $errors[] = "Budget head not found for column index {$i} in row {$rowIndex}";
                            continue;
                        }

                        // Check if record already exists
                        $existingRecord = DB::table('state_release_data')
                            ->where('state_id', $validated['state_id'])
                            ->where('fy', $validated['fy'])
                            ->where('SLS_id', $row['sls_id'])
                            ->where('budget_head_id', $budgetHeadId)
                            ->first();

                        if ($existingRecord) {
                            // Update existing record
                            DB::table('state_release_data')
                                ->where('id', $existingRecord->id)
                                ->update([
                                    'amount' => $amountData['amount'],
                                    'flag' => $flag,
                                    'updated_at' => now()
                                ]);
                        } else {
                            // Create new record
                            DB::table('state_release_data')->insert([
                                'fy' => $validated['fy'],
                                'state_id' => $validated['state_id'],
                                'SLS_id' => $row['sls_id'],
                                'budget_head_id' => $budgetHeadId,
                                'amount' => $amountData['amount'],
                                'flag' => $flag,
                                'isactive' => 1,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                        $savedCount++;
                        
                    } catch (\Exception $e) {
                        $errors[] = "Error processing row {$rowIndex}, amount {$i}: " . $e->getMessage();
                        Log::error("Error processing amount", [
                            'row_index' => $rowIndex,
                            'amount_index' => $i,
                            'error' => $e->getMessage()
                        ]);
                    }
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
                    return $bhAllocations->keyBy('pd_id');
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
}
