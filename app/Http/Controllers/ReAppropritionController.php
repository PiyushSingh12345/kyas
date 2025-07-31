<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReAppropriation; 
use App\Models\BudgetPhase;
class ReAppropritionController extends Controller
{
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
        'financial_year' => 'required|string',
        'budget_phase' => 'required|string',
        'ro_date' => 'nullable|date',
        'type' => 'nullable|string',
        'section' => 'nullable|string',
        'program_division_id' => 'nullable|integer',
        'from_budget_head_id' => 'nullable|integer',
        'to_budget_head_id' => 'nullable|integer',
        'reappropriation_amount' => 'nullable|numeric',
        'other_details' => 'nullable|string',
        'entity_type' => 'nullable|string',
        'selected_entity_ids' => 'nullable|array',
        'from_rule' => 'nullable|string',
        'to_rule' => 'nullable|string',
        'reason_for_additionality' => 'nullable|string',
        'proposal_attract_ns_nis' => 'nullable|string',
        'remarks' => 'nullable|string',
    ]);

    // Save reappropriation record
    $reappropriation = Reappropriation::create($data);

    // Update budget amounts using computed values
    $updatedFromBudget = $fromBE - $reappropriationAmount;
    $updatedToBudget = $toBE + $reappropriationAmount;

    // Update DB
    BudgetPhase::where('budget_head_id', $request->from_budget_head_id)
        ->update(['budget_amount' => $updatedFromBudget]);

    BudgetPhase::where('budget_head_id', $request->to_budget_head_id)
        ->update(['budget_amount' => $updatedToBudget]);

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
