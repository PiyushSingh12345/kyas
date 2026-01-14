<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\BudgetHead;
use App\Models\SlsPDComponent;
use App\Models\FundAllocation;
use App\Models\MotherSanction;
use App\Models\MotherSanctionHistory;
use App\Models\BudgetPhase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

use Inertia\Inertia;

class MotherSanctionController extends Controller
{
    public function getBudgetHeads()
    {
        return response()->json(
            BudgetHead::where('status', 1)->select('id', 'budget')->get()
        );
    }

    public function getSlsData($stateId)
    {
        $slsData = SlsPDComponent::where('state_id', $stateId)
                                 ->select('id', 'name')
                                 ->get();

        return response()->json($slsData);
    }

    public function getBudgetDetails($id)
    {
        $budgetHead = BudgetHead::with(['budgetPhases' => function($query) {
            $query->where('status', 1);
        }])->find($id);

        if (!$budgetHead) {
            return response()->json(['message' => 'Budget Head not found'], 404);
        }

        return response()->json([
            'category' => $budgetHead->category,
            'available_amount' => $budgetHead->budgetPhases->sum('budget_amount'),
        ]);
    }

    public function getFundAllocationData($slsId, $stateId)
    {
        //get the data from pd_and_sls_comp table and joinded with md_program_divisions, budget_heads, pdwise_aap_allocation
        $data = DB::table('pd_and_sls_comp as pd')
        ->join('md_program_divisions as md', function($join) {
            $join->on(DB::raw('pd.slsPD COLLATE utf8mb4_unicode_ci'), '=', DB::raw('md.division_name COLLATE utf8mb4_unicode_ci'));
        })
        ->join('pdwise_aap_allocation as pda', 'md.division_id', '=', 'pda.pd_id')
        ->join('budget_heads as bh', 'pda.bh_id', '=', 'bh.id')
        ->where('pd.state_id', $stateId)
        ->where('pd.name', $slsId)
        ->select('bh.budget as budget', 'pd.slsPD as slsPD', 'pda.amount', 'bh.category')
        ->get();

        return response()->json($data);
    }

    public function getFundAllocationByBudgetHead(Request $request)
    {
        $budget = $request->query('budget');
        $slsId = $request->query('sls_id');
        $stateId = $request->query('state_id');

        //get the data from pdwise_aap_allocation table and join with budget_heads table, md_program_divisions table, and pd_and_sls_comp table
        $data = DB::table('pdwise_aap_allocation as pda')
        ->join('budget_heads as bh', 'pda.bh_id', '=', 'bh.id')
        ->join('md_program_divisions as md', 'pda.pd_id', '=', 'md.division_id')
        ->join('pd_and_sls_comp as psc', function($join) {
            $join->on(DB::raw('md.division_name COLLATE utf8mb4_unicode_ci'), '=', DB::raw('psc.slsPD COLLATE utf8mb4_unicode_ci'));
        })
        ->where('bh.budget', $budget)
        ->where('psc.name', $slsId)
        ->where('psc.state_id', $stateId)
        ->select('bh.budget as budget', 'pda.amount', 'bh.category')
        ->get();

        if (!$data) {
            return response()->json(['message' => 'No matching record found.'], 404);
        }

        return response()->json($data);
    }

    /**
     * Get sum of mother_sanction_amount for a given budget_head and pd_component
     */
    public function getMotherSanctionReleasedAmount(Request $request)
    {
        try {
            $budgetHead = $request->query('budget_head');
            $pdComponent = $request->query('pd_component');

            if (!$budgetHead || !$pdComponent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Budget head and PD component are required',
                    'total_released' => 0
                ], 400);
            }

            // Sum all mother_sanction_amount from mother_sanction table
            // where budget_head and pd_component match
            $totalReleased = DB::table('mother_sanction')
                ->whereRaw('TRIM(budget_head) = TRIM(?)', [$budgetHead])
                ->whereRaw('TRIM(pd_component) COLLATE utf8mb4_unicode_ci = TRIM(?) COLLATE utf8mb4_unicode_ci', [$pdComponent])
                ->where('status', 1)
                ->whereNotNull('mother_sanction_amount')
                ->sum('mother_sanction_amount');

