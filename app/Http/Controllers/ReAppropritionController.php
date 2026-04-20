<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReAppropriation; 
use App\Models\BudgetPhase;
class ReAppropritionController extends Controller
{
    private const SAFE_TEXT_PATTERN = "/^[A-Za-z0-9\s\-\.,&()\/:'_]+$/";

    public function index()
    {
        $reappropriations = ReAppropriation::orderBy('created_at', 'desc')->get();
        return response()->json($reappropriations);
    }

    public function store(Request $request)
    {
        // Remove commas if values are formatted like "12,345"
        $fromBE = str_replace(',', '', $request->input('from_be'));
        $toBE = str_replace(',', '', $request->input('to_be'));

        // Optional: convert to numbers and handle empty/null cases
        $fromBE = is_numeric($fromBE) ? (float) $fromBE : 0;
        $toBE = is_numeric($toBE) ? (float) $toBE : 0;
        $reappropriationAmount = is_numeric($request->reappropriation_amount) ? (float) $request->reappropriation_amount : 0;

        $data = $request->validate([
            'financial_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{2,4}$/'],
            'budget_phase' => ['required', 'string', 'max:50', 'regex:' . self::SAFE_TEXT_PATTERN],
            'ro_date' => 'nullable|date',
            'type' => ['nullable', 'string', 'max:100', 'regex:' . self::SAFE_TEXT_PATTERN],
            'section' => ['nullable', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
            'program_division_id' => 'nullable|integer',
            'from_budget_head_id' => 'nullable|integer',
            'from_budget_head_remarks' => ['nullable', 'string', 'max:1000', 'regex:' . self::SAFE_TEXT_PATTERN],
            'to_budget_head_id' => 'nullable|integer',
            'reappropriation_amount' => 'nullable|numeric',
            'other_details' => ['nullable', 'string', 'max:1000', 'regex:' . self::SAFE_TEXT_PATTERN],
            'entity_type' => ['nullable', 'string', 'max:100', 'regex:' . self::SAFE_TEXT_PATTERN],
            'selected_entity_ids' => 'nullable|array',
            'from_rule' => ['nullable', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
            'to_rule' => ['nullable', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
            'reason_for_additionality' => ['nullable', 'string', 'max:1000', 'regex:' . self::SAFE_TEXT_PATTERN],
            'proposal_attract_ns_nis' => ['nullable', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
            'remarks' => ['nullable', 'string', 'max:1000', 'regex:' . self::SAFE_TEXT_PATTERN],
        ], [
            'financial_year.regex' => 'Financial year format must be like 2025-26.',
            '*.regex' => 'Input contains invalid special characters.',
        ]);

        // Save reappropriation record
        $reappropriation = ReAppropriation::create($data);

        // Update budget amounts using computed values
        // Only deduct from budget if it's not the "Other" option (ID 999)
        if ($request->from_budget_head_id != 999) {
            $updatedFromBudget = $fromBE - $reappropriationAmount;
            
            // Update DB using Eloquent model to trigger observer
            $fromBudgetPhase = BudgetPhase::where('budget_head_id', $request->from_budget_head_id)->first();
            if ($fromBudgetPhase) {
                $fromBudgetPhase->update(['budget_amount' => $updatedFromBudget]);
            }
        }

        $updatedToBudget = $toBE + $reappropriationAmount;

        // Update DB using Eloquent model to trigger observer
        $toBudgetPhase = BudgetPhase::where('budget_head_id', $request->to_budget_head_id)->first();
        if ($toBudgetPhase) {
            $toBudgetPhase->update(['budget_amount' => $updatedToBudget]);
        }

        return response()->json($reappropriation, 201);
    }

    public function getBudgetAmountByHead(Request $request)
    {
        $budgetHeadId = $request->query('budget_head_id');
        if (!$budgetHeadId) {
            return response()->json(['error' => 'budget_head_id is required'], 400);
        }

        // Assuming you want the sum of budget_amounts for that budget_head_id or latest
        $budgetPhase = BudgetPhase::where('budget_head_id', $budgetHeadId)->first();

        if (!$budgetPhase) {
            return response()->json(['budget_amount' => 0]);
        }

        return response()->json(['budget_amount' => $budgetPhase->budget_amount]);
    }
} 
