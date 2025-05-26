<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MdProgramDivisionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all program divisions
        $programDivisions = \App\Models\MdProgramDivision::all();

        // Return a view with the program divisions
        return view('program_divisions.index', compact('programDivisions'));
        // Return a view with the program divisions
        // Alternatively, if you want to return a JSON response:
        // return response()->json($programDivisions);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