            return response()->json([
                'success' => true,
                'total_released' => floatval($totalReleased ?? 0)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching mother sanction released amount: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch released amount',
                'total_released' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }

   
public function list()
{
    try {
        // Get all records with relations and join with pd_and_sls_comp table
        $data = DB::table('mother_sanction as ms')
            ->select([
                'ms.*',
                's.name as state_name',
                'pdc.sls_code'
            ])
            ->join('states as s', 'ms.state_id', '=', 's.id')
            ->leftJoin('pd_and_sls_comp as pdc', function($join) {
                $join->on('ms.sls_name', '=', 'pdc.name')
                     ->on('ms.pd_component', '=', 'pdc.slsPD');
            })
            // Show all records regardless of status
            ->orderBy('ms.created_at', 'desc')
            ->get();

        // Group data by state_id and sls_code to get all budget heads
        $groupedData = $data->groupBy(function($item) {
            return ($item->state_id ?? '') . '|' . ($item->sls_code ?? '');
        });

        // Transform the grouped data
        $transformedData = $groupedData->map(function($group) {
            $firstItem = $group->first();
            
            // Get all unique budget heads for this group and aggregate their values
            $budgetHeadMap = [];
            
            // Collect all budget heads and aggregate amounts
            foreach ($group as $item) {
                if (empty($item->budget_head)) {
                    continue;
                }
                
                $budgetKey = $item->budget_head;
                
                if (!isset($budgetHeadMap[$budgetKey])) {
                    $budgetHeadMap[$budgetKey] = [
                        'budget_head' => $item->budget_head,
                        'category' => $item->category,
                        'available_fund' => 0,
                        'mother_sanction_amount' => 0,
                        'expenditure' => 0,
                        'carry_forward_amount' => 0,
                    ];
                }
                
                // Aggregate amounts
                $budgetHeadMap[$budgetKey]['available_fund'] += floatval($item->available_fund ?? 0);
                $budgetHeadMap[$budgetKey]['mother_sanction_amount'] += floatval($item->mother_sanction_amount ?? 0);
                $budgetHeadMap[$budgetKey]['carry_forward_amount'] += floatval($item->carry_forward_amount ?? 0);
            }
            
            // Calculate expenditure for each budget head across all mother sanctions in this group
            $stateId = $firstItem->state_id;
            $slsCode = $firstItem->sls_code;
            
            // Get all ky_ms_no values for this state + sls_code combination
            $kyMsNos = $group->pluck('ky_ms_no')->unique()->filter()->values();
            
            $budgetHeads = collect($budgetHeadMap)->map(function($budgetData) use ($kyMsNos, $stateId) {
                // Calculate expenditure across all mother sanctions for this budget head
                $expenditure = DB::table('daily_sanction')
                    ->whereIn('mother_sanction', $kyMsNos->toArray())
                    ->where('budget_head', $budgetData['budget_head'])
                    ->where('state_id', $stateId)
                    ->sum('center_share_amount');
                
                $budgetData['expenditure'] = floatval($expenditure ?? 0);
                
                return $budgetData;
            })->values();

            // Calculate totals
            $totalAmount = $group->sum('mother_sanction_amount');
            $totalAvailableFund = $group->sum('available_fund');
            
            // Get all unique ky_ms_no values for display
            $allKyMsNos = $kyMsNos->toArray();
            $kyMsNoDisplay = !empty($allKyMsNos) ? implode(', ', $allKyMsNos) : ($firstItem->ky_ms_no ?? '');
            
            // Determine status: if all records are inactive, show inactive; otherwise show active
            $allStatuses = $group->pluck('status')->unique();
            $isActive = $allStatuses->contains(1);

            return [
                'id' => $firstItem->id,
                'financial_year' => $firstItem->financial_year,
                'state_id' => $firstItem->state_id,
                'ms_sequence_no' => $firstItem->ms_sequence_no,
                'file_no' => $firstItem->file_no,
                'ifd_no' => $firstItem->ifd_no,
                'sanction_date' => $firstItem->sanction_date,
                'ky_ms_no' => $kyMsNoDisplay, // Display all ky_ms_no values
                'ky_ms_no_list' => $allKyMsNos, // Array of all ky_ms_no for programmatic access
                'sls_name' => $firstItem->sls_name,
                'pd_component' => $firstItem->pd_component,
                'total_mother_sanction_amount' => $totalAmount,
                'total_available_fund' => $totalAvailableFund,
                'budget_heads' => $budgetHeads,
                'uc_received_from_State' => $firstItem->uc_received_from_State,
                'signed_copy_of_mother_sanction' => $firstItem->signed_copy_of_mother_sanction,
                'last_id' => $firstItem->last_id,
                'status' => $isActive ? 'active' : 'inactive',
                'created_at' => $firstItem->created_at,
                'updated_at' => $firstItem->updated_at,
                'state' => [
                    'id' => $firstItem->state_id,
                    'name' => $firstItem->state_name
                ],
                'sls_code' => $firstItem->sls_code
            ];
        })->values();

        // Log some debug information
        Log::info('MotherSanction list query executed', [
            'total_records' => $transformedData->count(),
            'sample_record' => $transformedData->first() ? [
                'ky_ms_no' => $transformedData->first()['ky_ms_no'],
                'sls_name' => $transformedData->first()['sls_name'],
                'pd_component' => $transformedData->first()['pd_component'],
                'sls_code' => $transformedData->first()['sls_code'],
                'budget_heads_count' => count($transformedData->first()['budget_heads'])
            ] : null
        ]);

        return response()->json($transformedData);
    } catch (\Exception $e) {
        Log::error('Error in MotherSanction list method', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'error' => 'An error occurred while fetching data',
            'message' => $e->getMessage()
        ], 500);
    }
}

public function listReport(Request $request)
{
    $query = MotherSanction::with('state')
        ->where('status', 1)
        ->orderBy('created_at', 'desc');

    // Filtering
    if ($request->filled('year')) {
        $query->where('financial_year', $request->year);
    }
    if ($request->filled('program_division')) {
        $query->where('pd_component', $request->program_division);
    }
    if ($request->filled('state_id')) {
        $query->where('state_id', $request->state_id);
    }
    if ($request->filled('sanction_date')) {
        $query->where('sanction_date', $request->sanction_date);
    }

    $data = $query->get();

    return response()->json($data);
}

// public function listReport(Request $request)
//     {
//         // Get latest record per group of `last_id`
//         $subQuery = DB::table('mother_sanction')
//             ->select(DB::raw('MAX(id) as id'))
//             ->groupBy('last_id');

//         $query = MotherSanction::with('state')
//             ->whereIn('id', $subQuery)
//             ->orderBy('created_at', 'desc');

//         // Filtering
//         if ($request->filled('year')) {
//             $query->where('financial_year', $request->year);
//         }
//         if ($request->filled('state_id')) {
//             $query->where('state_id', $request->state_id);
//         }
//         if ($request->filled('sanction_date')) {
//             $query->where('sanction_date', $request->sanction_date);
//         }
//         $query->where('status', 1);
//         // Program Division filter using join with pd_and_sls_comp
//         if ($request->filled('program_division')) {
//             $programDivisionId = $request->program_division;
//             $query->whereExists(function($q) use ($programDivisionId) {
//                 $q->select(DB::raw(1))
//                     ->from('pd_and_sls_comp as pd')
//                     ->whereColumn('pd.name', 'mother_sanction.pd_component')
//                     ->where('pd.component', 'PD')
//                     ->where('pd.id', $programDivisionId);
//             });
//         }

//         $data = $query->get();

//         return response()->json($data);
//     }

