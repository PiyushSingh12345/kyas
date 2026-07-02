<?php

namespace App\Http\Controllers;
use App\Services\SafePdfValidator;
use Illuminate\Http\Request;
use App\Models\BudgetHead;
use App\Models\SlsPDComponent;
use App\Models\FundAllocation;
use App\Models\MotherSanction;
use App\Models\MotherSanctionHistory;
use App\Models\BudgetPhase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

use Inertia\Inertia;

class MotherSanctionController extends Controller
{
    private const UPLOAD_DISK = 'public';
    private const UPLOAD_DIR = 'mother_sanction';

    public function getBudgetHeads()
    {
        return response()->json(
            BudgetHead::where('status', 1)->select('id', 'budget')->get()
        );
    }

    public function getSlsData($stateId)
    {
        $slsData = SlsPDComponent::where('state_id', $stateId)
                                 ->select('id', 'name','sls_code','full_sls_name')
                                 ->get();

        return response()->json($slsData);
    }

    public function getBudgetDetails($id)
    {
        $budgetHead = BudgetHead::with(['budgetPhases' => function($query) {
            $query->where('status', 1);
        }])->find($id);

        if (!$budgetHead) {
            return response()->json(['message' => 'Budget Head not found'], 404);
        }

        return response()->json([
            'category' => $budgetHead->category,
            'available_amount' => $budgetHead->budgetPhases->sum('budget_amount'),
        ]);
    }

    public function getFundAllocationData(Request $request, $slsId, $stateId)
    {
        $financialYear = $request->query('financial_year');
        $budgetPhase = $request->query('budget_phase', 'BE');
        $pdComponent = $request->query('pd_component');

        $programDivisionId = $this->resolveProgramDivisionIdFromSls($slsId, (int) $stateId, $pdComponent);

        if (!$programDivisionId) {
            return response()->json([]);
        }

        $pdRow = DB::table('pd_and_sls_comp')
            ->where('state_id', $stateId)
            ->where('name', $slsId)
            ->first();

        $query = DB::table('pdwise_aap_allocation as pda')
            ->join('budget_heads as bh', 'pda.bh_id', '=', 'bh.id')
            ->where('pda.pd_id', $programDivisionId);

        $this->applyPdwiseAllocationFilters($query, $financialYear, $budgetPhase, 'pda');

        $data = $query
            ->select('bh.budget as budget', 'pda.amount', 'bh.category')
            ->get()
            ->map(function ($item) use ($pdRow) {
                $item->slsPD = $pdRow->slsPD ?? '';
                return $item;
            });

        return response()->json($data);
    }

    public function getFundAllocationByBudgetHead(Request $request)
    {
        $budget = $request->query('budget');
        $slsId = $request->query('sls_id');
        $stateId = $request->query('state_id');
        $financialYear = $request->query('financial_year');
        $budgetPhase = $request->query('budget_phase', 'BE');
        $pdComponent = $request->query('pd_component');

        if (!$budget || !$slsId || !$stateId) {
            return response()->json(['message' => 'Missing required parameters.'], 400);
        }

        $programDivisionId = $this->resolveProgramDivisionIdFromSls(
            $slsId,
            (int) $stateId,
            $pdComponent
        );

        if (!$programDivisionId) {
            return response()->json(['message' => 'No matching program division found.'], 404);
        }

        $budgetHead = BudgetHead::where('budget', $budget)->first();

        if (!$budgetHead) {
            return response()->json(['message' => 'Budget head not found.'], 404);
        }

        $query = DB::table('pdwise_aap_allocation')
            ->where('pd_id', $programDivisionId)
            ->where('bh_id', $budgetHead->id);

        $this->applyPdwiseAllocationFilters($query, $financialYear, $budgetPhase);

        $totalAmount = floatval($query->sum('amount') ?? 0);

        return response()->json([
            [
                'budget' => $budget,
                'amount' => $totalAmount,
                'category' => $budgetHead->category,
            ],
        ]);
    }

    /**
     * Get states and pd_and_sls_comp lookup for mother sanction bulk upload.
     * Used to resolve State Id and Full Program name from SLS/State/Program Division.
     */
    public function getBulkUploadLookup(Request $request)
    {
        try {
            $states = DB::table('states')->select('id', 'name')->orderBy('name')->get();
            $pdSlsComp = DB::table('pd_and_sls_comp as p')
                ->select('p.id', 'p.name', 'p.sls_code', 'p.full_sls_name', 'p.slsPD', 'p.state_id', 's.name as state_name')
                ->leftJoin('states as s', 'p.state_id', '=', 's.id')
                ->orderBy('p.name')
                ->get();
            $programDivisions = DB::table('md_program_divisions')
                ->select('division_id', 'division_name')
                ->where('is_active', 1)
                ->orderBy('division_name')
                ->get();
            return response()->json([
                'success' => true,
                'states' => $states,
                'pd_sls_comp' => $pdSlsComp,
                'program_divisions' => $programDivisions,
            ]);
        } catch (\Exception $e) {
            Log::error('getBulkUploadLookup error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Bulk insert preview rows into mother_sanction table (mapping from Excel preview columns).
     */
    public function bulkInsert(Request $request)
    {
        $request->validate(['rows' => 'required|array', 'rows.*' => 'array']);

        $rows = $request->input('rows', []);
        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'No rows to insert.'], 422);
        }

        $get = function (array $row, array $keys) {
            foreach ($keys as $k) {
                if (array_key_exists($k, $row) && $row[$k] !== '' && $row[$k] !== null) {
                    return $row[$k];
                }
            }
            return null;
        };

        // Find row value by key containing all of the given substrings (case-insensitive)
        $getByKeyPattern = function (array $row, array $substrings) {
            $lower = array_map('strtolower', $substrings);
            foreach ($row as $key => $val) {
                if ($val === '' || $val === null) continue;
                $keyLower = mb_strtolower((string) $key);
                if (count(array_filter($lower, fn ($s) => str_contains($keyLower, $s))) === count($lower)) {
                    return $val;
                }
            }
            return null;
        };

        $toLakhs = function ($val) {
            if ($val === null || $val === '') return 0;
            $str = (string) $val;
            $str = preg_replace('/[\$₹€£,\s]/u', '', $str);
            $str = preg_replace('/[^\d.-]/', '', $str);
            $n = $str === '' ? 0 : (float) $str;
            return round($n / 100000, 2);
        };

        $parseDate = function ($val) {
            if ($val === null || $val === '') return null;
            $str = trim((string) $val);
            if ($str === '') return null;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) return $str;
            if (is_numeric($str)) {
                $days = (int) (float) $str;
                $d = \DateTime::createFromFormat('Y-m-d', '1899-12-30');
                if ($d) $d->modify('+' . $days . ' days');
                return $d ? $d->format('Y-m-d') : null;
            }
            $d = date_create_from_format('d-m-Y', $str)
                ?: date_create_from_format('d/m/Y', $str)
                ?: date_create_from_format('Y-m-d', $str)
                ?: date_create_from_format('d-M-Y', $str)
                ?: date_create_from_format('d M Y', $str)
                ?: @date_create($str);
            return $d ? $d->format('Y-m-d') : null;
        };

