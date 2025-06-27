<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\BudgetHead;
use App\Models\SlsPDComponent;
use App\Models\FundAllocation;
use App\Models\MotherSanction;
use Illuminate\Support\Facades\DB;

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
       $data = DB::table('fund_allocation as fa')
        ->join('pd_and_sls_comp as pd', function ($join) {
            $join->on('fa.state_id', '=', 'pd.state_id')
                 ->on('fa.sls_pd_name', '=', 'pd.name');
        })
        ->where('fa.state_id', $stateId)
        ->where('fa.sls_pd_name', $slsId)
        ->select('fa.budget', 'pd.slsPD')
        ->get();

        return response()->json($data);
    }

    public function getFundAllocationByBudgetHead(Request $request)
    {
        $budget = $request->query('budget');
        $slsId = $request->query('sls_id');
        $stateId = $request->query('state_id');

        $data = FundAllocation::where('budget', $budget)
                    ->when($slsId, fn($q) => $q->where('sls_pd_name', $slsId))
                    ->when($stateId, fn($q) => $q->where('state_id', $stateId))
                    ->first(['category', 'budget_amount','amount']);

        if (!$data) {
            return response()->json(['message' => 'No matching record found.'], 404);
        }

        return response()->json($data);
    }

   
public function list()
{
    // Get latest record per group of `last_id`
    $subQuery = DB::table('mother_sanction')
        ->select(DB::raw('MAX(id) as id'))
        ->groupBy('last_id');

    // Then get those records with relations
    $data = MotherSanction::with('state')
        ->whereIn('id', $subQuery)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($data);
}

    public function addMotherSanction(Request $request)
{
    $ucFilePath = null;
    $signedCopyPath = null;

    if ($request->hasFile('uc_file_path')) {
        $ucFilePath = $request->file('uc_file_path')->store('mother_sanction', 'public');
    }

    if ($request->hasFile('signed_copy_path')) {
        $signedCopyPath = $request->file('signed_copy_path')->store('mother_sanction', 'public');
    }

    $commonData = [
        'financial_year' => $request->financial_year,
        'state_id' => $request->state_id,
        'ms_sequence_no' => $request->ms_sequence_no,
        'file_no' => $request->file_no,
        'ifd_no' => $request->ifd_no,
        'sanction_date' => $request->sanction_date,
        'ky_ms_no' => $request->ky_ms_no,
        'sls_name' => $request->sls_name,
        'pd_component' => $request->pd_component,
        'total_mother_sanction_amount' => $request->total_mother_sanction_amount,
        'uc_received_from_state' => $ucFilePath,
        'signed_copy_of_mother_sanction' => $signedCopyPath,
        'status' => $request->status,
        'last_id'=> rand(10, 99)
    ];

    $reappropriations = json_decode($request->reappropriations, true);

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
}


   
}