    public function addMotherSanction(Request $request)
{
    // Validate the request data
    $validated = $request->validate([
        'financial_year' => 'required|string',
        'state_id' => 'required|integer',
        'ms_sequence_no' => 'required|string',
        // 'file_no' => 'required|string',
        'ifd_no' => 'required|string',
        'sanction_date' => 'required|date',
        'ky_ms_no' => 'required|string',
        'sls_name' => 'required|string',
        'pd_component' => 'required|string',
        'total_mother_sanction_amount' => 'required|numeric|min:0',
        'reappropriations' => 'required|json',
        'status' => 'required|in:0,1',
        'remark' => 'nullable|string',
    ]);

    try {
        // Debug: Log all request data
        Log::info('MotherSanction Request Data:', $request->all());
        Log::info('Files received:', $request->allFiles());

        $ucFilePath = '';
        $signedCopyPath = '';

        if ($request->hasFile('uc_file_path')) {
            $ucFilePath = $request->file('uc_file_path')->store('mother_sanction', 'public');
            Log::info('UC File stored at:', ['path' => $ucFilePath]);
        } else {
            Log::info('No UC file received');
        }

        if ($request->hasFile('signed_copy_path')) {
            $signedCopyPath = $request->file('signed_copy_path')->store('mother_sanction', 'public');
            Log::info('Signed Copy stored at:', ['path' => $signedCopyPath]);
        } else {
            Log::info('No Signed Copy file received');
        }

        $commonData = [
            'financial_year' => $request->financial_year,
            'state_id' => $request->state_id,
            'ms_sequence_no' => $request->ms_sequence_no,
            // 'file_no' => $request->file_no,
            'remark' => $request->remark,
            'ifd_no' => $request->ifd_no,
            'sanction_date' => $request->sanction_date,
            'ky_ms_no' => $request->ky_ms_no,
            'sls_name' => $request->sls_name,
            'pd_component' => $request->pd_component,
            'total_mother_sanction_amount' => $request->total_mother_sanction_amount,
            'uc_received_from_State' => $ucFilePath,
            'signed_copy_of_mother_sanction' => $signedCopyPath,
            'status' => $request->status,
            'last_id'=> rand(10, 99)
        ];

        Log::info('Common data to be inserted:', $commonData);

        $reappropriations = json_decode($request->reappropriations, true);

        if (!is_array($reappropriations) || empty($reappropriations)) {
            return response()->json([
                'message' => 'Invalid reappropriations data',
                'errors' => ['reappropriations' => ['Reappropriations data is required and must be valid.']]
            ], 422);
        }

        $lastInserted = null;

        foreach ($reappropriations as $row) {
            $sanction = MotherSanction::create(array_merge($commonData, [
                'budget_head' => $row['budget_head'],
                'category' => $row['category'],
                'available_fund' => $row['available_amount'],
                'mother_sanction_amount' => $row['sanction_amount'],
                'carry_forward_amount' => $row['carry_forward'] ?? 0,
            ]));

            // Save history for creation
            $this->saveHistory($sanction, 'CREATE', 'New mother sanction record created');

            $lastInserted = $sanction; // Keep reference to the last inserted record
        }

        // Update the last inserted record with its own ID in 'last_id'
        /*if ($lastInserted) {
            $lastInserted->update([
                'last_id' => $lastInserted->id
            ]);
        }*/

        return response()->json([
            'message' => 'Data saved successfully',
            'last_id' => $lastInserted ? $lastInserted: null
        ]);
    } catch (\Exception $e) {
        Log::error('Error saving mother sanction:', ['error' => $e->getMessage()]);
        
        return response()->json([
            'message' => 'An error occurred while saving the data',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function motherSanctionData(Request $req){
      $query = MotherSanction::query();

    if ($req->filled('year')) {
        $query->where('financial_year', $req->year);
    }
    if ($req->filled('state_id')) {
        $query->where('state_id', $req->state_id);
    }
    if ($req->filled('sanction_date')) {
        $query->where('sanction_date', $req->sanction_date);
    }
    if ($req->filled('ky_ms_no')) {
        $query->where('ky_ms_no', $req->ky_ms_no);
    }

    $data = $query->orderBy('sanction_date')->get();

    return response()->json($data);

}

public function updateStatus(Request $request)
{
    try {
        // Accept either a single ky_ms_no (string) or an array of ky_ms_no values
        $request->validate([
            'ky_ms_no' => 'required', // Can be string or array
            // Added "close" as a valid action for full closure via Close button
            // Added "revise" as a valid action to add Available Fund to MS Amount
            'action' => 'required|in:deactivate,activate,close,revise'
        ]);

        $kyMsNoInput = $request->input('ky_ms_no');
        $action = $request->input('action');

        // Normalize to array: if string, convert to array; if already array, use as is
        $kyMsNos = is_array($kyMsNoInput) ? $kyMsNoInput : [$kyMsNoInput];
        
        // Filter out empty values
        $kyMsNos = array_filter($kyMsNos, function($value) {
            return !empty($value);
        });

        if (empty($kyMsNos)) {
            return response()->json([
                'message' => 'No valid KY MS No. provided.',
                'success' => false
            ], 400);
        }

        // Find all records with any of the provided ky_ms_no values
        $records = MotherSanction::whereIn('ky_ms_no', $kyMsNos)->get();

        if ($records->isEmpty()) {
            return response()->json([
                'message' => 'No records found with the given KY MS No(s).',
                'success' => false
            ], 404);
        }

        if ($action === 'deactivate') {
            // Deactivate all records with the provided ky_ms_no values (used by status toggle)
            DB::beginTransaction();
            
            $records = MotherSanction::whereIn('ky_ms_no', $kyMsNos)->get();
            
            foreach ($records as $record) {
                // Save history before update
                $this->saveHistory($record, 'DEACTIVATE', 'Record deactivated');
                
                $record->status = 0;
                $record->save();
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Records deactivated successfully',
                'success' => true,
                'updated_count' => $records->count()
            ]);
        } elseif ($action === 'activate') {
            // Activate all records with the provided ky_ms_no values
            DB::beginTransaction();
            
            $records = MotherSanction::whereIn('ky_ms_no', $kyMsNos)->get();
            
            foreach ($records as $record) {
                // Save history before update
                $this->saveHistory($record, 'ACTIVATE', 'Record activated');
                
                $record->status = 1;
                $record->save();
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Records activated successfully',
                'success' => true,
                'updated_count' => $records->count()
            ]);
        } elseif ($action === 'revise') {
            // "Revise" action triggered from Revise button:
            //  - Set old data status to inactive
            //  - MS Amount = Current MS Amount + Available Fund (where Available Fund = MS Amount - Expenditure)
            //  - Available Fund = New MS Amount - Expenditure

            DB::beginTransaction();

            foreach ($records as $record) {
                // Calculate expenditure for this budget head exactly like in list()
                $expenditure = DB::table('daily_sanction')
                    ->where('mother_sanction', $record->ky_ms_no)
                    ->where('budget_head', $record->budget_head)
                    ->where('state_id', $record->state_id)
                    ->sum('center_share_amount');

                // Ensure numeric
                $expenditure = $expenditure ?: 0;

                // Get current MS Amount
                $currentMsAmount = floatval($record->mother_sanction_amount ?: 0);
                $oldAvailableFund = floatval($record->available_fund ?: 0);

                // Calculate current Available Fund as MS Amount - Expenditure (matching frontend calculation)
                $currentAvailableFund = $currentMsAmount - $expenditure;

                // Store the current Available Fund as Carry Forward Amount (this is what was added)
                $carryForwardAmount = $currentAvailableFund;

                // New MS Amount = Current MS Amount + Available Fund
                $newMsAmount = $currentMsAmount + $currentAvailableFund;

                // New Available Fund = New MS Amount - Expenditure
                $newAvailableFund = $newMsAmount - $expenditure;

                // Save history before update
                $this->saveHistory($record, 'REVISE', 
                    "Record revised. MS Amount: {$currentMsAmount} -> {$newMsAmount}, Available Fund: {$oldAvailableFund} -> {$newAvailableFund}",
                    $currentMsAmount, $newMsAmount, $oldAvailableFund, $newAvailableFund
                );

                // Set status to inactive for revise
                $record->status = 0;
                $record->mother_sanction_amount = $newMsAmount;
                $record->available_fund = $newAvailableFund;
                $record->carry_forward_amount = $carryForwardAmount;

                $record->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Records revised successfully. MS Amount has been updated and Available Fund recalculated.',
                'success' => true,
                'updated_count' => $records->count()
            ]);
        } else {
            // "Close" action triggered from Close button:
            //  - MS Amount should become equal to Expenditure
            //  - Available Fund should be added back to BE budget phase amount
            //  - Record is marked inactive/closed

            DB::beginTransaction();

            $financialYear = $request->input('financial_year');

            foreach ($records as $record) {
                // Calculate expenditure for this budget head exactly like in list()
                $expenditure = DB::table('daily_sanction')
                    ->where('mother_sanction', $record->ky_ms_no)
                    ->where('budget_head', $record->budget_head)
                    ->where('state_id', $record->state_id)
                    ->sum('center_share_amount');

                // Ensure numeric
                $expenditure = $expenditure ?: 0;

                $oldMsAmount = floatval($record->mother_sanction_amount ?: 0);
                $oldAvailableFund = floatval($record->available_fund ?: 0);

                // Get available fund to add back to BE
                $availableFundToReturn = $oldAvailableFund;

                // Find BudgetHead by budget string
                $budgetHead = BudgetHead::where('budget', $record->budget_head)->first();
                
                if ($budgetHead && $availableFundToReturn > 0) {
                    // Find or create BE budget phase for this budget head and financial year
                    $budgetPhase = BudgetPhase::where('budget_head_id', $budgetHead->id)
                        ->where('budget_phase', 'BE')
                        ->where('financial_year', $financialYear ?: $record->financial_year)
                        ->where('status', 1)
                        ->first();
                    
                    if ($budgetPhase) {
                        // Add back the available amount to BE budget phase
                        $budgetPhase->budget_amount = floatval($budgetPhase->budget_amount) + $availableFundToReturn;
                        $budgetPhase->save();
                    } else {
                        // Create BE budget phase if it doesn't exist
                        $budgetPhase = BudgetPhase::create([
                            'budget_head_id' => $budgetHead->id,
                            'budget_phase' => 'BE',
                            'financial_year' => $financialYear ?: $record->financial_year,
                            'budget_amount' => $availableFundToReturn,
                            'status' => 1,
                            'draft_flag' => 0
                        ]);
                    }
                }

                // MS Amount should be equivalent to Expenditure
                $record->mother_sanction_amount = $expenditure;

                // Available fund is considered returned to BE, so set to zero here
                $record->available_fund = 0;

                // Mark record as inactive/closed
                $record->status = 0;

                // Save history before update
                $this->saveHistory($record, 'CLOSE', 
                    "Record closed. MS Amount: {$oldMsAmount} -> {$expenditure}, Available Fund: {$oldAvailableFund} -> 0 (returned to BE)",
                    $oldMsAmount, $expenditure, $oldAvailableFund, 0
                );

                $record->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Records closed successfully. MS Amount updated to match expenditure, available fund returned to BE, and status set to close.',
                'success' => true,
                'updated_count' => $records->count()
            ]);
        }

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Error updating mother sanction status:', [
            'error' => $e->getMessage(),
            'request' => $request->all()
        ]);
        
