<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\MotherSanction;
use App\Models\DailySanction;
use App\Models\SlsPDComponent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            ->groupBy('daily_sanction_no');

        // Get the sum of center_share_amount for each state_id
        $stateAmounts = DB::table('daily_sanction')
            ->select('state_id', DB::raw('SUM(center_share_amount) as total_amount'))
            ->groupBy('state_id')
            ->pluck('total_amount', 'state_id')
            ->toArray();

        // Get the sum of center_share_amount for each daily_sanction_no
        $dailySanctionAmounts = DB::table('daily_sanction')
            ->select('daily_sanction_no', DB::raw('SUM(center_share_amount) as total_amount'))
            ->groupBy('daily_sanction_no')
            ->pluck('total_amount', 'daily_sanction_no')
            ->toArray();

        // Get the sum of mother_sanction_amount for each daily_sanction_no
        $motherSanctionAmounts = DB::table('daily_sanction')
            ->select('daily_sanction_no', DB::raw('SUM(mother_sanction_amount) as total_amount'))
            ->groupBy('daily_sanction_no')
            ->pluck('total_amount', 'daily_sanction_no')
            ->toArray();
        
        $data = DailySanction::with(['state', 'slsComponent'])
            ->whereIn('id', $subQuery)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) use ($stateAmounts, $dailySanctionAmounts, $motherSanctionAmounts) {
                $item->full_sls_name = $item->slsComponent ? $item->slsComponent->full_sls_name : null;
                $item->sls_pd = $item->slsComponent ? $item->slsComponent->slsPD : null;
                $item->state_total_amount = $stateAmounts[$item->state_id] ?? 0;
                $item->daily_sanction_total_amount = $dailySanctionAmounts[$item->daily_sanction_no] ?? 0;
                $item->mother_sanction_total_amount = $motherSanctionAmounts[$item->daily_sanction_no] ?? 0;
                
                // Get budget head details for this daily sanction
                $budgetHeads = DailySanction::where('daily_sanction_no', $item->daily_sanction_no)
                    ->select('budget_head', 'center_share_amount')
                    ->get()
                    ->map(function ($budget) {
                        return [
                            'budget_head' => $budget->budget_head,
                            'daily_sanction_amount' => $budget->center_share_amount
                        ];
                    });
                
                $item->budget_heads = $budgetHeads;
                return $item;
            });

        return response()->json($data);
    }

