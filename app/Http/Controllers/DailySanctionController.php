<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\MotherSanction;
use App\Models\DailySanction;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DailySanctionController extends Controller
{
    
    public function getMotherSanctions(Request $request)
    {
        $stateId = $request->query('state_id');

        if (!$stateId) {
            return response()->json([], 400); // Bad request if no state_id
        }

        $data = MotherSanction::select('ky_ms_no')
        ->whereNotNull('ky_ms_no')
        ->where('status', '1')
        ->where('state_id', $stateId)
        ->distinct()
        ->get();

        return response()->json($data);
    }

public function list()
    {
        
        $subQuery = DB::table('daily_sanction')
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('mother_sanction');

        
        $data = DailySanction::with('state')
            ->whereIn('id', $subQuery)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($data);
    }

public function store(Request $request)
{
    $validated = $request->validate([
        'financial_year' => 'required|string',
        'state_id' => 'required|integer',
        'ds_date' => 'required|date',
        'mother_sanction' => 'required|string',
        'ifd_no' => 'required|string',
        'sls_name' => 'required|string',
        'entries' => 'required|array|min:1',
        'entries.*.budget_head' => 'required|string',
        'entries.*.mother_sanction_amount' => 'required|numeric',
        'entries.*.available_amount' => 'required|numeric',
        'entries.*.center_share_amount' => 'required|numeric',
    ]);

    foreach ($validated['entries'] as $entry) {
        DailySanction::create([
            'financial_year' => $validated['financial_year'],
            'state_id' => $validated['state_id'],
            'ds_date' => $validated['ds_date'],
            'mother_sanction' => $validated['mother_sanction'],
            'ifd_no' => $validated['ifd_no'],
            'sls_name' => $validated['sls_name'],
            'budget_head' => $entry['budget_head'],
            'mother_sanction_amount' => $entry['mother_sanction_amount'],
            'available_amount' => $entry['available_amount'],
            'center_share_amount' => $entry['center_share_amount'],
            'status' => 1
        ]);
    }

    return response()->json(['message' => 'Daily sanction entries saved successfully'], 201);
}


    public function getMotherSanctionDetails($ky_ms_no)
    {
        $records = MotherSanction::where('ky_ms_no', $ky_ms_no)
            ->where('status', 1)
            ->get(['ifd_no', 'sls_name', 'budget_head', 'available_fund', 'mother_sanction_amount']);

        if ($records->isEmpty()) {
            return response()->json([], 404);
        }

        $meta = [
            'ifd_no' => $records[0]->ifd_no,
            'sls_name' => $records[0]->sls_name,
        ];

        $entries = $records->map(fn ($item) => [
            'budget_head' => $item->budget_head,
            'available_fund' => $item->available_fund,
            'mother_sanction_amount' => $item->mother_sanction_amount,
        ]);

        return response()->json([
            'meta' => $meta,
            'entries' => $entries,
        ]);
    }

}
