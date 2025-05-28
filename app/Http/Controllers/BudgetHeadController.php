<?php

namespace App\Http\Controllers;

use App\Models\BudgetHead;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BudgetHeadController extends Controller
{
    public function index()
    {
        return Inertia::render('Budget_allocation/BudgetHeads', [
            'BudgetHeads' => BudgetHead::latest()->get() 
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'budget' => 'required|max:255',
            'description' => 'required|string|max:255',
            'category' => 'required'
        ]);

        BudgetHead::create([
        ...$validated,
        'status' => '1',
        ]);

        

        return redirect()->back()->with('success', 'Budget Heads added successfully!');

    }

    public function destroy(BudgetHead $budgetHead)
    {
        $budgetHead->update(['status' => '0']);

        return redirect()->route('BudgetHead.index')
                         ->with('success', 'Budget Head deactivated successfully.');
    }

    public function update(Request $request, BudgetHead $budgetHead)
    {
        $validated = $request->validate([
            'budget' => 'required|max:255',
            'description' => 'required|string|max:255',
            'category' => 'required'
        ]);

        $budgetHead->update($validated);

        return redirect()->back()->with('success', 'Budget Head updated successfully!');
    }
    public function toggleStatus($id, Request $request)
    {
        $budgetHead = BudgetHead::findOrFail($id);
        $budgetHead->status = $request->status;
        $budgetHead->save();

        return back()->with('success', 'Status updated successfully!');
    }

     public function fetchBudgetHeads(Request $request)
    {
        
        $budgetHeads = BudgetHead::where('status', 1)->get();

        return response()->json($budgetHeads);
    }
}
