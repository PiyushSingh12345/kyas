<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\BudgetHead;
use App\Models\SlsPDComponent;
use App\Models\FundAllocation;
use App\Models\MotherSanction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // Group data by sls_name, ky_ms_no and state_id to get all budget heads
        
        $groupedData = $data->groupBy(function($item) {
            return $item->sls_name . '|' . $item->ky_ms_no . '|' . $item->state_id;
        });

        // Transform the grouped data
        $transformedData = $groupedData->map(function($group) {
            $firstItem = $group->first();
            
            // Get all budget heads for this group
            $budgetHeads = $group->map(function($item) use ($firstItem) {
                // Get expenditure from daily_sanction table for this budget head
                $expenditure = DB::table('daily_sanction')
                    ->where('mother_sanction', $firstItem->ky_ms_no)
                    ->where('budget_head', $item->budget_head)
                    ->where('state_id', $firstItem->state_id)
                    ->sum('center_share_amount');
                
                // Debug logging for expenditure calculation
                Log::info('Expenditure calculation', [
                    'ky_ms_no' => $firstItem->ky_ms_no,
                    'budget_head' => $item->budget_head,
                    'state_id' => $firstItem->state_id,
                    'expenditure' => $expenditure
                ]);
                
                return [
                    'budget_head' => $item->budget_head,
                    'category' => $item->category,
                    'available_fund' => $item->available_fund,
                    'mother_sanction_amount' => $item->mother_sanction_amount,
                    'expenditure' => $expenditure ?: 0,
                ];
            })->filter(function($item) {
                return !empty($item['budget_head']);
            })->values();

            // Calculate totals
            $totalAmount = $group->sum('mother_sanction_amount');
            $totalAvailableFund = $group->sum('available_fund');

            return [
                'id' => $firstItem->id,
                'financial_year' => $firstItem->financial_year,
                'state_id' => $firstItem->state_id,
                'ms_sequence_no' => $firstItem->ms_sequence_no,
                'file_no' => $firstItem->file_no,
                'ifd_no' => $firstItem->ifd_no,
                'sanction_date' => $firstItem->sanction_date,
                'ky_ms_no' => $firstItem->ky_ms_no,
                'sls_name' => $firstItem->sls_name,
                'pd_component' => $firstItem->pd_component,
                'total_mother_sanction_amount' => $totalAmount,
                'total_available_fund' => $totalAvailableFund,
                'budget_heads' => $budgetHeads,
                'uc_received_from_State' => $firstItem->uc_received_from_State,
                'signed_copy_of_mother_sanction' => $firstItem->signed_copy_of_mother_sanction,
                'last_id' => $firstItem->last_id,
                'status' => $firstItem->status == 1 ? 'active' : 'inactive',
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
                
            ]));

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
        $validated = $request->validate([
            'ky_ms_no' => 'required|string',
            'action' => 'required|in:deactivate,activate'
        ]);

        $kyMsNo = $validated['ky_ms_no'];
        $action = $validated['action'];

        // Find all records with the same ky_ms_no
        $records = MotherSanction::where('ky_ms_no', $kyMsNo)->get();

        if ($records->isEmpty()) {
            return response()->json([
                'message' => 'No records found with the given KY MS No.',
                'success' => false
            ], 404);
        }

        if ($action === 'deactivate') {
            // Deactivate all records with the same ky_ms_no
            $updated = MotherSanction::where('ky_ms_no', $kyMsNo)
                ->update(['status' => 0]);
            
            return response()->json([
                'message' => 'Records deactivated successfully',
                'success' => true,
                'updated_count' => $updated
            ]);
        } else {
            // Activate all records with the same ky_ms_no
            $updated = MotherSanction::where('ky_ms_no', $kyMsNo)
                ->update(['status' => 1]);
            
            return response()->json([
                'message' => 'Records activated successfully',
                'success' => true,
                'updated_count' => $updated
            ]);
        }

    } catch (\Exception $e) {
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


   
}