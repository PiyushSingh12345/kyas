<?php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StateController extends Controller
{
    private const SAFE_TEXT_PATTERN = "/^[A-Za-z0-9\s\-\.,&()\/']+$/";

    public function index()
    {
        return Inertia::render('Budget_allocation/StateUTs', [
            'states' => State::latest()->get()
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:states,name', 'regex:' . self::SAFE_TEXT_PATTERN],
            'description' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
            'budgethead_fourdigits' => 'nullable|integer'
        ], [
            'name.regex' => 'State name contains invalid special characters.',
            'description.regex' => 'Description contains invalid special characters.',
        ]);

        State::create($validated);

        return redirect()->back()->with('success', 'State added successfully!');

    }
    public function getStatesApi()
    {
        if (request()->header('X-Inertia')) {
            return \Inertia\Inertia::location(route('state-uts'));
        }

        return response()->json(State::all());
    }
}