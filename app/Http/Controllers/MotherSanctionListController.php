<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
// use App\Models\BudgetHead;
// use App\Models\SlsPDComponent;
// use App\Models\FundAllocation;
use App\Models\MotherSanction;
use Illuminate\Support\Facades\DB;

use Inertia\Inertia;

class MotherSanctionListController extends Controller
{
    //

    public function list(Request $request)
    {
        // Get latest record per group of `last_id`
        $subQuery = DB::table('mother_sanction')
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('last_id');

        $query = MotherSanction::with('state')
            ->whereIn('id', $subQuery)
            ->orderBy('created_at', 'desc');

        // Filtering
        if ($request->filled('year')) {
            $query->where('financial_year', $request->year);
        }
        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }
        if ($request->filled('sanction_date')) {
            $query->where('sanction_date', $request->sanction_date);
        }
        // Program Division filter using join with pd_and_sls_comp
        if ($request->filled('program_division')) {
            $programDivisionId = $request->program_division;
            $query->whereExists(function($q) use ($programDivisionId) {
                $q->select(DB::raw(1))
                    ->from('pd_and_sls_comp as pd')
                    ->whereColumn('pd.name', 'mother_sanction.pd_component')
                    ->where('pd.component', 'PD')
                    ->where('pd.id', $programDivisionId);
            });
        }

        $data = $query->get();

        return response()->json($data);
    }




}
