<?php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StateController extends Controller
{
    public function index()
    {
        return Inertia::render('Budget_allocation/StateUTs', [
            'states' => State::latest()->get()
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:states,name',
            'description' => 'required|string|max:255',
            'budgethead_fourdigits' => 'nullable|integer'
        ]);

        State::create($validated);

        return redirect()->back()->with('success', 'State added successfully!');

    }
    public function getStatesApi()
    {
        return response()->json(State::all());
    }
}