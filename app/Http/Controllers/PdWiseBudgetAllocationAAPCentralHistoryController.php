<?php

namespace App\Http\Controllers;

use App\Models\PdWiseAapAllocationHistory;
use App\Models\BudgetHead;
use App\Models\MdProgramDivision;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PdWiseBudgetAllocationAAPCentralHistoryController extends Controller
{
    /**
     * Display a listing of the PD wise AAP allocation history records.
     */
    public function index(Request $request)
    {
        $query = PdWiseAapAllocationHistory::with(['budgetHead', 'programDivision'])
            ->orderBy('history_timestamp', 'desc');

        // Apply filters if provided
        if ($request->filled('financial_year')) {
            $query->where('financial_year', $request->financial_year);
        }

        if ($request->filled('bh_id')) {
            $query->where('bh_id', $request->bh_id);
        }

        if ($request->filled('pd_id')) {
            $query->where('pd_id', $request->pd_id);
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('changed_by')) {
            $query->where('changed_by', $request->changed_by);
        }

        // Apply sorting
        $sortField = $request->get('sort_field', 'history_timestamp');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // Validate sort field to prevent SQL injection
        $allowedSortFields = [
            'history_timestamp', 'financial_year', 'bh_id', 'pd_id',
            'amount', 'status', 'action_type', 'created_at', 'updated_at'
        ];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $historyRecords = $query->paginate(50);

        return Inertia::render('Annual_action_plan/PdWiseBudgetAllocationAAPCentralHistory', [
            'historyRecords' => $historyRecords,
            'filters' => $request->only(['financial_year', 'bh_id', 'pd_id', 'action_type', 'changed_by']),
            'sortField' => $sortField,
            'sortDirection' => $sortDirection
        ]);
    }

    /**
     * Get PD wise AAP allocation history data for API.
     */
    public function getHistoryData(Request $request)
    {
        $query = PdWiseAapAllocationHistory::with(['budgetHead', 'programDivision'])
            ->orderBy('history_timestamp', 'desc');

        // Apply filters
        if ($request->filled('financial_year')) {
            $query->where('financial_year', $request->financial_year);
        }

        if ($request->filled('bh_id')) {
            $query->where('bh_id', $request->bh_id);
        }

        if ($request->filled('pd_id')) {
            $query->where('pd_id', $request->pd_id);
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('changed_by')) {
            $query->where('changed_by', $request->changed_by);
        }

        // Apply sorting
        $sortField = $request->get('sort_field', 'history_timestamp');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        $allowedSortFields = [
            'history_timestamp', 'financial_year', 'bh_id', 'pd_id',
            'amount', 'status', 'action_type', 'created_at', 'updated_at'
        ];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Apply pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $historyRecords = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $historyRecords->items(),
            'pagination' => [
                'current_page' => $historyRecords->currentPage(),
                'last_page' => $historyRecords->lastPage(),
                'per_page' => $historyRecords->perPage(),
                'total' => $historyRecords->total(),
                'from' => $historyRecords->firstItem(),
                'to' => $historyRecords->lastItem(),
                'has_more_pages' => $historyRecords->hasMorePages(),
                'prev_page_url' => $historyRecords->previousPageUrl(),
                'next_page_url' => $historyRecords->nextPageUrl(),
                'links' => $historyRecords->linkCollection()->toArray()
            ]
        ]);
    }

    /**
     * Get budget heads that have records in pdwise_aap_allocation_history table.
     */
    public function getBudgetHeadsWithHistory()
    {
        $budgetHeads = BudgetHead::whereHas('pdWiseAapAllocationHistory')
            ->select('id', 'budget', 'description')
            ->orderBy('budget', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $budgetHeads
        ]);
    }

    /**
     * Get program divisions that have records in pdwise_aap_allocation_history table.
     */
    public function getProgramDivisionsWithHistory()
    {
        $programDivisions = MdProgramDivision::whereHas('pdWiseAapAllocationHistory')
            ->select('division_id', 'division_name')
            ->orderBy('division_name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $programDivisions
        ]);
    }
}
