<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\MotherSanction;
use App\Models\DailySanction;
use App\Models\SlsPDComponent;
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

        // Get the sum of center_share_amount for each state_id
        $stateAmounts = DB::table('daily_sanction')
            ->select('state_id', DB::raw('SUM(center_share_amount) as total_amount'))
            ->groupBy('state_id')
            ->pluck('total_amount', 'state_id')
            ->toArray();
        
        $data = DailySanction::with(['state', 'slsComponent'])
            ->whereIn('id', $subQuery)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) use ($stateAmounts) {
                $item->full_sls_name = $item->slsComponent ? $item->slsComponent->full_sls_name : null;
                $item->sls_pd = $item->slsComponent ? $item->slsComponent->slsPD : null;
                $item->state_total_amount = $stateAmounts[$item->state_id] ?? 0;
                return $item;
            });

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
            'remark' => $request->remark,
            'status' => 1
        ]);
    }

    return response()->json(['message' => 'Daily sanction entries saved successfully'], 201);
}


    public function getMotherSanctionDetails($ky_ms_no)
    {
        $records = MotherSanction::join('pd_and_sls_comp', 'mother_sanction.sls_name', '=', 'pd_and_sls_comp.name')
            ->where('mother_sanction.ky_ms_no', $ky_ms_no)
            ->where('mother_sanction.status', 1)
            ->select(
                'mother_sanction.ifd_no',
                'mother_sanction.sls_name',
                'mother_sanction.budget_head',
                'mother_sanction.available_fund',
                'mother_sanction.mother_sanction_amount',
                'pd_and_sls_comp.sls_code'
            )
            ->get();

        if ($records->isEmpty()) {
            return response()->json([], 404);
        }

        $meta = [
            'ifd_no' => $records[0]->ifd_no,
            'sls_name' => $records[0]->sls_name,
            'sls_code' => $records[0]->sls_code,
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
