<?php

namespace App\Http\Controllers;

use App\Models\BudgetPhaseHistory;
use App\Models\BudgetHead;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BudgetPhaseHistoryController extends Controller
{
    /**
     * Display a listing of the budget phase history records.
     */
    public function index(Request $request)
    {
        $query = BudgetPhaseHistory::with(['budgetHead', 'changedByUser'])
            ->orderBy('history_timestamp', 'desc');

        // Apply filters if provided
        if ($request->filled('financial_year')) {
            $query->where('financial_year', $request->financial_year);
        }

        if ($request->filled('budget_phase')) {
            $query->where('budget_phase', $request->budget_phase);
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('changed_by')) {
            $query->where('changed_by', $request->changed_by);
        }

        if ($request->filled('budget_head_id')) {
            $query->where('budget_head_id', $request->budget_head_id);
        }

        // Apply sorting
        $sortField = $request->get('sort_field', 'history_timestamp');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // Validate sort field to prevent SQL injection
        $allowedSortFields = [
            'history_timestamp', 'financial_year', 'budget_phase', 
            'budget_amount', 'status', 'draft_flag', 'action_type', 
            'created_at', 'updated_at'
        ];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $historyRecords = $query->paginate(50);

        return Inertia::render('Budget_allocation/BudgetPhaseHistory', [
            'historyRecords' => $historyRecords,
            'filters' => $request->only(['financial_year', 'budget_phase', 'action_type', 'changed_by', 'budget_head_id']),
            'sortField' => $sortField,
            'sortDirection' => $sortDirection
        ]);
    }

    /**
     * Get budget phase history data for API.
     */
    public function getHistoryData(Request $request)
    {
        $query = BudgetPhaseHistory::with(['budgetHead', 'changedByUser'])
            ->orderBy('history_timestamp', 'desc');

        // Apply filters
        if ($request->filled('financial_year')) {
            $query->where('financial_year', $request->financial_year);
        }

        if ($request->filled('budget_phase')) {
            $query->where('budget_phase', $request->budget_phase);
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('changed_by')) {
            $query->where('changed_by', $request->changed_by);
        }

        if ($request->filled('budget_head_id')) {
            $query->where('budget_head_id', $request->budget_head_id);
        }

        // Apply sorting
        $sortField = $request->get('sort_field', 'history_timestamp');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        $allowedSortFields = [
            'history_timestamp', 'financial_year', 'budget_phase', 
            'budget_amount', 'status', 'draft_flag', 'action_type', 
            'created_at', 'updated_at'
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
     * Get budget heads that have records in budget_phase_history table.
     */
    public function getBudgetHeadsWithHistory()
    {
        $budgetHeads = BudgetHead::whereHas('budgetPhaseHistory')
            ->select('id', 'budget', 'description')
            ->orderBy('budget', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $budgetHeads
        ]);
    }
}