        $financialYearFromDate = function ($dateStr) {
            if (!$dateStr) return null;
            $d = date_create($dateStr);
            if (!$d) return null;
            $y = (int) $d->format('Y');
            $m = (int) $d->format('m');
            return $m >= 4 ? "{$y}-" . ($y + 1) : ($y - 1) . "-{$y}";
        };

        $inserted = 0;
        $errors = [];
        $carried = [
            'stateId' => null,
            'motherSanctionNumber' => null,
            'sanctionDate' => null,
            'slsName' => null,
            'pdComponent' => null,
            'totalMsAmount' => null,
            'statusText' => null,
            'status' => 1,
            'budgetHead' => null,
        ];

        try {
            DB::beginTransaction();
            foreach ($rows as $index => $row) {
                $stateId = $get($row, ['State Id', 'state_id']) ?? $carried['stateId'];
                $motherSanctionNumber = $get($row, ['Mother Sanction Number', 'ifd_no', 'ky_ms_no']) ?? $carried['motherSanctionNumber'];
                $sanctionDateVal = $get($row, ['Mother Sanction Date', 'sanction_date']);
                $parsed = $parseDate($sanctionDateVal);
                $sanctionDate = $parsed ?? $carried['sanctionDate'];
                if ($sanctionDate === null) {
                    $sanctionDate = date('Y-m-d');
                }
                $slsRaw = $get($row, ['sls', 'SLS', 'sls_name']);
                $slsName = null;
                if ($slsRaw !== null && $slsRaw !== '') {
                    $slsName = strpos($slsRaw, '-') !== false
                        ? trim(substr($slsRaw, strpos($slsRaw, '-') + 1))
                        : trim((string) $slsRaw);
                }
                $slsName = $slsName ?? $carried['slsName'];
                $pdComponent = $get($row, ['Full Program division name', 'pd_component']) ?? $carried['pdComponent'];
                $totalMsAmount = $get($row, ['Mother Sanction Amount (Fund released)', 'total_mother_sanction_amount'])
                    ?? $getByKeyPattern($row, ['mother sanction amount', 'fund released'])
                    ?? $carried['totalMsAmount'];
                $budgetHead = $get($row, ['Budget_head', 'Budget head', 'budget_head'])
                    ?? $getByKeyPattern($row, ['budget'])
                    ?? $carried['budgetHead'];
                $allocationType = $get($row, ['Allocation_Type', 'Allocation Type', 'category']);
                $allocationAmount = $get($row, ['Allocation_Amount', 'Allocation Amount', 'mother_sanction_amount'])
                    ?? $getByKeyPattern($row, ['allocation_amount', 'allocation amount']);
                $carryForward = $get($row, ['Carry Forward Amount', 'carry_forward_amount'])
                    ?? $getByKeyPattern($row, ['carry', 'forward']);
                $statusText = $get($row, ['Status', 'status', 'STATUS']) ?? $carried['statusText'];
                $remarkText = $statusText !== null ? (string) $statusText : '';

                $status = 1;
                if ($statusText !== null && $statusText !== '') {
                    $status = (stripos((string) $statusText, 'active') !== false) ? 1 : 0;
                }

                if (!$stateId || !$motherSanctionNumber || $budgetHead === null || $budgetHead === '') {
                    $errors[] = "Row " . ($index + 1) . ": missing State Id, Mother Sanction Number, or Budget_head.";
                    continue;
                }

                $carried['stateId'] = $stateId;
                $carried['motherSanctionNumber'] = $motherSanctionNumber;
                $carried['sanctionDate'] = $sanctionDate;
                $carried['slsName'] = $slsName;
                $carried['pdComponent'] = $pdComponent;
                $carried['totalMsAmount'] = $totalMsAmount;
                $carried['statusText'] = $statusText;
                $carried['status'] = $status;
                $carried['budgetHead'] = $budgetHead;

                $financialYear = $financialYearFromDate($sanctionDate) ?? '';

                $data = [
                    'financial_year' => $financialYear,
                    'state_id' => (int) $stateId,
                    'ms_sequence_no' => '1',
                    'file_no' => '',
                    'ifd_no' => $motherSanctionNumber,
                    'sanction_date' => $sanctionDate,
                    'ky_ms_no' => $motherSanctionNumber,
                    'sls_name' => $slsName ?? '',
                    'pd_component' => $pdComponent ?? '',
                    'total_mother_sanction_amount' => $toLakhs($totalMsAmount),
                    'budget_head' => trim((string) $budgetHead),
                    'category' => $allocationType !== null ? trim((string) $allocationType) : '',
                    'available_fund' => 0,
                    'mother_sanction_amount' => $toLakhs($allocationAmount),
                    'carry_forward_amount' => $toLakhs($carryForward),
                    'uc_received_from_State' => '',
                    'signed_copy_of_mother_sanction' => '',
                    'status' => $status,
                    'action_type' => 'FRESH_CREATE',
                    'last_id' => rand(10, 99),
                    'remark' => $remarkText,
                ];

                MotherSanction::create($data);
                $inserted++;
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => $inserted . ' record(s) inserted successfully.',
                'inserted' => $inserted,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mother sanction bulk insert error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'inserted' => $inserted,
                'errors' => $errors,
            ], 500);
        }
    }

    /**
     * Get sum of mother_sanction_amount for a given budget_head and pd_component
     */
    public function getMotherSanctionReleasedAmount(Request $request)
    {
        try {
            $budgetHead = $request->query('budget_head');
            $pdComponent = $request->query('pd_component');
            $stateId = $request->query('state_id');
            $slsName = $request->query('sls_name');
            $financialYear = $request->query('financial_year');

            if (!$budgetHead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Budget head is required',
                    'total_released' => 0
                ], 400);
            }

            $query = DB::table('mother_sanction')
                ->whereRaw('TRIM(budget_head) = TRIM(?)', [$budgetHead])
                ->where('status', 1)
                ->whereNotNull('mother_sanction_amount');

            if ($stateId) {
                $query->where('state_id', $stateId);
            }

            if ($slsName) {
                $query->where('sls_name', $slsName);
            }

            if ($financialYear) {
                $yearVariants = $this->normalizeFinancialYearVariants($financialYear);
                if (!empty($yearVariants)) {
                    $query->whereIn('financial_year', $yearVariants);
                }
            }

            if ($pdComponent) {
                $query->whereRaw('TRIM(pd_component) COLLATE utf8mb4_unicode_ci = TRIM(?) COLLATE utf8mb4_unicode_ci', [$pdComponent]);
            }
            
            $totalReleased = $query->sum('mother_sanction_amount');

            return response()->json([
                'success' => true,
                'total_released' => floatval($totalReleased ?? 0)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching mother sanction released amount: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch released amount',
                'total_released' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Net mother sanction amount for list totals (excludes carry forward when it is baked into MS amount).
     */
    private function netMotherSanctionAmountForTotal(object $record): float
    {
        $ms = floatval($record->mother_sanction_amount ?? 0);
        $carryForward = floatval($record->carry_forward_amount ?? 0);

        if (($record->action_type ?? '') === 'REVISED') {
            return $ms - $carryForward;
        }

        return $ms;
    }

   
public function list()
{
    try {
        // Get all records with relations and join with pd_and_sls_comp table
        $data = DB::table('mother_sanction as ms')
            ->select([
                'ms.*',
                's.name as state_name',
                'pdc.sls_code'
            ])
            ->join('states as s', 'ms.state_id', '=', 's.id')
            ->leftJoin('pd_and_sls_comp as pdc', function($join) {
                $join->on('ms.sls_name', '=', 'pdc.name')
                     ->on('ms.state_id', '=', 'pdc.state_id');
            })
            // Show all records regardless of status
            ->orderBy('ms.created_at', 'desc')
            ->get();

        // Group data by state_id and sls_code to get all budget heads
        $groupedData = $data->groupBy(function($item) {
            return ($item->state_id ?? '') . '|' . ($item->sls_code ?? '');
        });

        // Transform the grouped data
        $transformedData = $groupedData->map(function($group) {
            $pickLatestBatch = function ($records) {
                return $records
                    ->groupBy('ky_ms_no')
                    ->sortByDesc(fn($batch) => $batch->max('created_at'))
                    ->first() ?? collect();
            };

            // Show the latest active mother sanction batch; older revisions remain inactive in DB
            $activeRecords = $group->filter(fn($item) => (int) ($item->status ?? 0) === 1);
            $isActive = $activeRecords->isNotEmpty();
            $displayRecords = $pickLatestBatch($isActive ? $activeRecords : $group);
            $firstItem = $displayRecords->sortByDesc('created_at')->first() ?? $group->first();

            // Budget head amounts come from the displayed batch only (not summed across inactive revisions)
            $budgetHeadMap = [];
            foreach ($displayRecords as $item) {
                if (empty($item->budget_head)) {
                    continue;
                }

                $budgetHeadMap[$item->budget_head] = [
                    'budget_head' => $item->budget_head,
                    'category' => $item->category,
                    'available_fund' => floatval($item->available_fund ?? 0),
                    'mother_sanction_amount' => floatval($item->mother_sanction_amount ?? 0),
                    'expenditure' => 0,
                    'carry_forward_amount' => floatval($item->carry_forward_amount ?? 0),
                ];
            }

            $stateId = $firstItem->state_id;

            // Expenditure spans all mother sanctions for this state + SLS (including prior revisions)
            $kyMsNos = $group->pluck('ky_ms_no')->unique()->filter()->values();

            $annualAllocationByBudgetHead = $this->getAnnualAllocationByBudgetHead(
                $firstItem->sls_name,
                $firstItem->state_id,
                $firstItem->pd_component,
                array_keys($budgetHeadMap),
                $firstItem->financial_year
            );

            $budgetHeads = collect($budgetHeadMap)->map(function($budgetData) use ($kyMsNos, $stateId, $annualAllocationByBudgetHead, $group) {
                $expenditure = DB::table('daily_sanction')
                    ->whereIn('mother_sanction', $kyMsNos->toArray())
                    ->where('budget_head', $budgetData['budget_head'])
                    ->where('state_id', $stateId)
                    ->sum('center_share_amount');

                $budgetData['expenditure'] = floatval($expenditure ?? 0);
                $budgetData['annual_allocation_individual'] = $annualAllocationByBudgetHead[$budgetData['budget_head']] ?? 0.0;
                $budgetData['total_ms_amount'] = floatval(
                    $group
                        ->filter(fn($item) => ($item->budget_head ?? '') === $budgetData['budget_head'])
                        ->sum(fn($item) => $this->netMotherSanctionAmountForTotal($item))
                );

                return $budgetData;
            })->sortBy('budget_head', SORT_NATURAL)->values();

            $totalAmount = $displayRecords->sum('mother_sanction_amount');
            $totalAvailableFund = $displayRecords->sum('available_fund');
            $annualAllocation = array_sum($annualAllocationByBudgetHead);

            $displayKyMsNo = $firstItem->ky_ms_no ?? '';
            $kyMsNoList = $displayKyMsNo ? [$displayKyMsNo] : [];

            $hasInactiveRecords = $group->contains(fn($item) => (int) ($item->status ?? 0) === 0);
            $isRevised = $kyMsNos->count() > 1 || ($isActive && $hasInactiveRecords);

            return [
                'id' => $firstItem->id,
                'financial_year' => $firstItem->financial_year,
                'state_id' => $firstItem->state_id,
                'ms_sequence_no' => $firstItem->ms_sequence_no,
                'file_no' => $firstItem->file_no,
                'ifd_no' => $firstItem->ifd_no,
                'sanction_date' => $firstItem->sanction_date,
                'ky_ms_no' => $displayKyMsNo,
                'ky_ms_no_list' => $kyMsNoList,
                'sls_name' => $firstItem->sls_name,
                'pd_component' => $firstItem->pd_component,
                'remark' => $firstItem->remark,
                'effective_total_ms' => $totalAmount,
                'total_mother_sanction_amount' => $totalAmount,
                'is_revised' => $isRevised,
                'total_available_fund' => $totalAvailableFund,
                'annual_allocation' => $annualAllocation,
                'budget_heads' => $budgetHeads,
                'uc_received_from_State' => $firstItem->uc_received_from_State,
                'signed_copy_of_mother_sanction' => $firstItem->signed_copy_of_mother_sanction,
                'last_id' => $firstItem->last_id,
                'status' => $isActive ? 'active' : 'inactive',
                'action_type' => $firstItem->action_type ?? 'FRESH_CREATE',
                'created_at' => $firstItem->created_at,
                'updated_at' => $firstItem->updated_at,
                'state' => [
                    'id' => $firstItem->state_id,
                    'name' => $firstItem->state_name
                ],
                'sls_code' => $firstItem->sls_code
            ];
        })->values();

        // Log some debug information
        Log::info('MotherSanction list query executed', [
            'total_records' => $transformedData->count(),
            'sample_record' => $transformedData->first() ? [
                'ky_ms_no' => $transformedData->first()['ky_ms_no'],
                'sls_name' => $transformedData->first()['sls_name'],
                'pd_component' => $transformedData->first()['pd_component'],
                'sls_code' => $transformedData->first()['sls_code'],
                'budget_heads_count' => count($transformedData->first()['budget_heads'])
            ] : null
        ]);

        return response()->json($transformedData);
    } catch (\Exception $e) {
        Log::error('Error in MotherSanction list method', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'error' => 'An error occurred while fetching data',
            'message' => $e->getMessage()
        ], 500);
    }
}

public function listReport(Request $request)
{
    $query = MotherSanction::with('state')
        ->where('status', 1)
        ->orderBy('created_at', 'desc');

    // Filtering
    if ($request->filled('year')) {
        $query->where('financial_year', $request->year);
    }
    if ($request->filled('program_division')) {
        $query->where('pd_component', $request->program_division);
    }
    if ($request->filled('state_id')) {
        $query->where('state_id', $request->state_id);
    }
    if ($request->filled('sanction_date')) {
        $query->where('sanction_date', $request->sanction_date);
    }

    $data = $query->get();

    return response()->json($data);
}

// public function listReport(Request $request)
//     {
//         // Get latest record per group of `last_id`
//         $subQuery = DB::table('mother_sanction')
//             ->select(DB::raw('MAX(id) as id'))
//             ->groupBy('last_id');

//         $query = MotherSanction::with('state')
//             ->whereIn('id', $subQuery)
//             ->orderBy('created_at', 'desc');

//         // Filtering
//         if ($request->filled('year')) {
//             $query->where('financial_year', $request->year);
//         }
//         if ($request->filled('state_id')) {
//             $query->where('state_id', $request->state_id);
//         }
//         if ($request->filled('sanction_date')) {
//             $query->where('sanction_date', $request->sanction_date);
//         }
//         $query->where('status', 1);
//         // Program Division filter using join with pd_and_sls_comp
//         if ($request->filled('program_division')) {
//             $programDivisionId = $request->program_division;
//             $query->whereExists(function($q) use ($programDivisionId) {
//                 $q->select(DB::raw(1))
//                     ->from('pd_and_sls_comp as pd')
//                     ->whereColumn('pd.name', 'mother_sanction.pd_component')
//                     ->where('pd.component', 'PD')
//                     ->where('pd.id', $programDivisionId);
//             });
//         }

//         $data = $query->get();

//         return response()->json($data);
//     }

    public function addMotherSanction(Request $request)
{
    // Frontend submits empty-string for optional file inputs when no file is selected.
    // Normalize these to null so Laravel's `nullable|file` validation passes.
    if ($request->has('uc_file_path') && $request->input('uc_file_path') === '') {
        $request->merge(['uc_file_path' => null]);
    }
    if ($request->has('signed_copy_path') && $request->input('signed_copy_path') === '') {
        $request->merge(['signed_copy_path' => null]);
    }

    // Validate the request data
    $validated = $request->validate([
        'financial_year' => 'required|string',
        'state_id' => 'required|integer',
        'ms_sequence_no' => 'required|string',
        'file_no' => 'nullable|string',
        'ifd_no' => 'required|string',
        'sanction_date' => 'required|date',
        'ky_ms_no' => 'required|string',
        'sls_name' => 'required|string',
        'pd_component' => 'required|string',
        'total_mother_sanction_amount' => 'required|numeric|min:0',
        'reappropriations' => 'required|json',
        'status' => 'required|in:0,1',
        'remark' => 'nullable|string',
        'uc_file_path' => 'nullable|file|max:10240|mimes:csv,pdf,png,jpg,jpeg|mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel,application/pdf,image/png,image/jpeg',
        'signed_copy_path' => 'nullable|file|max:10240|mimes:pdf,png,jpg,jpeg|mimetypes:application/pdf,image/png,image/jpeg',
    ]);

    try {
        // Debug: Log all request data
        Log::info('MotherSanction Request Data:', $request->all());
        Log::info('Files received:', $request->allFiles());

        $ucFilePath = '';
        $signedCopyPath = '';

        if ($request->hasFile('uc_file_path')) {
            $ucFilePath = $this->storeValidatedFile(
                $request->file('uc_file_path'),
                ['csv', 'pdf', 'png', 'jpg', 'jpeg']
            );
            Log::info('UC File stored at:', ['path' => $ucFilePath]);
        } else {
            Log::info('No UC file received');
        }

        if ($request->hasFile('signed_copy_path')) {
            $signedCopyPath = $this->storeValidatedFile(
                $request->file('signed_copy_path'),
                ['pdf', 'png', 'jpg', 'jpeg']
            );
            Log::info('Signed Copy stored at:', ['path' => $signedCopyPath]);
        } else {
            Log::info('No Signed Copy file received');
        }

        $sanitizedRemark = $this->sanitizeTextInput($request->remark);
        $sanitizedIfdNo = $this->sanitizeTextInput($request->ifd_no);
        $sanitizedKyMsNo = $this->sanitizeTextInput($request->ky_ms_no);
        $sanitizedSlsName = $this->sanitizeTextInput($request->sls_name);
        $sanitizedPdComponent = $this->sanitizeTextInput($request->pd_component);

        $createActionType = $this->resolveCreateActionType($request);

        $commonData = [
            'financial_year' => $request->financial_year,
            'state_id' => $request->state_id,
            'ms_sequence_no' => $request->ms_sequence_no,
            'file_no' => $request->filled('file_no') ? $request->file_no : '',
            'remark' => $sanitizedRemark,
            'ifd_no' => $sanitizedIfdNo,
            'sanction_date' => $request->sanction_date,
            'ky_ms_no' => $sanitizedKyMsNo,
            'sls_name' => $sanitizedSlsName,
            'pd_component' => $sanitizedPdComponent,
            'total_mother_sanction_amount' => $request->total_mother_sanction_amount,
            'uc_received_from_State' => $ucFilePath,
            'signed_copy_of_mother_sanction' => $signedCopyPath,
            'status' => $request->status,
            'action_type' => $createActionType,
            'last_id'=> rand(10, 99)
        ];

        Log::info('Common data to be inserted:', $commonData);

        $reappropriations = json_decode($request->reappropriations, true);

        if (!is_array($reappropriations) || empty($reappropriations)) {
            return response()->json([
                'message' => 'Invalid reappropriations data',
                'errors' => ['reappropriations' => ['Reappropriations data is required and must be valid.']]
            ], 422);
        }

        $lastInserted = null;

        foreach ($reappropriations as $row) {
            $safeBudgetHead = $this->sanitizeTextInput($row['budget_head'] ?? '');
            $safeCategory = $this->sanitizeTextInput($row['category'] ?? '');
            $safeAvailableAmount = is_numeric($row['available_amount'] ?? null) ? (float) $row['available_amount'] : 0;
            $safeSanctionAmount = is_numeric($row['sanction_amount'] ?? null) ? (float) $row['sanction_amount'] : 0;
            $safeCarryForward = is_numeric($row['carry_forward'] ?? null) ? (float) $row['carry_forward'] : 0;

            $sanction = MotherSanction::create(array_merge($commonData, [
                'budget_head' => $safeBudgetHead,
                'category' => $safeCategory,
                'available_fund' => $safeAvailableAmount,
                'mother_sanction_amount' => $safeSanctionAmount,
                'carry_forward_amount' => $safeCarryForward,
            ]));

            // Save history for creation
            $historyDescription = $createActionType === 'REVISED'
                ? 'Revised mother sanction record created'
                : 'New mother sanction record created';
            $this->saveHistory($sanction, $createActionType, $historyDescription);

            $lastInserted = $sanction; // Keep reference to the last inserted record
        }

        // Update the last inserted record with its own ID in 'last_id'
        /*if ($lastInserted) {
            $lastInserted->update([
                'last_id' => $lastInserted->id
            ]);
        }*/

        return response()->json([
            'message' => 'Data saved successfully',
            'last_id' => $lastInserted ? $lastInserted: null
        ]);
    } catch (\Exception $e) {
        Log::error('Error saving mother sanction:', ['error' => $e->getMessage()]);
        
        return response()->json([
            'message' => 'An error occurred while saving the data',
            'error' => $e->getMessage()
        ], 500);
    }
}

    private function sanitizeTextInput($value): string
    {
        if ($value === null) {
            return '';
        }

        $text = trim((string) $value);
        $text = strip_tags($text);
        // Remove control chars but keep newlines/tabs/spaces.
        $text = preg_replace('/[^\P{C}\n\r\t]/u', '', $text);

        return $text ?? '';
    }

    private function storeValidatedFile(UploadedFile $file, array $allowedExtensions): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'file' => ['Unsupported file extension.'],
            ]);
        }

        $detectedMime = mime_content_type($file->getRealPath()) ?: '';
        $clientMime = (string) $file->getClientMimeType();
        $allowedMimes = $this->getAllowedMimesForExtension($extension);

        if (!in_array($detectedMime, $allowedMimes, true) && !in_array($clientMime, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'file' => ['File MIME type does not match the allowed extension.'],
            ]);
        }

        $storedContent = file_get_contents($file->getRealPath());
        if ($storedContent === false) {
            throw ValidationException::withMessages([
                'file' => ['Unable to read uploaded file.'],
            ]);
        }

        if ($extension === 'csv') {
            $storedContent = $this->sanitizeCsvContent($file);
        }

        if ($extension === 'pdf') {
            app(SafePdfValidator::class)->assertSafe($storedContent, 'file');
        }

        $generatedName = uniqid('ms_', true) . '.' . $extension;
        $path = self::UPLOAD_DIR . '/' . $generatedName;
        Storage::disk(self::UPLOAD_DISK)->put($path, $storedContent);

        return $path;
    }

    private function getAllowedMimesForExtension(string $extension): array
    {
        return match ($extension) {
            'csv' => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
            'pdf' => ['application/pdf'],
            'png' => ['image/png'],
            'jpg', 'jpeg' => ['image/jpeg'],
            default => [],
        };
    }

    private function sanitizeCsvContent(UploadedFile $file): string
    {
        $input = fopen($file->getRealPath(), 'rb');
        if ($input === false) {
            throw ValidationException::withMessages([
                'uc_file_path' => ['Unable to parse CSV file.'],
            ]);
        }

        $firstLine = fgets($input);
        rewind($input);
        $delimiter = $this->detectCsvDelimiter($firstLine ?: '');

        $output = fopen('php://temp', 'w+b');
        if ($output === false) {
            fclose($input);
            throw ValidationException::withMessages([
                'uc_file_path' => ['Unable to sanitize CSV file.'],
            ]);
        }

        while (($row = fgetcsv($input, 0, $delimiter)) !== false) {
            $safeRow = array_map(function ($cell) {
                $cell = is_string($cell) ? str_replace("\0", '', $cell) : (string) $cell;
                if (preg_match('/^\s*[=\+\-@]/', $cell)) {
                    return "'" . $cell;
                }
                return $cell;
            }, $row);
            fputcsv($output, $safeRow, $delimiter);
        }

        rewind($output);
        $csv = stream_get_contents($output);

        fclose($input);
        fclose($output);

        return $csv === false ? '' : $csv;
    }

    private function detectCsvDelimiter(string $line): string
    {
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $bestCount = 0;

        foreach ($delimiters as $delimiter) {
            $count = substr_count($line, $delimiter);
            if ($count > $bestCount) {
                $bestCount = $count;
                $bestDelimiter = $delimiter;
            }
        }

        return $bestDelimiter;
    }

    public function motherSanctionData(Request $req){
      $query = MotherSanction::query();

    if ($req->filled('year')) {
        $query->where('financial_year', $req->year);
    }
    if ($req->filled('state_id')) {
        $query->where('state_id', $req->state_id);
    }
    if ($req->filled('sanction_date')) {
        $query->where('sanction_date', $req->sanction_date);
    }
    if ($req->filled('ky_ms_no')) {
        $query->where('ky_ms_no', $req->ky_ms_no);
    }

    $data = $query->orderBy('sanction_date')->get();

    return response()->json($data);

}

