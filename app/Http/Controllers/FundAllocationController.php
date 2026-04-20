<?php

namespace App\Http\Controllers;


use App\Models\BudgetPhase;
use App\Models\BudgetHead;
use App\Models\FundAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FundAllocationController extends Controller
{
    private const SAFE_TEXT_PATTERN = "/^[A-Za-z0-9\s\-\.,&()\/:'_]+$/";
    private const SAFE_BUDGET_HEAD_PATTERN = '/^(\d{15}|\d{4}\.\d{2}\.\d{3}\.\d{2}\.\d{2}\.\d{2})$/';
   

    // Store budget allocation (as draft or final)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fund_allocation_for' => ['required', 'string', 'max:100', 'regex:' . self::SAFE_TEXT_PATTERN],
            'financial_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{2,4}$/'],
            'budget_phase' => ['required', 'string', 'max:50', 'regex:' . self::SAFE_TEXT_PATTERN],
            'state_id' => 'required|integer',
            'status' => 'required|in:0,1',
            'budget_array' => 'required|array|min:1',
            'budget_array.*.category' => ['required', 'string', 'max:100', 'regex:' . self::SAFE_TEXT_PATTERN],
            'budget_array.*.budget' => ['required', 'string', 'regex:' . self::SAFE_BUDGET_HEAD_PATTERN],
            'budget_array.*.budget_amount' => 'required|numeric',
            'budget_array.*.sls_pd_name' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
            'budget_array.*.amount' => 'required|numeric'
        ], [
            'financial_year.regex' => 'Financial year format must be like 2025-26.',
            'budget_array.*.budget.regex' => 'Budget head format is invalid.',
            'budget_array.*.category.regex' => 'Category contains invalid special characters.',
            'budget_array.*.sls_pd_name.regex' => 'SLS/PD name contains invalid special characters.',
        ]);

        DB::beginTransaction();

        try {
            foreach ($validated['budget_array'] as $entry) {
                FundAllocation::updateOrCreate(
                    [
                        'fund_allocation' => $validated['fund_allocation_for'],
                        'financial_year' => $validated['financial_year'],
                        'budget_phase' => $validated['budget_phase'],
                        'state_id' => $validated['state_id'],
                        'category' => $entry['category'],
                        'budget' => $entry['budget'],
                        'sls_pd_name' => $entry['sls_pd_name'],
                    ],
                    [
                        'budget_amount' => $entry['budget_amount'],
                        'amount' => $entry['amount'],
                        'status' => $validated['status'],
                    ]
                );
            }

            DB::commit();

            return response()->json(['message' => 'Data saved/updated successfully.'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to save data.', 'details' => $e->getMessage()], 500);
        }
    }


    public function getBudgetAllocations(Request $request)
    {
        $validated = $request->validate([
            'fund_allocation_for' => 'required|string',
            'financial_year' => 'required|string',
            'budget_phase' => 'required|string',
        ]);

        $records = DB::table('fund_allocations')
            ->where('fund_allocation', $validated['fund_allocation_for'])
            ->where('financial_year', $validated['financial_year'])
            ->where('budget_phase', $validated['budget_phase'])
            ->get();

        $grouped = [];

        foreach ($records as $record) {
            $grouped[$record->category][] = [
                'budget' => $record->budget,
                'amount' => $record->budget_amount,
                'sls_pd_name' => $record->sls_pd_name,
                'pd_amount' => $record->amount,
            ];
        }

        // Transform to match frontend expectations
        $finalResult = [];
        foreach ($grouped as $category => $budgets) {
            $byBudget = [];
            foreach ($budgets as $entry) {
                if (!isset($byBudget[$entry['budget']])) {
                    $byBudget[$entry['budget']] = [
                        'budget' => $entry['budget'],
                        'amount' => $entry['amount'],
                        'sls_pd' => [],
                    ];
                }
                $byBudget[$entry['budget']]['sls_pd'][$entry['sls_pd_name']] = $entry['pd_amount'];
            }

            $finalResult[] = [
                'category' => $category,
                'budgetArray' => array_values($byBudget),
            ];
        }

        return response()->json($finalResult);
    }

    public function getBudgetAllocation(Request $request)
{
    $validated = $request->validate([
        'fund_allocation_for' => 'required|string',
        'financial_year' => 'required|string',
        'budget_phase' => 'required|string',
        'state_id' => 'nullable|integer', // Add this
    ]);

    $query = DB::table('fund_allocations')
        ->where('fund_allocation', $validated['fund_allocation_for'])
        ->where('financial_year', $validated['financial_year'])
        ->where('budget_phase', $validated['budget_phase']);

    if (!empty($validated['state_id'])) {
        $query->where('state_id', $validated['state_id']);
    }

    $records = $query->get();

    // (rest of the grouping logic remains the same)
}




}