public function store(Request $request)
{
    try {
        // Log the incoming request for debugging
        Log::info('Daily Sanction Store Request', [
            'request_data' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        $validated = $request->validate([
            'financial_year' => 'required|string',
            'state_id' => 'required|integer',
            'ds_date' => 'required|date',
            'daily_sanction_no' => 'required|string|unique:daily_sanction,daily_sanction_no',
            'mother_sanction' => 'required|string',
            'ifd_no' => 'required|string',
            'sls_name' => 'required|string',
            'entries' => 'required|array|min:1',
            'entries.*.budget_head' => 'required|string',
            'entries.*.mother_sanction_amount' => 'required|numeric',
            'entries.*.available_amount' => 'required|numeric',
            'entries.*.center_share_amount' => 'required|numeric',
        ]);

        Log::info('Daily Sanction Validation Passed', ['validated_data' => $validated]);

        foreach ($validated['entries'] as $entry) {
            DailySanction::create([
                'financial_year' => $validated['financial_year'],
                'state_id' => $validated['state_id'],
                'ds_date' => $validated['ds_date'],
                'daily_sanction_no' => $validated['daily_sanction_no'],
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

        Log::info('Daily Sanction Entries Created Successfully');
        return response()->json(['message' => 'Daily sanction entries saved successfully'], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Daily Sanction Validation Error', [
            'errors' => $e->errors(),
            'request_data' => $request->all()
        ]);
        
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        Log::error('Daily Sanction Store Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);
        
        return response()->json([
            'message' => 'Failed to save daily sanction entries. Please try again.',
            'error' => $e->getMessage()
        ], 500);
    }
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

    /**
     * Get sum of daily sanction amounts by budget head
     * This returns the total of all center_share_amount for each budget_head
     */
    public function getDailySanctionAmountsByBudgetHead(Request $request)
    {
        try {
            $budgetHeads = $request->query('budget_heads');
            $stateId = $request->query('state_id');
            $financialYear = $request->query('financial_year');

            if (!$budgetHeads) {
                return response()->json([
                    'success' => false,
                    'message' => 'Budget heads are required',
                    'data' => []
                ], 400);
            }

            // Parse budget heads if it's a JSON string or array
            if (is_string($budgetHeads)) {
                $budgetHeads = json_decode($budgetHeads, true) ?: explode(',', $budgetHeads);
            }

            if (!is_array($budgetHeads)) {
                $budgetHeads = [$budgetHeads];
            }

            // Build query
            $query = DB::table('daily_sanction')
                ->where('status', 1)
                ->whereIn(DB::raw('TRIM(budget_head)'), array_map('trim', $budgetHeads));

            // Add optional filters
            if ($stateId) {
                $query->where('state_id', $stateId);
            }

            if ($financialYear) {
                $query->where('financial_year', $financialYear);
            }

            // Get sum of center_share_amount grouped by budget_head
            $results = $query
                ->select(
                    DB::raw('TRIM(budget_head) as budget_head'),
                    DB::raw('SUM(center_share_amount) as total_amount')
                )
                ->groupBy(DB::raw('TRIM(budget_head)'))
                ->get()
                ->mapWithKeys(function ($item) {
                    return [trim($item->budget_head) => floatval($item->total_amount ?? 0)];
                });

            // Ensure all requested budget heads are in the result (with 0 if not found)
            $data = [];
            foreach ($budgetHeads as $bh) {
                $bh = trim($bh);
                $data[$bh] = $results->get($bh, 0);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching daily sanction amounts by budget head: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch daily sanction amounts',
                'data' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function timeSeriesReport(Request $request)
    {
        $query = DailySanction::with(['state', 'slsComponent'])
            ->where('status', 1);

        // Apply filters
        if ($request->has('state_id') && $request->state_id) {
            $query->where('state_id', $request->state_id);
        }

        if ($request->has('financial_year') && $request->financial_year) {
            $query->where('financial_year', $request->financial_year);
        }

        if ($request->has('budget_head') && $request->budget_head) {
            $query->where('budget_head', $request->budget_head);
        }

        $data = $query->get();

        // Get unique financial years for columns
        $financialYears = $data->pluck('financial_year')->unique()->sort()->values()->toArray();
        
        // Get unique states
        $states = $data->pluck('state_id')->unique();
        $statesWithNames = DB::table('states')
            ->whereIn('id', $states)
            ->pluck('name', 'id')
            ->toArray();

        // Group by state and budget head
        $grouped = [];
        
        foreach ($states as $stateId) {
            $stateName = $statesWithNames[$stateId] ?? 'Unknown';
            $stateData = $data->where('state_id', $stateId);
            $budgetHeads = $stateData->pluck('budget_head')->unique();
            
            $budgetHeadRows = [];
            
            foreach ($budgetHeads as $budgetHead) {
                $budgetData = $stateData->where('budget_head', $budgetHead);
                $metrics = [];
                
                foreach ($financialYears as $year) {
                    $yearData = $budgetData->where('financial_year', $year);
                    $metrics[$year] = [
                        'center_share_amount' => round($yearData->sum('center_share_amount'), 2),
                        'mother_sanction_amount' => round($yearData->sum('mother_sanction_amount'), 2),
                        'available_amount' => round($yearData->sum('available_amount'), 2),
                    ];
                }
                
                $budgetHeadRows[] = [
                    'budget_head' => $budgetHead,
                    'metrics' => $metrics,
                ];
            }
            
            // Add total row for the state
            $totalMetrics = [];
            foreach ($financialYears as $year) {
                $yearData = $stateData->where('financial_year', $year);
                $totalMetrics[$year] = [
                    'center_share_amount' => round($yearData->sum('center_share_amount'), 2),
                    'mother_sanction_amount' => round($yearData->sum('mother_sanction_amount'), 2),
                    'available_amount' => round($yearData->sum('available_amount'), 2),
                ];
            }
            
            $budgetHeadRows[] = [
                'budget_head' => 'Total',
                'metrics' => $totalMetrics,
                'is_total' => true,
            ];
            
            $grouped[] = [
                'state' => $stateName,
                'state_id' => $stateId,
                'items' => $budgetHeadRows,
            ];
        }

        return response()->json([
            'years' => $financialYears,
            'data' => $grouped,
        ]);
    }

}