public function updateStatus(Request $request)
{
    try {
        // Accept either a single ky_ms_no (string) or an array of ky_ms_no values
        $request->validate([
            'ky_ms_no' => 'required', // Can be string or array
            // Added "close" as a valid action for full closure via Close button
            // Added "revise" as a valid action to add Available Fund to MS Amount
            'action' => 'required|in:deactivate,activate,close,revise'
        ]);

        $kyMsNoInput = $request->input('ky_ms_no');
        $action = $request->input('action');

        // Normalize to array: if string, convert to array; if already array, use as is
        $kyMsNos = is_array($kyMsNoInput) ? $kyMsNoInput : [$kyMsNoInput];
        
        // Filter out empty values
        $kyMsNos = array_filter($kyMsNos, function($value) {
            return !empty($value);
        });

        if (empty($kyMsNos)) {
            return response()->json([
                'message' => 'No valid KY MS No. provided.',
                'success' => false
            ], 400);
        }

        // Find all records with any of the provided ky_ms_no values
        $records = MotherSanction::whereIn('ky_ms_no', $kyMsNos)->get();

        if ($records->isEmpty()) {
            return response()->json([
                'message' => 'No records found with the given KY MS No(s).',
                'success' => false
            ], 404);
        }

        if ($action === 'deactivate') {
            // Deactivate all records with the provided ky_ms_no values (used by status toggle)
            DB::beginTransaction();
            
            $records = MotherSanction::whereIn('ky_ms_no', $kyMsNos)->get();
            
            foreach ($records as $record) {
                $this->saveHistory($record, 'DEACTIVATED', 'Record deactivated');

                $record->status = 0;
                $record->action_type = 'DEACTIVATED';
                $record->save();
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Records deactivated successfully',
                'success' => true,
                'updated_count' => $records->count()
            ]);
        } elseif ($action === 'activate') {
            // Activate all records with the provided ky_ms_no values
            DB::beginTransaction();
            
            $records = MotherSanction::whereIn('ky_ms_no', $kyMsNos)->get();
            
            foreach ($records as $record) {
                $this->saveHistory($record, 'ACTIVATED', 'Record activated');

                $record->status = 1;
                $record->action_type = 'ACTIVATED';
                $record->save();
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Records activated successfully',
                'success' => true,
                'updated_count' => $records->count()
            ]);
        } elseif ($action === 'revise') {
            // "Revise" action triggered from Revise button:
            //  - Set old data status to inactive
            //  - MS Amount = Current MS Amount + Available Fund (where Available Fund = MS Amount - Expenditure)
            //  - Available Fund = New MS Amount - Expenditure

            DB::beginTransaction();

            foreach ($records as $record) {
                // Calculate expenditure for this budget head exactly like in list()
                $expenditure = DB::table('daily_sanction')
                    ->where('mother_sanction', $record->ky_ms_no)
                    ->where('budget_head', $record->budget_head)
                    ->where('state_id', $record->state_id)
                    ->sum('center_share_amount');

                // Ensure numeric
                $expenditure = $expenditure ?: 0;

                // Get current MS Amount
                $currentMsAmount = floatval($record->mother_sanction_amount ?: 0);
                $oldAvailableFund = floatval($record->available_fund ?: 0);

                // Calculate current Available Fund as MS Amount - Expenditure (matching frontend calculation)
                $currentAvailableFund = $currentMsAmount - $expenditure;

                // Store the current Available Fund as Carry Forward Amount (this is what was added)
                $carryForwardAmount = $currentAvailableFund;

                // New MS Amount = Current MS Amount + Available Fund
                $newMsAmount = $currentMsAmount + $currentAvailableFund;

                // New Available Fund = New MS Amount - Expenditure
                $newAvailableFund = $newMsAmount - $expenditure;

                // Save history before update
                $this->saveHistory($record, 'REVISED', 
                    "Record revised. MS Amount: {$currentMsAmount} -> {$newMsAmount}, Available Fund: {$oldAvailableFund} -> {$newAvailableFund}",
                    $currentMsAmount, $newMsAmount, $oldAvailableFund, $newAvailableFund
                );

                // Set status to inactive for revise
                $record->status = 0;
                $record->action_type = 'REVISED';
                $record->mother_sanction_amount = $newMsAmount;
                $record->available_fund = $newAvailableFund;
                $record->carry_forward_amount = $carryForwardAmount;

                $record->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Records revised successfully. MS Amount has been updated and Available Fund recalculated.',
                'success' => true,
                'updated_count' => $records->count()
            ]);
        } else {
            // "Close" action triggered from Close button:
            //  - MS Amount should become equal to Expenditure
            //  - Available Fund should be added back to BE budget phase amount
            //  - Record is marked inactive/closed

            DB::beginTransaction();

            $financialYear = $request->input('financial_year');

            foreach ($records as $record) {
                // Calculate expenditure for this budget head exactly like in list()
                $expenditure = DB::table('daily_sanction')
                    ->where('mother_sanction', $record->ky_ms_no)
                    ->where('budget_head', $record->budget_head)
                    ->where('state_id', $record->state_id)
                    ->sum('center_share_amount');

                // Ensure numeric
                $expenditure = $expenditure ?: 0;

                $oldMsAmount = floatval($record->mother_sanction_amount ?: 0);
                $oldAvailableFund = floatval($record->available_fund ?: 0);

                // Get available fund to add back to BE
                $availableFundToReturn = $oldAvailableFund;

                // Find BudgetHead by budget string
                $budgetHead = BudgetHead::where('budget', $record->budget_head)->first();
                
                if ($budgetHead && $availableFundToReturn > 0) {
                    // Find or create BE budget phase for this budget head and financial year
                    $budgetPhase = BudgetPhase::where('budget_head_id', $budgetHead->id)
                        ->where('budget_phase', 'BE')
                        ->where('financial_year', $financialYear ?: $record->financial_year)
                        ->where('status', 1)
                        ->first();
                    
                    if ($budgetPhase) {
                        // Add back the available amount to BE budget phase
                        $budgetPhase->budget_amount = floatval($budgetPhase->budget_amount) + $availableFundToReturn;
                        $budgetPhase->save();
                    } else {
                        // Create BE budget phase if it doesn't exist
                        $budgetPhase = BudgetPhase::create([
                            'budget_head_id' => $budgetHead->id,
                            'budget_phase' => 'BE',
                            'financial_year' => $financialYear ?: $record->financial_year,
                            'budget_amount' => $availableFundToReturn,
                            'status' => 1,
                            'draft_flag' => 0
                        ]);
                    }
                }

                // MS Amount should be equivalent to Expenditure
                $record->mother_sanction_amount = $expenditure;

                // Available fund is considered returned to BE, so set to zero here
                $record->available_fund = 0;

                // Mark record as inactive/closed
                $record->status = 0;
                $record->action_type = 'CLOSED';

                // Save history before update
                $this->saveHistory($record, 'CLOSED', 
                    "Record closed. MS Amount: {$oldMsAmount} -> {$expenditure}, Available Fund: {$oldAvailableFund} -> 0 (returned to BE)",
                    $oldMsAmount, $expenditure, $oldAvailableFund, 0
                );

                $record->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Records closed successfully. MS Amount updated to match expenditure, available fund returned to BE, and status set to close.',
                'success' => true,
                'updated_count' => $records->count()
            ]);
        }

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Error updating mother sanction status:', [
            'error' => $e->getMessage(),
            'request' => $request->all()
        ]);
        
        return response()->json([
            'message' => 'An error occurred while updating status',
            'error' => $e->getMessage(),
            'success' => false
        ], 500);
    }
}

