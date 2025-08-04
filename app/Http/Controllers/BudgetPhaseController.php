<?php

namespace App\Http\Controllers;


use App\Models\BudgetPhase;
use App\Models\BudgetHead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BudgetPhaseController extends Controller
{
    // Fetch only active budget heads (status = 1)
    public function fetchActiveBudgetHeads(Request $request)
    {

        $phase = $request->query('phase'); // BE/FE/RE
        $year = $request->query('year');   // 2024-2025

        // Get all budget heads with status 1
        $budgetHeads = BudgetHead::where('status', 1)->get();

        // Get already saved budget_phase records
        $existingAllocations = BudgetPhase::where('budget_phase', $phase)
            ->where('financial_year', $year)
            ->where('status', 1)
            ->get()
            ->keyBy('budget_head_id'); // Index by budget_head_id for faster lookup

        $mergedData = $budgetHeads->map(function ($head) use ($existingAllocations) {
            if ($existingAllocations->has($head->id)) {
                $item = $existingAllocations[$head->id];
                return [
                    'id' => $head->id,
                    'budget' => $head->budget,
                    'description' => $head->description,
                    'amount' => $item->budget_amount,
                    'draft_flag' => $item->draft_flag,
                    'is_existing' => true,
                ];
            } else {
                // No allocation yet → allow user to input
                return [
                    'id' => $head->id,
                    'budget' => $head->budget,
                    'description' => $head->description,
                    'amount' => null,
                    'draft_flag' => 0, // allow editing
                    'is_existing' => false,
                ];
            }
        });

        return response()->json($mergedData);
    }

    public function budgetPhaseSummary(Request $request)
    {
        $year = $request->query('year');

        // Get all active budget heads
        $budgetHeads = BudgetHead::where('status', 1)->get();

        // Fetch all phase-wise allocations for this year
        $allocations = BudgetPhase::where('financial_year', $year)
            ->where('status', 1)
            ->get()
            ->groupBy(['budget_head_id', 'budget_phase']); // Group by both head and phase

        $result = $budgetHeads->map(function ($head) use ($allocations) {
            return [
                'id' => $head->id,
                'budget' => $head->budget,
                'description' => $head->description,
                'be' => optional(optional($allocations[$head->id]['BE'] ?? [])[0])->budget_amount,
                're' => optional(optional($allocations[$head->id]['RE'] ?? [])[0])->budget_amount,
                'fe' => optional(optional($allocations[$head->id]['FE'] ?? [])[0])->budget_amount,
            ];
        });

        return response()->json($result);
    }


    public function fetchActiveBudgetAllocation(Request $request)
    {
        $financialYear = $request->query('financial_year');
        $budgetPhase = $request->query('budget_phase');
        $fundAllocationFor = $request->query('fund_allocation_for'); // Optional, use if needed

        $budgetHeads = BudgetHead::where('status', 1)->get();

        $grouped = [];

        foreach ($budgetHeads as $head) {
            $phases = BudgetPhase::where('budget_head_id', $head->id)
                ->where('financial_year', $financialYear)
                ->where('budget_phase', $budgetPhase)
                ->where('draft_flag', 1)
                ->get(['budget_head_id', 'budget_amount']);

            if ($phases->isNotEmpty()) {
                // Group by category
                $grouped[$head->category] = $grouped[$head->category] ?? [];

                foreach ($phases as $phase) {
                    $grouped[$head->category][] = [
                        'budget' => $head->budget,
                        'amount' => $phase->budget_amount,
                    ];
                }
            }
        }

        // Reformat for frontend
        $result = [];
        foreach ($grouped as $category => $budgetArray) {
            $result[] = [
                'category' => $category,
                'budgetArray' => $budgetArray
            ];
        }

        return response()->json($result);
    }



    // Store budget allocation (as draft or final)
    public function store(Request $request)
    {
       

        $validated = $request->validate([
            'allocations' => 'required|array',
            'allocations.*.financial_year' => 'required|string|regex:/^\d{4}-\d{2}$/',

            'allocations.*.budget_phase' => 'required|string', // ✅ Add this line
            'allocations.*.budget_head_id' => 'required|exists:budget_heads,id',
            'allocations.*.budget_amount' => 'required|numeric|min:0',
            'allocations.*.status' => 'required|in:0,1',
            'allocations.*.draft_flag' => 'required|in:0,1',
        ]);
        print_r($validated);
        
        DB::beginTransaction();
        try {
            foreach ($validated['allocations'] as $allocation) {
                BudgetPhase::updateOrCreate(
                    [
                        'financial_year' => $allocation['financial_year'],
                        'budget_phase' => $allocation['budget_phase'],
                        'budget_head_id' => $allocation['budget_head_id'],
                    ],
                    [
                        'budget_amount' => $allocation['budget_amount'],
                        'status' => $allocation['status'],
                        'draft_flag' => $allocation['draft_flag'],
                    ]
                );
            }
            Log::info('Saving allocation:', $allocation);

            DB::commit();
            Log::info('Budget saved successfully.');

            return redirect()->back()->with('success', $validated['allocations'][0]['draft_flag'] ? 'Budget submitted successfully.' : 'Draft saved successfully.');
        } catch (\Exception $e) {
            Log::error('Exception occurred while saving budget:', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

            DB::rollBack();
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function addBudget(Request $request){
        echo "efwkfhkw";
    }
}