        return response()->json([
            'message' => 'An error occurred while updating status',
            'error' => $e->getMessage(),
            'success' => false
        ], 500);
    }
}

public function getMotherSanctionDetails($kyMsNo)
{
    try {
        // Get all records with the same ky_ms_no
        $records = MotherSanction::where('ky_ms_no', $kyMsNo)
            ->with('state')
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'message' => 'No records found with the given KY MS No.',
                'success' => false
            ], 404);
        }

        $firstRecord = $records->first();
        
        // Get budget heads data
        $budgetHeads = $records->map(function($record) {
            return [
                'budget_head' => $record->budget_head,
                'category' => $record->category,
                'available_fund' => $record->available_fund,
                'mother_sanction_amount' => $record->mother_sanction_amount
            ];
        })->filter(function($item) {
            return !empty($item['budget_head']);
        })->values();

        return response()->json([
            'meta' => [
                'ky_ms_no' => $firstRecord->ky_ms_no,
                'financial_year' => $firstRecord->financial_year,
                'state_id' => $firstRecord->state_id,
                'state_name' => $firstRecord->state->name ?? '',
                'ms_sequence_no' => $firstRecord->ms_sequence_no,
                'sls_name' => $firstRecord->sls_name,
                'pd_component' => $firstRecord->pd_component,
                'ifd_no' => $firstRecord->ifd_no,
                'sanction_date' => $firstRecord->sanction_date,
                'remark' => $firstRecord->remark,
                'total_mother_sanction_amount' => $records->sum('mother_sanction_amount'),
                'total_available_fund' => $records->sum('available_fund')
            ],
            'entries' => $budgetHeads
        ]);

    } catch (\Exception $e) {
        Log::error('Error fetching mother sanction details:', [
            'error' => $e->getMessage(),
            'ky_ms_no' => $kyMsNo
        ]);
        
        return response()->json([
            'message' => 'An error occurred while fetching details',
            'error' => $e->getMessage(),
            'success' => false
        ], 500);
    }
}


    public function timeSeriesReport(Request $request)
    {
        $query = MotherSanction::with('state')
            ->where('status', 1);

        // Apply filters
        if ($request->has('state_id') && $request->state_id) {
            $query->where('state_id', $request->state_id);
        }

        if ($request->has('financial_year') && $request->financial_year) {
            $query->where('financial_year', $request->financial_year);
        }

        if ($request->has('budget_head') && $request->budget_head) {
            $query->where('budget_head', $request->budget_head);
        }

        $data = $query->get();

        // Get unique financial years for columns
        $financialYears = $data->pluck('financial_year')->unique()->sort()->values()->toArray();
        
        // Get unique states
        $states = $data->pluck('state_id')->unique();
        $statesWithNames = DB::table('states')
            ->whereIn('id', $states)
            ->pluck('name', 'id')
            ->toArray();

        // Group by state and budget head
        $grouped = [];
        
        foreach ($states as $stateId) {
            $stateName = $statesWithNames[$stateId] ?? 'Unknown';
            $stateData = $data->where('state_id', $stateId);
            $budgetHeads = $stateData->pluck('budget_head')->unique();
            
            $budgetHeadRows = [];
            
            foreach ($budgetHeads as $budgetHead) {
                $budgetData = $stateData->where('budget_head', $budgetHead);
                $metrics = [];
                
                foreach ($financialYears as $year) {
                    $yearData = $budgetData->where('financial_year', $year);
                    $metrics[$year] = [
                        'mother_sanction_amount' => round($yearData->sum('mother_sanction_amount'), 2),
                        'available_fund' => round($yearData->sum('available_fund'), 2),
                        'total_mother_sanction_amount' => round($yearData->sum('total_mother_sanction_amount'), 2),
                    ];
                }
                
                $budgetHeadRows[] = [
                    'budget_head' => $budgetHead,
                    'metrics' => $metrics,
                ];
            }
            
            // Add total row for the state
            $totalMetrics = [];
            foreach ($financialYears as $year) {
                $yearData = $stateData->where('financial_year', $year);
                $totalMetrics[$year] = [
                    'mother_sanction_amount' => round($yearData->sum('mother_sanction_amount'), 2),
                    'available_fund' => round($yearData->sum('available_fund'), 2),
                    'total_mother_sanction_amount' => round($yearData->sum('total_mother_sanction_amount'), 2),
                ];
            }
            
            $budgetHeadRows[] = [
                'budget_head' => 'Total',
                'metrics' => $totalMetrics,
                'is_total' => true,
            ];
            
            $grouped[] = [
                'state' => $stateName,
                'state_id' => $stateId,
                'items' => $budgetHeadRows,
            ];
        }

        return response()->json([
            'years' => $financialYears,
            'data' => $grouped,
        ]);
    }

    /**
     * Save history record for mother sanction changes
     */
    private function saveHistory($record, $actionType, $description = null, $oldMsAmount = null, $newMsAmount = null, $oldAvailableFund = null, $newAvailableFund = null)
    {
        $changedBy = Auth::check() ? Auth::user()->name : 'System';
        
        MotherSanctionHistory::create([
            'mother_sanction_id' => $record->id,
            'financial_year' => $record->financial_year,
            'state_id' => $record->state_id,
            'ms_sequence_no' => $record->ms_sequence_no,
            'file_no' => $record->file_no,
            'ifd_no' => $record->ifd_no,
            'sanction_date' => $record->sanction_date,
            'ky_ms_no' => $record->ky_ms_no,
            'sls_name' => $record->sls_name,
            'pd_component' => $record->pd_component,
            'total_mother_sanction_amount' => $record->total_mother_sanction_amount,
            'budget_head' => $record->budget_head,
            'category' => $record->category,
            'available_fund' => $record->available_fund,
            'mother_sanction_amount' => $record->mother_sanction_amount,
            'carry_forward_amount' => $record->carry_forward_amount,
            'uc_received_from_State' => $record->uc_received_from_State,
            'signed_copy_of_mother_sanction' => $record->signed_copy_of_mother_sanction,
            'last_id' => $record->last_id,
            'status' => $record->status,
            'remark' => $record->remark,
            'action_type' => $actionType,
            'changed_by' => $changedBy,
            'change_description' => $description,
            'old_mother_sanction_amount' => $oldMsAmount ?? $record->mother_sanction_amount,
            'new_mother_sanction_amount' => $newMsAmount ?? $record->mother_sanction_amount,
            'old_available_fund' => $oldAvailableFund ?? $record->available_fund,
            'new_available_fund' => $newAvailableFund ?? $record->available_fund,
        ]);
    }

    /**
     * Get mother sanction history list
     */
    public function historyList()
    {
        try {
            $history = MotherSanctionHistory::with(['state', 'motherSanction'])
                ->orderBy('history_timestamp', 'desc')
                ->get();

            // Group by state_id and sls_code similar to list method
            $groupedData = $history->groupBy(function($item) {
                return ($item->state_id ?? '') . '|' . ($item->sls_name ?? '');
            });

            $transformedData = $groupedData->map(function($group) {
                $firstItem = $group->first();
                
                // Get all unique budget heads for this group
                $budgetHeadMap = [];
                
                foreach ($group as $item) {
                    if (empty($item->budget_head)) {
                        continue;
                    }
                    
                    $budgetKey = $item->budget_head;
                    
                    if (!isset($budgetHeadMap[$budgetKey])) {
                        $budgetHeadMap[$budgetKey] = [
                            'budget_head' => $item->budget_head,
                            'category' => $item->category,
                            'mother_sanction_amount' => 0,
                            'available_fund' => 0,
                            'old_mother_sanction_amount' => 0,
                            'new_mother_sanction_amount' => 0,
                            'old_available_fund' => 0,
                            'new_available_fund' => 0,
                            'action_type' => $item->action_type,
                            'change_description' => $item->change_description,
                            'changed_by' => $item->changed_by,
                            'history_timestamp' => $item->history_timestamp,
                        ];
                    }
                    
                    // Aggregate amounts
                    $budgetHeadMap[$budgetKey]['mother_sanction_amount'] += floatval($item->mother_sanction_amount ?? 0);
                    $budgetHeadMap[$budgetKey]['available_fund'] += floatval($item->available_fund ?? 0);
                    $budgetHeadMap[$budgetKey]['old_mother_sanction_amount'] += floatval($item->old_mother_sanction_amount ?? 0);
                    $budgetHeadMap[$budgetKey]['new_mother_sanction_amount'] += floatval($item->new_mother_sanction_amount ?? 0);
                    $budgetHeadMap[$budgetKey]['old_available_fund'] += floatval($item->old_available_fund ?? 0);
                    $budgetHeadMap[$budgetKey]['new_available_fund'] += floatval($item->new_available_fund ?? 0);
                }

                $budgetHeads = collect($budgetHeadMap)->values();

                // Get all unique ky_ms_no values
                $allKyMsNos = $group->pluck('ky_ms_no')->unique()->filter()->values()->toArray();
                $kyMsNoDisplay = !empty($allKyMsNos) ? implode(', ', $allKyMsNos) : ($firstItem->ky_ms_no ?? '');

                return [
                    'id' => $firstItem->history_id,
                    'financial_year' => $firstItem->financial_year,
                    'state_id' => $firstItem->state_id,
                    'ky_ms_no' => $kyMsNoDisplay,
                    'ky_ms_no_list' => $allKyMsNos,
                    'sls_name' => $firstItem->sls_name,
                    'pd_component' => $firstItem->pd_component,
                    'sanction_date' => $firstItem->sanction_date,
                    'budget_heads' => $budgetHeads,
                    'action_type' => $firstItem->action_type,
                    'changed_by' => $firstItem->changed_by,
                    'history_timestamp' => $firstItem->history_timestamp,
                    'change_description' => $firstItem->change_description,
                    'state' => [
                        'id' => $firstItem->state_id,
                        'name' => $firstItem->state->name ?? ''
                    ],
                ];
            })->values();

            return response()->json($transformedData);
        } catch (\Exception $e) {
            Log::error('Error fetching mother sanction history:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'An error occurred while fetching history',
                'message' => $e->getMessage()
            ], 500);
        }
    }

   
}