public function getMotherSanctionDetails(Request $request, $kyMsNo)
{
    try {
        $stateId = $request->query('state_id');

        $query = MotherSanction::where('ky_ms_no', $kyMsNo)
            ->where(function ($q) {
                $q->where('status', 1)->orWhere('status', '1');
            });

        if ($stateId) {
            $query->where('state_id', (int) $stateId);
        }

        $records = $query->with('state')->get();

        if ($records->isEmpty()) {
            return response()->json([
                'message' => 'No records found with the given KY MS No.',
                'success' => false
            ], 404);
        }

        $firstRecord = $records->first();
        
        // Get budget heads data
        $budgetHeads = $records->map(function($record) {
            return [
                'budget_head' => $record->budget_head,
                'category' => $record->category,
                'available_fund' => $record->available_fund,
                'mother_sanction_amount' => $record->mother_sanction_amount
            ];
        })->filter(function($item) {
            return !empty($item['budget_head']);
        })->values();

        return response()->json([
            'meta' => [
                'ky_ms_no' => $firstRecord->ky_ms_no,
                'financial_year' => $firstRecord->financial_year,
                'state_id' => $firstRecord->state_id,
                'state_name' => $firstRecord->state->name ?? '',
                'ms_sequence_no' => $firstRecord->ms_sequence_no,
                'sls_name' => $firstRecord->sls_name,
                'pd_component' => $firstRecord->pd_component,
                'ifd_no' => $firstRecord->ifd_no,
                'sanction_date' => $firstRecord->sanction_date,
                'remark' => $firstRecord->remark,
                'total_mother_sanction_amount' => $records->sum('mother_sanction_amount'),
                'total_available_fund' => $records->sum('available_fund')
            ],
            'entries' => $budgetHeads
        ]);

    } catch (\Exception $e) {
        Log::error('Error fetching mother sanction details:', [
            'error' => $e->getMessage(),
            'ky_ms_no' => $kyMsNo
        ]);
        
        return response()->json([
            'message' => 'An error occurred while fetching details',
            'error' => $e->getMessage(),
            'success' => false
        ], 500);
    }
}


    public function timeSeriesReport(Request $request)
    {
        $query = MotherSanction::with('state')
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
                        'mother_sanction_amount' => round($yearData->sum('mother_sanction_amount'), 2),
                        'available_fund' => round($yearData->sum('available_fund'), 2),
                        'total_mother_sanction_amount' => round($yearData->sum('total_mother_sanction_amount'), 2),
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
                    'mother_sanction_amount' => round($yearData->sum('mother_sanction_amount'), 2),
                    'available_fund' => round($yearData->sum('available_fund'), 2),
                    'total_mother_sanction_amount' => round($yearData->sum('total_mother_sanction_amount'), 2),
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

    /**
     * Resolve action_type for newly created mother sanction records.
     */
    private function resolveCreateActionType(Request $request): string
    {
        $requested = strtoupper((string) $request->input('action_type'));

        if ($request->boolean('is_revise') || in_array($requested, ['REVISE', 'REVISED'], true)) {
            return 'REVISED';
        }

        return 'FRESH_CREATE';
    }

    /**
     * Save history record for mother sanction changes
     */
    private function saveHistory($record, $actionType, $description = null, $oldMsAmount = null, $newMsAmount = null, $oldAvailableFund = null, $newAvailableFund = null)
    {
        $changedBy = Auth::check() ? Auth::user()->name : 'System';
        $nowIst = Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s');
        
        MotherSanctionHistory::create([
            'mother_sanction_id' => $record->id,
            'financial_year' => $record->financial_year,
            'state_id' => $record->state_id,
            'ms_sequence_no' => $record->ms_sequence_no,
            'file_no' => $record->file_no,
            'ifd_no' => $record->ifd_no,
            'sanction_date' => $record->sanction_date,
            'ky_ms_no' => $record->ky_ms_no,
            'sls_name' => $record->sls_name,
            'pd_component' => $record->pd_component,
            'total_mother_sanction_amount' => $record->total_mother_sanction_amount,
            'budget_head' => $record->budget_head,
            'category' => $record->category,
            'available_fund' => $record->available_fund,
            'mother_sanction_amount' => $record->mother_sanction_amount,
            'carry_forward_amount' => $record->carry_forward_amount,
            'uc_received_from_State' => $record->uc_received_from_State,
            'signed_copy_of_mother_sanction' => $record->signed_copy_of_mother_sanction,
            'last_id' => $record->last_id,
            'status' => $record->status,
            'remark' => $record->remark,
            'action_type' => $actionType,
            'changed_by' => $changedBy,
            'change_description' => $description,
            'old_mother_sanction_amount' => $oldMsAmount ?? $record->mother_sanction_amount,
            'new_mother_sanction_amount' => $newMsAmount ?? $record->mother_sanction_amount,
            'old_available_fund' => $oldAvailableFund ?? $record->available_fund,
            'new_available_fund' => $newAvailableFund ?? $record->available_fund,
            'history_timestamp' => $nowIst,
            'created_at' => $nowIst,
            'updated_at' => $nowIst,
        ]);
    }

    /**
     * Get mother sanction history list
     */
    public function historyList()
    {
        try {
            $history = DB::table('mother_sanction_history as msh')
                ->select([
                    'msh.*',
                    's.name as state_name',
                    'pdc.sls_code',
                ])
                ->join('states as s', 'msh.state_id', '=', 's.id')
                ->leftJoin('pd_and_sls_comp as pdc', function ($join) {
                    $join->on(DB::raw('msh.sls_name COLLATE utf8mb4_unicode_ci'), '=', DB::raw('pdc.name COLLATE utf8mb4_unicode_ci'))
                         ->on(DB::raw('msh.pd_component COLLATE utf8mb4_unicode_ci'), '=', DB::raw('pdc.slsPD COLLATE utf8mb4_unicode_ci'));
                })
                ->orderBy('msh.history_timestamp', 'desc')
                ->get();

            $transformedData = $history->map(function ($item) {
                $budgetHeads = [];
                if (!empty($item->budget_head)) {
                    $expenditure = DB::table('daily_sanction')
                        ->where('mother_sanction', $item->ky_ms_no)
                        ->where('budget_head', $item->budget_head)
                        ->where('state_id', $item->state_id)
                        ->sum('center_share_amount');

                    $annualAllocationByBudgetHead = $this->getAnnualAllocationByBudgetHead(
                        $item->sls_name,
                        $item->state_id,
                        $item->pd_component,
                        [$item->budget_head],
                        $item->financial_year
                    );

                    $budgetHeads[] = [
                        'budget_head' => $item->budget_head,
                        'category' => $item->category,
                        'annual_allocation_individual' => $annualAllocationByBudgetHead[$item->budget_head] ?? 0.0,
                        'mother_sanction_amount' => floatval($item->mother_sanction_amount ?? 0),
                        'expenditure' => floatval($expenditure ?? 0),
                        'available_fund' => floatval($item->available_fund ?? 0),
                        'carry_forward_amount' => floatval($item->carry_forward_amount ?? 0),
                    ];
                }

                return [
                    'id' => $item->history_id,
                    'financial_year' => $item->financial_year,
                    'state_id' => $item->state_id,
                    'ky_ms_no' => $item->ky_ms_no,
                    'sls_name' => $item->sls_name,
                    'sls_code' => $item->sls_code ?? '',
                    'pd_component' => $item->pd_component,
                    'sanction_date' => $item->sanction_date,
                    'budget_heads' => $budgetHeads,
                    'action_type' => $item->action_type,
                    'changed_by' => $item->changed_by,
                    'history_timestamp' => $item->history_timestamp
                        ? Carbon::parse($item->history_timestamp, 'Asia/Kolkata')->format('c')
                        : null,
                    'change_description' => $item->change_description,
                    'state' => [
                        'id' => $item->state_id,
                        'name' => $item->state_name ?? '',
                    ],
                ];
            })->values();

            return response()->json($transformedData);
        } catch (\Exception $e) {
            Log::error('Error fetching mother sanction history:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'An error occurred while fetching history',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return both short (2026-27) and long (2026-2027) financial year formats.
     *
     * @return array<int, string>
     */
    private function normalizeFinancialYearVariants(?string $year): array
    {
        if (empty($year)) {
            return [];
        }

        $year = trim($year);
        $variants = [$year];

        if (preg_match('/^\d{4}-\d{4}$/', $year)) {
            [$start, $end] = explode('-', $year);
            $variants[] = $start . '-' . substr($end, -2);
        } elseif (preg_match('/^\d{4}-\d{2}$/', $year)) {
            [$start, $end] = explode('-', $year);
            $variants[] = $start . '-20' . $end;
        }

        return array_values(array_unique($variants));
    }

    /**
     * Apply PD-wise allocation filters aligned with the PD wise Budget Allocation page.
     */
    private function applyPdwiseAllocationFilters($query, ?string $financialYear, ?string $budgetPhase, string $tableAlias = ''): void
    {
        $prefix = $tableAlias !== '' ? $tableAlias . '.' : '';

        $query->where($prefix . 'status', 1);

        if ($financialYear) {
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);
            if (!empty($yearVariants)) {
                $query->whereIn($prefix . 'financial_year', $yearVariants);
            }
        }

        $phase = $budgetPhase ?: 'BE';
        if ($phase !== '0') {
            if ($phase === 'BE') {
                $query->where(function ($phaseQuery) use ($prefix) {
                    $phaseQuery->where($prefix . 'budget_phase', 'BE')
                        ->orWhereNull($prefix . 'budget_phase');
                });
            } else {
                $query->where($prefix . 'budget_phase', $phase);
            }
        }
    }

    /**
     * Resolve program division ID from pd_component and/or SLS via pd_and_sls_comp.
     *
     * mother_sanction.pd_component may store either a program division name
     * (md_program_divisions.division_name) or the SLS PD label (pd_and_sls_comp.slsPD).
     */
    private function resolveProgramDivisionIdFromSls(
        ?string $slsName,
        ?int $stateId,
        ?string $pdComponent
    ): ?int {
        if ($pdComponent) {
            $divisionId = DB::table('md_program_divisions')
                ->whereRaw('division_name COLLATE utf8mb4_unicode_ci = ?', [trim($pdComponent)])
                ->value('division_id');

            if ($divisionId) {
                return (int) $divisionId;
            }
        }

        if (empty($slsName)) {
            return null;
        }

        $programDivisionQuery = DB::table('pd_and_sls_comp as psc')
            ->join('md_program_divisions as md', function ($join) {
                $join->on(
                    DB::raw('psc.slsPD COLLATE utf8mb4_unicode_ci'),
                    '=',
                    DB::raw('md.division_name COLLATE utf8mb4_unicode_ci')
                );
            })
            ->where('psc.name', $slsName);

        if ($stateId) {
            $programDivisionQuery->where('psc.state_id', $stateId);
        }

        if ($pdComponent) {
            $programDivisionQuery->where('psc.slsPD', $pdComponent);
        }

        $programDivisionId = $programDivisionQuery->value('md.division_id');

        return $programDivisionId ? (int) $programDivisionId : null;
    }

    /**
     * Get pdwise_aap_allocation amounts per budget head for the program division resolved via SLS.
     *
     * @return array<string, float>
     */
    private function getAnnualAllocationByBudgetHead(
        ?string $slsName,
        ?int $stateId,
        ?string $pdComponent,
        array $budgetHeadNames,
        ?string $financialYear = null,
        ?string $budgetPhase = 'BE'
    ): array {
        $result = array_fill_keys($budgetHeadNames, 0.0);

        if (empty($budgetHeadNames)) {
            return $result;
        }

        $programDivisionId = $this->resolveProgramDivisionIdFromSls($slsName, $stateId, $pdComponent);

        if (!$programDivisionId) {
            return $result;
        }

        $budgetHeadIdMap = DB::table('budget_heads')
            ->whereIn('budget', $budgetHeadNames)
            ->pluck('id', 'budget');

        if ($budgetHeadIdMap->isEmpty()) {
            return $result;
        }

        $allocationQuery = DB::table('pdwise_aap_allocation')
            ->where('pd_id', $programDivisionId)
            ->whereIn('bh_id', $budgetHeadIdMap->values());

        $this->applyPdwiseAllocationFilters($allocationQuery, $financialYear, $budgetPhase);

        $allocations = $allocationQuery
            ->select('bh_id', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('bh_id')
            ->pluck('total_amount', 'bh_id');

        foreach ($budgetHeadNames as $budgetHeadName) {
            $budgetHeadId = $budgetHeadIdMap[$budgetHeadName] ?? null;
            if ($budgetHeadId) {
                $result[$budgetHeadName] = floatval($allocations[$budgetHeadId] ?? 0);
            }
        }

        return $result;
    }

    /**
     * Sum pdwise_aap_allocation amounts for budget heads under the program division resolved via SLS.
     */
    private function calculateAnnualAllocationFromPdwiseAap(
        ?string $slsName,
        ?int $stateId,
        ?string $pdComponent,
        array $budgetHeadNames
    ): float {
        return array_sum($this->getAnnualAllocationByBudgetHead($slsName, $stateId, $pdComponent, $budgetHeadNames));
    }

}