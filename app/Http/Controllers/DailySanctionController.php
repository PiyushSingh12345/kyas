<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\MotherSanction;
use App\Models\DailySanction;
use App\Models\DailySanctionHistory;
use App\Models\SlsPDComponent;
use App\Models\State;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DailySanctionController extends Controller
{
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

    private const SAFE_TEXT_PATTERN = "/^[A-Za-z0-9\s\-\.,&()\/:'_]+$/";
    private const SAFE_BUDGET_HEAD_PATTERN = '/^(\d{15}|\d{4}\.\d{2}\.\d{3}\.\d{2}\.\d{2}\.\d{2})$/';
    
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

public function list(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 20), 50);
        $page = max(1, (int) $request->get('page', 1));

        $subQuery = DB::table('daily_sanction')
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('daily_sanction_no');

        $query = DailySanction::with(['state', 'slsComponent'])
            ->whereIn('id', $subQuery)
            ->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $items = $paginator->getCollection();

        if ($items->isEmpty()) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ]);
        }

        $dailySanctionNos = $items->pluck('daily_sanction_no')->unique()->values()->all();
        $stateIds = $items->pluck('state_id')->unique()->values()->all();

        // Aggregates only for current page's state_id and daily_sanction_no (reduces query load)
        $stateAmounts = DB::table('daily_sanction')
            ->select('state_id', DB::raw('SUM(center_share_amount) as total_amount'))
            ->whereIn('state_id', $stateIds)
            ->groupBy('state_id')
            ->pluck('total_amount', 'state_id')
            ->toArray();

        $dailySanctionAmounts = DB::table('daily_sanction')
            ->select('daily_sanction_no', DB::raw('SUM(center_share_amount) as total_amount'))
            ->whereIn('daily_sanction_no', $dailySanctionNos)
            ->groupBy('daily_sanction_no')
            ->pluck('total_amount', 'daily_sanction_no')
            ->toArray();

        $motherSanctionAmounts = DB::table('daily_sanction')
            ->select('daily_sanction_no', DB::raw('SUM(mother_sanction_amount) as total_amount'))
            ->whereIn('daily_sanction_no', $dailySanctionNos)
            ->groupBy('daily_sanction_no')
            ->pluck('total_amount', 'daily_sanction_no')
            ->toArray();

        // Single query for all budget heads on current page (avoids N+1)
        $budgetRows = DB::table('daily_sanction')
            ->whereIn('daily_sanction_no', $dailySanctionNos)
            ->select('daily_sanction_no', 'budget_head', 'center_share_amount')
            ->get();

        $budgetHeadsByNo = [];
        foreach ($budgetRows as $row) {
            $budgetHeadsByNo[$row->daily_sanction_no][] = [
                'budget_head' => $row->budget_head,
                'daily_sanction_amount' => $row->center_share_amount,
            ];
        }

        $data = $items->map(function ($item) use ($stateAmounts, $dailySanctionAmounts, $motherSanctionAmounts, $budgetHeadsByNo) {
            $item->full_sls_name = $item->slsComponent ? $item->slsComponent->full_sls_name : null;
            $item->sls_pd = $item->slsComponent ? $item->slsComponent->slsPD : null;
            $item->state_total_amount = $stateAmounts[$item->state_id] ?? 0;
            $item->daily_sanction_total_amount = $dailySanctionAmounts[$item->daily_sanction_no] ?? 0;
            $item->mother_sanction_total_amount = $motherSanctionAmounts[$item->daily_sanction_no] ?? 0;
            $item->budget_heads = $budgetHeadsByNo[$item->daily_sanction_no] ?? [];
            return $item;
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
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
            'financial_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{2,4}$/'],
            'state_id' => 'required|integer',
            'ds_date' => 'required|date',
            'daily_sanction_no' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN, 'unique:daily_sanction,daily_sanction_no'],
            'mother_sanction' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
            'ifd_no' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
            'sls_name' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
            'entries' => 'required|array|min:1',
            'entries.*.budget_head' => ['required', 'string', 'regex:' . self::SAFE_BUDGET_HEAD_PATTERN],
            'entries.*.mother_sanction_amount' => 'required|numeric',
            'entries.*.available_amount' => 'required|numeric',
            'entries.*.center_share_amount' => 'required|numeric',
            'remark' => ['nullable', 'string', 'max:1000', 'regex:' . self::SAFE_TEXT_PATTERN],
        ], [
            'financial_year.regex' => 'Financial year format must be like 2025-26.',
            'daily_sanction_no.regex' => 'Daily sanction number contains invalid special characters.',
            'mother_sanction.regex' => 'Mother sanction no contains invalid special characters.',
            'ifd_no.regex' => 'IFD no contains invalid special characters.',
            'sls_name.regex' => 'SLS name contains invalid special characters.',
            'entries.*.budget_head.regex' => 'Budget head format is invalid.',
            'remark.regex' => 'Remark contains invalid special characters.',
        ]);

        Log::info('Daily Sanction Validation Passed', ['validated_data' => $validated]);

        $safeDailySanctionNo = $this->sanitizeTextInput($validated['daily_sanction_no'] ?? '');
        $safeMotherSanction = $this->sanitizeTextInput($validated['mother_sanction'] ?? '');
        $safeIfdNo = $this->sanitizeTextInput($validated['ifd_no'] ?? '');
        $safeSlsName = $this->sanitizeTextInput($validated['sls_name'] ?? '');
        $safeRemark = $this->sanitizeTextInput($validated['remark'] ?? $request->remark);

        foreach ($validated['entries'] as $entry) {
            $safeBudgetHead = $this->sanitizeTextInput($entry['budget_head'] ?? '');
            $record = DailySanction::create([
                'financial_year' => $validated['financial_year'],
                'state_id' => $validated['state_id'],
                'ds_date' => $validated['ds_date'],
                'daily_sanction_no' => $safeDailySanctionNo,
                'mother_sanction' => $safeMotherSanction,
                'ifd_no' => $safeIfdNo,
                'sls_name' => $safeSlsName,
                'budget_head' => $safeBudgetHead,
                'mother_sanction_amount' => $entry['mother_sanction_amount'],
                'available_amount' => $entry['available_amount'],
                'center_share_amount' => $entry['center_share_amount'],
                'remark' => $safeRemark,
                'status' => 1
            ]);
            $this->saveDailySanctionHistory($record, 'CREATE', 'New daily sanction entry created');
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

    /**
     * Read an uploaded Excel file into an array of sheets (same shape as Maatwebsite Excel::toArray).
     * Uses PhpSpreadsheet when [excel] is not bound (e.g. server package not discovered); otherwise Maatwebsite Excel.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return array<int, array<int, array<int, mixed>>>
     */
    private function readExcelToSheets($file): array
    {
        // When [excel] is not bound (e.g. maatwebsite/excel not discovered on server), use PhpSpreadsheet directly
        // so we never trigger "Target class [excel] does not exist."
        if (! app()->bound('excel')) {
            return $this->readExcelWithPhpSpreadsheet($file);
        }

        try {
            return Excel::toArray([], $file);
        } catch (\Throwable $e) {
            Log::warning('Excel facade failed, falling back to PhpSpreadsheet: ' . $e->getMessage());
            return $this->readExcelWithPhpSpreadsheet($file);
        }
    }

    /**
     * Read uploaded Excel using PhpSpreadsheet (no dependency on Maatwebsite Excel binding).
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return array<int, array<int, array<int, mixed>>>
     */
    private function readExcelWithPhpSpreadsheet($file): array
    {
        $path = $file->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $sheets = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheets[] = $sheet->toArray(null, true, true, false);
        }
        // Normalize to 0-based row/column keys to match Excel::toArray
        foreach ($sheets as $si => $rows) {
            $normalized = [];
            foreach ($rows as $row) {
                $normalized[] = array_values($row);
            }
            $sheets[$si] = $normalized;
        }
        return $sheets;
    }

    /**
     * Parse uploaded Excel file (SPARSH format) and return preview for daily sanction bulk upload.
     * Ignores Excel lines 1–7 (rows 0–6). Line 8 = table headers, line 9+ = data rows.
     * Metadata (lines 1–7) is still read for header_data (state, from_date, etc.) for display and bulk store.
     */
    public function uploadPreview(Request $request)
    {
        // Allow larger memory and time for big Excel files so response is always valid JSON
        $previousMemory = ini_get('memory_limit');
        $previousTime = ini_get('max_execution_time');
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240',
            ]);

            $file = $request->file('file');
            $sheets = $this->readExcelToSheets($file);
            $sheet = $sheets[0] ?? null;

            if (!$sheet || count($sheet) < 9) {
                return response()->json([
                    'success' => false,
                    'message' => 'Excel file has insufficient rows. Need at least row 8 (headers) and row 9+ (data).',
                    'rows' => [],
                    'header_data' => null,
                ], 422);
            }

            /* Normalize sheet so every row has dense 0-based keys (0,1,2,...) - avoids blank data from sparse/associative rows */
            $sheet = $this->normalizeSheetToDenseRows($sheet);


            /* 1) Drill3_basic format (grouped layout -> flat preview); has header at row 9 */
            $basicResult = $this->parseDrill3BasicFormat($sheet);
            if ($basicResult !== null) {
                $enriched = $this->enrichPreviewRows($basicResult['rows'], $basicResult['columns']);
                $basicResult['rows'] = $enriched['rows'];
                $basicResult['columns'] = $enriched['columns'];
                return response()->json($basicResult);
            }

            // Try strict parser that reads headers at row 8 and data from row 9
            $strictResult = $this->parseStartingAtRowNine($sheet);
            if ($strictResult !== null) {
                $enriched = $this->enrichPreviewRows($strictResult['rows'], $strictResult['columns']);
                $strictResult['rows'] = $enriched['rows'];
                $strictResult['columns'] = $enriched['columns'];
                return response()->json($strictResult);
            }

            /* 2) Simple format: row 0 = title, rows 3–6 = metadata, row 7 = table headers, row 8+ = data (like readExcel) */
            $simpleResult = $this->parseSimpleExcelFormat($sheet);
            if ($simpleResult !== null) {
                $enriched = $this->enrichPreviewRows($simpleResult['rows'], $simpleResult['columns']);
                $simpleResult['rows'] = $enriched['rows'];
                $simpleResult['columns'] = $enriched['columns'];
                return response()->json($simpleResult);
            }

            /* 3) Fallback: Header/metadata from rows 0–8, detect header row */
            $headerData = [
                'report_title' => isset($sheet[0][0]) ? trim((string) $sheet[0][0]) : null,
                'financial_year' => trim((string) ($sheet[1][1] ?? '')),
                'state' => trim((string) ($sheet[2][1] ?? '')),
                'scheme_css' => trim((string) ($sheet[3][1] ?? '')),
                'scheme_sls' => trim((string) ($sheet[4][1] ?? '')),
                'from_date' => trim((string) ($sheet[5][1] ?? '')),
                'to_date' => trim((string) ($sheet[5][3] ?? '')),
                'isdbt_payment_mode' => trim((string) ($sheet[6][1] ?? '')),
                'figures_in' => trim((string) ($sheet[7][1] ?? '')),
                'total_sanction' => isset($sheet[8][1]) ? trim((string) $sheet[8][1]) : null,
            ];
            if ($headerData['total_sanction'] === '' || !is_numeric(str_replace([',', ' '], '', $headerData['total_sanction']))) {
                $headerData['total_sanction'] = null;
            }

            /* Find table header row: skip Grand Total/Total rows; use first row that looks like SPARSH headers */
            $tableHeaders = [];
            $dataStartIndex = 8;
            $headerRowIndex = null;
            for ($r = 7; $r < min(count($sheet), 20); $r++) {
                $candidate = $sheet[$r] ?? [];
                if ($this->isTotalOrGrandTotalRow($candidate)) {
                    continue;
                }
                if ($this->rowLooksLikeTableHeader($candidate)) {
                    $headerRowIndex = $r;
                    break;
                }
            }
            if ($headerRowIndex !== null) {
                $rawHeaderRow = $sheet[$headerRowIndex] ?? [];
                $dataStartIndex = $headerRowIndex + 1;
            } else {
                $rawHeaderRow = $sheet[7] ?? [];
                $dataStartIndex = 8;
            }

            /* Find actual table start: first column with header or data so we don't misalign */
            $endCol = 9;
            $headerKeys = array_keys($rawHeaderRow);
            if (!empty($headerKeys)) {
                $endCol = max($endCol, is_numeric($headerKeys[array_key_last($headerKeys)]) ? (int) $headerKeys[array_key_last($headerKeys)] : count($rawHeaderRow) - 1);
            }
            for ($c = 0; $c < 30; $c++) {
                $v = trim((string) ($rawHeaderRow[$c] ?? ''));
                if ($v !== '') {
                    $endCol = max($endCol, $c);
                }
            }
            for ($r = $dataStartIndex; $r < min($dataStartIndex + 15, count($sheet)); $r++) {
                $dataRow = $sheet[$r] ?? [];
                if ($this->isTotalOrGrandTotalRow($dataRow)) {
                    continue;
                }
                for ($c = 0; $c < 30; $c++) {
                    $v = trim((string) ($dataRow[$c] ?? ''));
                    if ($v !== '' && $c > $endCol) {
                        $endCol = $c;
                    }
                }
            }
            $startCol = 0;
            for ($c = 0; $c <= min($endCol, 29); $c++) {
                $v = trim((string) ($rawHeaderRow[$c] ?? ''));
                if ($v !== '') {
                    $startCol = $c;
                    break;
                }
            }
            for ($r = $dataStartIndex; $r < min($dataStartIndex + 10, count($sheet)); $r++) {
                $dataRow = $sheet[$r] ?? [];
                for ($c = 0; $c <= min($endCol, 29); $c++) {
                    $v = trim((string) ($dataRow[$c] ?? ''));
                    if ($v !== '' && $c < $startCol) {
                        $startCol = $c;
                        break 2;
                    }
                }
            }
            $numCols = $endCol - $startCol + 1;
            $numCols = max(10, min(30, $numCols));

            /* Build header row from startCol so column indices align with data */
            $tableHeaders = [];
            for ($c = 0; $c < $numCols; $c++) {
                $tableHeaders[] = $rawHeaderRow[$startCol + $c] ?? null;
            }
            $tableHeaders = array_values($tableHeaders);

            /* Normalize headers: empty cells get default SPARSH names by index so Vue and fill-down get correct keys */
            $tableHeaders = $this->normalizeTableHeadersWithDefaults($tableHeaders);
            if (!empty($tableHeaders)) {
                $used = [];
                foreach ($tableHeaders as $idx => $h) {
                    if (isset($used[$h])) {
                        $tableHeaders[$idx] = $h . '_' . $idx;
                    } else {
                        $used[$h] = true;
                    }
                }
            }

            /* Extract TABLE DATA: slice each row from startCol so header and data align */
            $tableData = [];
            for ($i = $dataStartIndex; $i < count($sheet); $i++) {
                $row = $sheet[$i] ?? [];
                if (empty(array_filter($row))) {
                    continue;
                }
                if ($this->isTotalOrGrandTotalRow($row)) {
                    continue;
                }
                $sliced = [];
                for ($c = 0; $c < count($tableHeaders); $c++) {
                    $sliced[] = $row[$startCol + $c] ?? null;
                }
                $padded = array_pad($sliced, count($tableHeaders), null);
                $combined = @array_combine($tableHeaders, $padded);
                if ($combined === false) {
                    continue;
                }
                $tableData[] = $combined;
            }

            /* Fill down: SLS Scheme, S. No., Daily Sanction Number, IsDBT, Sanction Date, Sanction Status, Object Head - so empty cells get value from previous row until new data comes */
            $tableData = $this->fillDownGroupedColumns($tableData, $tableHeaders);

            $enriched = $this->enrichPreviewRows($tableData, $tableHeaders);
            $tableData = $enriched['rows'];
            $tableHeaders = $enriched['columns'];

            return response()->json([
                'success' => true,
                'message' => count($tableData) . ' row(s) parsed from Excel.',
                'header_data' => $headerData,
                'columns' => $tableHeaders,
                'rows' => $tableData,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            @ini_set('memory_limit', $previousMemory);
            @set_time_limit($previousTime);
            throw $e;
        } catch (\Throwable $e) {
            @ini_set('memory_limit', $previousMemory);
            @set_time_limit($previousTime);
            Log::error('Daily sanction bulk upload preview error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse Excel: ' . $e->getMessage(),
                'rows' => [],
                'header_data' => null,
            ], 422);
        }
    }

    /**
     * Simple Excel read (like readExcel): row 0 = report title, rows 3–6 = metadata, row 7 = table headers, row 8+ = data.
     * Returns response in the format: { header_data: { report_title, state, scheme_css, from_date, to_date, total_sanction }, columns, rows }.
     * Returns null if sheet does not have at least 9 rows.
     */
    private function parseSimpleExcelFormat(array $sheet): ?array
    {
        if (count($sheet) < 9) {
            return null;
        }

        /* 1. Extract HEADER / METADATA */
        $headerData = [
            'report_title'   => isset($sheet[0][0]) ? trim((string) $sheet[0][0]) : null,
            'state'          => isset($sheet[3][1]) ? trim((string) $sheet[3][1]) : null,
            'scheme_css'     => isset($sheet[4][1]) ? trim((string) $sheet[4][1]) : null,
            'from_date'      => isset($sheet[5][1]) ? trim((string) $sheet[5][1]) : null,
            'to_date'        => isset($sheet[5][3]) ? trim((string) $sheet[5][3]) : null,
            'total_sanction' => isset($sheet[6][1]) ? trim((string) $sheet[6][1]) : null,
        ];

        /* 2. Extract TABLE HEADERS (row 8 in Excel = index 7) - preserve column indices, use default names for empty */
        $rawHeaderRow = $sheet[7] ?? [];
        $numCols = 10;
        for ($c = 0; $c < 30; $c++) {
            $v = trim((string) ($rawHeaderRow[$c] ?? ''));
            if ($v !== '') {
                $numCols = max($numCols, $c + 1);
            }
        }
        for ($r = 8; $r < min(23, count($sheet)); $r++) {
            $dataRow = $sheet[$r] ?? [];
            for ($c = 0; $c < 30; $c++) {
                $v = trim((string) ($dataRow[$c] ?? ''));
                if ($v !== '' && $c >= $numCols) {
                    $numCols = $c + 1;
                }
            }
        }
        $numCols = min(30, max(10, $numCols));
        $tableHeaders = [];
        for ($c = 0; $c < $numCols; $c++) {
            $h = trim(str_replace(["\r", "\n"], ' ', (string) ($rawHeaderRow[$c] ?? '')));
            $tableHeaders[] = $h !== '' ? $h : null;
        }
        $tableHeaders = $this->normalizeTableHeadersWithDefaults($tableHeaders);

        /* 3. Extract TABLE DATA (row 9 onward = index 8+) - align by column index so all data shows */
        $tableData = [];
        for ($i = 8; $i < count($sheet); $i++) {
            $row = $sheet[$i] ?? [];
            if (empty(array_filter($row))) {
                continue;
            }
            if ($this->isTotalOrGrandTotalRow($row)) {
                continue;
            }
            $sliced = [];
            for ($c = 0; $c < count($tableHeaders); $c++) {
                $sliced[] = $row[$c] ?? null;
            }
            $padded = array_pad($sliced, count($tableHeaders), null);
            $combined = @array_combine($tableHeaders, $padded);
            if ($combined === false) {
                continue;
            }
            $tableData[] = $combined;
        }

        /* Fill down grouped columns so SLS Scheme, S. No., etc. repeat until new data */
        $tableData = $this->fillDownGroupedColumns($tableData, $tableHeaders);
        $tableData = $this->formatPreviewRowsToMatchOutput($tableData);

        return [
            'success'     => true,
            'message'     => count($tableData) . ' row(s) parsed.',
            'header_data' => $headerData,
            'columns'     => $tableHeaders,
            'rows'        => $tableData,
        ];
    }

    /**
     * Parse SPARSH_03_Drill3_basic.xlsx format and return data in SPARSH_03_Drill3_basic_preview.xlsx format.
     * Basic has grouped rows (group header + detail rows with only Function Head & Sanction Amount). Preview is flat: one row per detail with fill-down.
     * Returns null if sheet does not match this format.
     */
    private function parseDrill3BasicFormat(array $sheet): ?array
    {
        $maxCol = 25;
        $headerRow = null;
        $dataStartRow = 9;
        foreach ([8, 7] as $headerIndex) {
            if (!isset($sheet[$headerIndex])) {
                continue;
            }
            $candidate = $sheet[$headerIndex];
            $indices = $this->detectDrill3ColumnIndices($candidate, $maxCol);
            if (isset($indices['Function Head']) && isset($indices['Sanction Amount']) && isset($indices['SLS Scheme'])) {
                $headerRow = $candidate;
                $dataStartRow = $headerIndex + 1;
                break;
            }
        }
        if ($headerRow === null) {
            $headerRow = $sheet[8] ?? [];
            $dataStartRow = 9;
        }
        $row8 = $headerRow;
        $colIndices = $this->detectDrill3ColumnIndices($headerRow, $maxCol);
        $idxSNo = $colIndices['S.No. (SLS)'] ?? 1;
        $idxSLSScheme = $colIndices['SLS Scheme'] ?? 2;
        $idxFunctionHead = $colIndices['Function Head'] ?? 12;
        $idxSanctionAmount = $colIndices['Sanction Amount'] ?? 13;
        $h1 = trim(str_replace(["\r", "\n"], ' ', (string) ($row8[$idxSNo] ?? '')));
        $h2 = trim(str_replace(["\r", "\n"], ' ', (string) ($row8[$idxSLSScheme] ?? '')));
        $h12 = trim(str_replace(["\r", "\n"], ' ', (string) ($row8[$idxFunctionHead] ?? '')));
        $h13 = trim(str_replace(["\r", "\n"], ' ', (string) ($row8[$idxSanctionAmount] ?? '')));
        $hasSNo = stripos($h1, 'S.No') !== false || stripos($h1, 'SLS') !== false;
        $hasSLSScheme = stripos($h2, 'SLS') !== false && stripos($h2, 'Scheme') !== false;
        $hasFunctionHead = stripos($h12, 'Function') !== false || stripos($h12, 'Head') !== false;
        $hasSanctionAmount = (stripos($h13, 'Sanction') !== false && stripos($h13, 'Amount') !== false) || stripos($h13, 'Amount') !== false;
        if (!$hasSNo || !$hasSLSScheme || !$hasFunctionHead || !$hasSanctionAmount) {
            return null;
        }

        $headerData = [
            'report_title' => trim((string) ($sheet[0][0] ?? '')),
            'financial_year' => trim((string) ($sheet[2][1] ?? '')),
            'state' => trim((string) ($sheet[2][10] ?? '')),
            'scheme_css' => trim((string) ($sheet[3][3] ?? '')),
            'scheme_sls' => trim((string) ($sheet[3][10] ?? '')),
            'from_date' => trim((string) ($sheet[4][3] ?? '')),
            'to_date' => trim((string) ($sheet[4][10] ?? '')),
            'isdbt_payment_mode' => trim((string) ($sheet[5][3] ?? '')),
            'figures_in' => trim((string) ($sheet[5][10] ?? '')),
            'total_sanction' => trim((string) ($sheet[6][3] ?? '')),
        ];

        $previewColumns = [
            'S.No. (SLS)',
            'SLS Scheme',
            'S. No. (Sanction)',
            'Daily Sanction Number',
            'IsDBT',
            'Sanction Date',
            'Sanction Status',
            'Object Head',
            'Function Head',
            'Sanction Amount',
        ];

        $colMap = [];
        foreach ($previewColumns as $name) {
            $idx = $colIndices[$name] ?? null;
            if ($idx !== null) {
                $colMap[$idx] = $name;
            }
        }
        $defaultMap = [1 => 'S.No. (SLS)', 2 => 'SLS Scheme', 4 => 'S. No. (Sanction)', 5 => 'Daily Sanction Number', 6 => 'IsDBT', 8 => 'Sanction Date', 9 => 'Sanction Status', 11 => 'Object Head', 12 => 'Function Head', 13 => 'Sanction Amount'];
        foreach ($defaultMap as $c => $name) {
            if (!isset($colMap[$c])) {
                $colMap[$c] = $name;
            }
        }
        $groupCols = array_unique(array_keys($colMap));
        sort($groupCols);
        $groupCols = array_values(array_filter($groupCols, function ($c) use ($colMap) {
            $name = $colMap[$c];
            return $name !== 'Function Head' && $name !== 'Sanction Amount';
        }));
        $funcHeadCol = $idxFunctionHead;
        $sanctionAmountCol = $idxSanctionAmount;

        $context = [];
        foreach ($previewColumns as $name) {
            if ($name !== 'Function Head' && $name !== 'Sanction Amount') {
                $context[$name] = '';
            }
        }
        $rows = [];
        $sno = 0;
        $scanCols = max($maxCol, $funcHeadCol, $sanctionAmountCol) + 1;

        for ($i = $dataStartRow; $i < count($sheet); $i++) {
            $row = $sheet[$i] ?? [];
            if (empty(array_filter($row))) {
                continue;
            }
            if ($this->isTotalOrGrandTotalRow($row)) {
                for ($c = 0; $c < $scanCols; $c++) {
                    $v = trim((string) ($row[$c] ?? ''));
                    if ($v !== '' && isset($colMap[$c])) {
                        $context[$colMap[$c]] = $v;
                    }
                }
                continue;
            }
            $m = trim((string) ($row[$funcHeadCol] ?? ''));
            $n = trim((string) ($row[$sanctionAmountCol] ?? ''));
            if (stripos($m, 'Total (Sanction)') !== false || stripos($m, 'Total(Sanction)') !== false) {
                for ($c = 0; $c < $scanCols; $c++) {
                    $v = trim((string) ($row[$c] ?? ''));
                    if ($v !== '' && isset($colMap[$c])) {
                        $context[$colMap[$c]] = $v;
                    }
                }
                continue;
            }
            $funcHead = preg_replace('/[^0-9]/', '', $m);
            $amount = preg_replace('/[^0-9.]/', '', $n);
            if (strlen($funcHead) < 10 || !is_numeric($amount)) {
                for ($c = 0; $c < $scanCols; $c++) {
                    $v = trim((string) ($row[$c] ?? ''));
                    if ($v !== '' && isset($colMap[$c])) {
                        $context[$colMap[$c]] = $v;
                    }
                }
                continue;
            }
            $sno++;
            foreach ($groupCols as $c) {
                $v = trim((string) ($row[$c] ?? ''));
                if ($v !== '' && isset($colMap[$c])) {
                    $context[$colMap[$c]] = $v;
                }
            }
            $objectHead = $context['Object Head'] ?? '';
            $out = [
                'S.No. (SLS)' => (string) $sno,
                'SLS Scheme' => $context['SLS Scheme'] ?? '',
                'S. No. (Sanction)' => $context['S. No. (Sanction)'] ?? '',
                'Daily Sanction Number' => $context['Daily Sanction Number'] ?? '',
                'IsDBT' => $context['IsDBT'] ?? '',
                'Sanction Date' => $context['Sanction Date'] ?? '',
                'Sanction Status' => $context['Sanction Status'] ?? '',
                'Object Head' => $objectHead,
                'Function Head' => $this->formatBudgetHead($m, $objectHead),
                'Sanction Amount' => number_format((float) preg_replace('/[^0-9.]/', '', $n), 2, '.', ''),
            ];
            $rows[] = $out;
        }

        $rows = $this->fillDownGroupedColumns($rows, $previewColumns);

        return [
            'success' => true,
            'message' => count($rows) . ' row(s) parsed (Drill3 basic format).',
            'header_data' => $headerData,
            'columns' => $previewColumns,
            'rows' => $rows,
        ];
    }

    /**
     * Detect column indices for Drill3 format by matching header text in the header row.
     * Returns associative array: field name => column index (0-based).
     */
    private function detectDrill3ColumnIndices(array $headerRow, int $maxCol): array
    {
        $out = [];
        $patterns = [
            'S.No. (SLS)' => ['s.no', 'sls', 'sno'],
            'SLS Scheme' => ['sls scheme', 'slsscheme'],
            'S. No. (Sanction)' => ['s. no. (sanction)', 's no sanction', 'sanction)'],
            'Daily Sanction Number' => ['daily sanction number', 'daily sanction'],
            'IsDBT' => ['isdbt', 'is dbt'],
            'Sanction Date' => ['sanction date', 'date'],
            'Sanction Status' => ['sanction status', 'status'],
            'Object Head' => ['object head', 'objecthead'],
            'Function Head' => ['function head', 'functionhead'],
            'Sanction Amount' => ['sanction amount', 'amount'],
        ];
        for ($c = 0; $c <= $maxCol; $c++) {
            $cell = trim(str_replace(["\r", "\n"], ' ', (string) ($headerRow[$c] ?? '')));
            if ($cell === '') {
                continue;
            }
            $lower = strtolower($cell);
            foreach ($patterns as $name => $keywords) {
                if (isset($out[$name])) {
                    continue;
                }
                foreach ($keywords as $kw) {
                    if (strpos($lower, $kw) !== false) {
                        $out[$name] = $c;
                        break 2;
                    }
                }
            }
        }
        return $out;
    }

    /**
     * Parse sheet using explicit rule: headers at row 8 (index 7), data from row 9 (index 8).
     * Forward-fill blank cells from previous non-empty value, remove total/grand total rows,
     * and return typed preview rows matching requested shape.
     */
    private function parseStartingAtRowNine(array $sheet): ?array
    {
        if (count($sheet) < 9) {
            return null;
        }

        $headerRow = $sheet[7] ?? [];

        $endCol = 9;
        for ($c = 0; $c < 30; $c++) {
            $v = trim((string) ($headerRow[$c] ?? ''));
            if ($v !== '') {
                $endCol = max($endCol, $c);
            }
        }
        for ($r = 8; $r < min(23, count($sheet)); $r++) {
            $dataRow = $sheet[$r] ?? [];
            for ($c = 0; $c < 30; $c++) {
                $v = trim((string) ($dataRow[$c] ?? ''));
                if ($v !== '' && $c > $endCol) {
                    $endCol = $c;
                }
            }
        }
        $startCol = 0;
        for ($c = 0; $c <= min($endCol, 29); $c++) {
            $v = trim((string) ($headerRow[$c] ?? ''));
            if ($v !== '') {
                $startCol = $c;
                break;
            }
        }
        for ($r = 8; $r < min(18, count($sheet)); $r++) {
            $dataRow = $sheet[$r] ?? [];
            for ($c = 0; $c <= min($endCol, 29); $c++) {
                $v = trim((string) ($dataRow[$c] ?? ''));
                if ($v !== '' && $c < $startCol) {
                    $startCol = $c;
                    break 2;
                }
            }
        }
        $numCols = $endCol - $startCol + 1;
        $numCols = min(50, max(10, $numCols));

        $tableHeaders = [];
        for ($c = 0; $c < $numCols; $c++) {
            $h = trim(str_replace(["\r", "\n"], ' ', (string) ($headerRow[$startCol + $c] ?? '')));
            $tableHeaders[] = $h !== '' ? $h : null;
        }
        $tableHeaders = $this->normalizeTableHeadersWithDefaults($tableHeaders);

        // blank markers
        $blankMarkers = ['', '—', '-', 'na', 'n/a', 'nil', 'none', '--'];

        $rows = [];
        $lastValues = array_fill(0, count($tableHeaders), null);

        for ($i = 8; $i < count($sheet); $i++) {
            $row = $sheet[$i] ?? [];
            if (empty(array_filter($row))) {
                continue;
            }
            if ($this->isTotalOrGrandTotalRow($row)) {
                continue;
            }
            $sliced = [];
            for ($c = 0; $c < count($tableHeaders); $c++) {
                $cell = $row[$startCol + $c] ?? null;
                $trim = trim((string) $cell);
                $isBlank = $cell === null || $trim === '' || in_array(strtolower($trim), $blankMarkers, true);
                if ($isBlank) {
                    $cell = $lastValues[$c] ?? null;
                } else {
                    $lastValues[$c] = $cell;
                }
                $sliced[$tableHeaders[$c]] = $cell;
            }
            $rows[] = $sliced;
        }

        if (empty($rows)) {
            return null;
        }

        // Map header keys to desired output keys
        $mapKeys = [];
        foreach ($tableHeaders as $h) {
            $lower = strtolower(trim((string) $h));
            if (strpos($lower, 's.no') !== false && strpos($lower, 'sls') !== false) {
                $mapKeys[$h] = "S.No.\n(SLS)";
            } elseif (strpos($lower, 'sls') !== false && strpos($lower, 'scheme') !== false) {
                $mapKeys[$h] = 'SLS Scheme';
            } elseif ((strpos($lower, 's. no') !== false || strpos($lower, 's.no') !== false) && strpos($lower, 'sanction') !== false) {
                $mapKeys[$h] = "S. No.\n(Sanction)";
            } elseif (strpos($lower, 'daily') !== false && strpos($lower, 'sanction') !== false) {
                $mapKeys[$h] = 'Daily Sanction Number';
            } elseif (strpos($lower, 'isdbt') !== false || strpos($lower, 'dbt') !== false) {
                $mapKeys[$h] = 'IsDBT';
            } elseif (strpos($lower, 'sanction date') !== false || strpos($lower, 'date') !== false) {
                $mapKeys[$h] = 'Sanction Date';
            } elseif (strpos($lower, 'sanction status') !== false || strpos($lower, 'status') !== false) {
                $mapKeys[$h] = 'Sanction Status';
            } elseif (strpos($lower, 'object') !== false && strpos($lower, 'head') !== false) {
                $mapKeys[$h] = 'Object Head';
            } elseif (strpos($lower, 'function') !== false && strpos($lower, 'head') !== false) {
                $mapKeys[$h] = 'Function Head';
            } elseif (strpos($lower, 'sanction') !== false && strpos($lower, 'amount') !== false) {
                $mapKeys[$h] = 'Sanction Amount';
            } else {
                $mapKeys[$h] = $h;
            }
        }

        $outRows = [];
        foreach ($rows as $r) {
            $o = [];
            // helper to get value by header original
            $get = function ($orig) use ($r) {
                return $r[$orig] ?? null;
            };

            // S.No. (SLS)
            $snoVal = null;
            foreach ($mapKeys as $orig => $target) {
                if ($target === "S.No.\n(SLS)") {
                    $v = $get($orig);
                    $vnum = preg_replace('/[^0-9]/', '', (string) $v);
                    $snoVal = $vnum === '' ? null : (int) $vnum;
                    break;
                }
            }
            $o["S.No.\n(SLS)"] = $snoVal;

            // SLS Scheme
            $slsScheme = null;
            foreach ($mapKeys as $orig => $target) {
                if ($target === 'SLS Scheme') {
                    $slsScheme = trim((string) ($get($orig) ?? ''));
                    break;
                }
            }
            $o['SLS Scheme'] = $slsScheme;

            // S. No. (Sanction)
            $sanNo = null;
            foreach ($mapKeys as $orig => $target) {
                if ($target === "S. No.\n(Sanction)") {
                    $v = $get($orig);
                    $vnum = preg_replace('/[^0-9]/', '', (string) $v);
                    $sanNo = $vnum === '' ? null : (int) $vnum;
                    break;
                }
            }
            $o["S. No.\n(Sanction)"] = $sanNo;

            // Daily Sanction Number
            $dsNum = null;
            foreach ($mapKeys as $orig => $target) {
                if ($target === 'Daily Sanction Number') {
                    $dsNum = trim((string) ($get($orig) ?? ''));
                    break;
                }
            }
            $o['Daily Sanction Number'] = $dsNum;

            // IsDBT
            $isdbt = null;
            foreach ($mapKeys as $orig => $target) {
                if ($target === 'IsDBT') {
                    $isdbt = trim((string) ($get($orig) ?? ''));
                    break;
                }
            }
            $o['IsDBT'] = $isdbt;

            // Sanction Date
            $sdate = null;
            foreach ($mapKeys as $orig => $target) {
                if ($target === 'Sanction Date') {
                    $sdate = trim((string) ($get($orig) ?? ''));
                    break;
                }
            }
            $o['Sanction Date'] = $sdate;

            // Sanction Status
            $sstatus = null;
            foreach ($mapKeys as $orig => $target) {
                if ($target === 'Sanction Status') {
                    $sstatus = trim((string) ($get($orig) ?? ''));
                    break;
                }
            }
            $o['Sanction Status'] = $sstatus;

            // Object Head
            $objHead = null;
            foreach ($mapKeys as $orig => $target) {
                if ($target === 'Object Head') {
                    $v = $get($orig);
                    $vnum = preg_replace('/[^0-9]/', '', (string) $v);
                    $objHead = $vnum === '' ? null : (int) $vnum;
                    break;
                }
            }
            $o['Object Head'] = $objHead;

            // Function Head: concatenate digits from function head + object head
            $funcHead = null;
            $funcRaw = '';
            foreach ($mapKeys as $orig => $target) {
                if ($target === 'Function Head') {
                    $funcRaw = (string) ($get($orig) ?? '');
                    break;
                }
            }
            $funcDigits = preg_replace('/[^0-9]/', '', $funcRaw . ($objHead !== null ? (string) $objHead : ''));
            $funcHead = $funcDigits === '' ? null : (int) $funcDigits;
            $o['Function Head'] = $funcHead;

            // Sanction Amount
            $sAmount = null;
            foreach ($mapKeys as $orig => $target) {
                if ($target === 'Sanction Amount') {
                    $v = $get($orig);
                    $num = preg_replace('/[^0-9.]/', '', (string) $v);
                    if ($num === '') {
                        $sAmount = null;
                    } else {
                        // remove decimals for preview integer
                        $sAmount = (int) floor((float) $num);
                    }
                    break;
                }
            }
            $o['Sanction Amount'] = $sAmount;

            $outRows[] = $o;
        }

        return [
            'success' => true,
            'message' => count($outRows) . ' row(s) parsed (row-9 strict parser).',
            'header_data' => null,
            'columns' => array_values(array_unique(array_values($mapKeys))),
            'rows' => $outRows,
        ];
    }

    /**
     * Normalize sheet so every row has dense 0-based integer keys (0, 1, 2, ...) up to max column.
     * Ensures $row[$c] is reliable and avoids blank data when Excel returns sparse or associative rows.
     */
    private function normalizeSheetToDenseRows(array $sheet): array
    {
        $maxCol = 0;
        foreach ($sheet as $row) {
            if (!is_array($row)) {
                continue;
            }
            $keys = array_keys($row);
            foreach ($keys as $k) {
                if (is_numeric($k)) {
                    $maxCol = max($maxCol, (int) $k);
                }
            }
        }
        $maxCol = min(50, max($maxCol + 1, 14));
        $out = [];
        foreach ($sheet as $i => $row) {
            if (!is_array($row)) {
                $out[$i] = $row;
                continue;
            }
            $dense = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $dense[$c] = array_key_exists($c, $row) ? $row[$c] : null;
            }
            $out[$i] = $dense;
        }
        return $out;
    }

    /**
     * Replace empty/null headers with default SPARSH column names by index so row keys match what the frontend expects.
     * Deduplicate so each header is unique.
     */
    private function normalizeTableHeadersWithDefaults(array $tableHeaders): array
    {
        $defaults = [
            0 => 'S.No. (SLS)',
            1 => 'SLS Scheme',
            2 => 'S. No. (Sanction)',
            3 => 'Daily Sanction Number',
            4 => 'IsDBT',
            5 => 'Sanction Date',
            6 => 'Sanction Status',
            7 => 'Object Head',
            8 => 'Function Head',
            9 => 'Sanction Amount',
        ];
        $normalized = [];
        foreach ($tableHeaders as $idx => $h) {
            $s = trim((string) $h);
            $normalized[] = $s !== '' ? $s : ($defaults[$idx] ?? 'Column_' . $idx);
        }
        $used = [];
        foreach ($normalized as $idx => $h) {
            if (isset($used[$h])) {
                $normalized[$idx] = $h . '_' . $idx;
            } else {
                $used[$h] = true;
            }
        }
        return $normalized;
    }

    /**
     * Fill down grouped columns: SLS Scheme, S. No., Daily Sanction Number, IsDBT, Sanction Date, Sanction Status, Object Head.
     * When a cell is blank, use the value from the previous row in that column until a new non-blank value is found.
     * SLS Scheme is explicitly included: if SLS Scheme is blank, take from the previous cell of that column.
     */
    private function fillDownGroupedColumns(array $tableData, array $tableHeaders): array
    {
        $fillDownNames = [
            's.no. (sls)',
            'sls scheme',
            'slsscheme',
            's. no. (sanction)',
            'daily sanction number',
            'isdbt',
            'sanction date',
            'sanction status',
            'object head',
        ];

        // Columns we definitely should not auto-fill (amounts/totals)
        $excludeMarkers = ['amount', 'total', 'available', 'figure', 'center_share', 'mother_sanction'];

        $keysToFillDown = [];
        foreach ($tableHeaders as $headerKey) {
            $lower = strtolower(trim((string) $headerKey));
            $lower = preg_replace('/\s+/', ' ', $lower);
            $lower = preg_replace('/_\d+$/', '', $lower);

            foreach ($fillDownNames as $name) {
                if ($lower === $name || strpos($lower, $name) !== false || strpos($name, $lower) !== false) {
                    $keysToFillDown[$headerKey] = true;
                    break;
                }
            }

            // Also include any SLS/Scheme header variants
            if (!isset($keysToFillDown[$headerKey]) && strpos($lower, 'sls') !== false && strpos($lower, 'scheme') !== false) {
                $keysToFillDown[$headerKey] = true;
            }

            // If still not decided, include the header unless it looks like an amount/total column
            if (!isset($keysToFillDown[$headerKey])) {
                $isExclude = false;
                foreach ($excludeMarkers as $m) {
                    if (strpos($lower, $m) !== false) {
                        $isExclude = true;
                        break;
                    }
                }
                if (!$isExclude) {
                    $keysToFillDown[$headerKey] = true;
                }
            }
        }

        // Common blank markers we should consider empty
        $blankMarkers = ['', '—', '-', 'na', 'n/a', 'nil', 'none', '--'];

        // Forward-fill: for each row, if a target column is blank, take the last non-blank value above it
        for ($i = 1; $i < count($tableData); $i++) {
            foreach (array_keys($keysToFillDown) as $key) {
                $val = $tableData[$i][$key] ?? null;
                $trimmed = trim((string) $val);
                $isBlank = $val === null || $trimmed === '' || in_array(strtolower($trimmed), $blankMarkers, true);
                if ($isBlank) {
                    for ($p = $i - 1; $p >= 0; $p--) {
                        $prev = $tableData[$p][$key] ?? null;
                        $prevTrim = trim((string) $prev);
                        if ($prev !== null && $prevTrim !== '' && !in_array(strtolower($prevTrim), $blankMarkers, true)) {
                            $tableData[$i][$key] = $prev;
                            break;
                        }
                    }
                }
            }
        }

        // Back-fill: for each fill-down column, if the first row(s) are blank, fill them from the first non-blank value in that column
        foreach (array_keys($keysToFillDown) as $key) {
            $firstNonBlank = null;
            $firstNonBlankIndex = null;
            for ($j = 0; $j < count($tableData); $j++) {
                $v = $tableData[$j][$key] ?? null;
                $t = trim((string) $v);
                if ($v !== null && $t !== '' && !in_array(strtolower($t), $blankMarkers, true)) {
                    $firstNonBlank = $v;
                    $firstNonBlankIndex = $j;
                    break;
                }
            }
            if ($firstNonBlank !== null && $firstNonBlankIndex > 0) {
                for ($i = 0; $i < $firstNonBlankIndex; $i++) {
                    $tableData[$i][$key] = $firstNonBlank;
                }
            }
        }

        return $tableData;
    }

    /**
     * Return true if the row looks like the SPARSH data table header (S.No, SLS Scheme, Sanction Date, etc.).
     */
    private function rowLooksLikeTableHeader(array $row): bool
    {
        $nonEmpty = array_filter($row, function ($v) {
            return trim((string) $v) !== '';
        });
        if (count($nonEmpty) < 4) {
            return false;
        }
        $concat = ' ' . strtolower(implode(' ', array_map('strval', $row)));
        $markers = ['s.no', 'sls scheme', 'sanction date', 'sanction amount', 'function head', 'object head'];
        $found = 0;
        foreach ($markers as $m) {
            if (strpos($concat, $m) !== false) {
                $found++;
            }
        }
        return $found >= 2;
    }

    /**
     * Return true if the row is a Grand Total, Total ( ... ), or Total (Sanction) row (to be excluded from data).
     */
    private function isTotalOrGrandTotalRow(array $row): bool
    {
        foreach ($row as $cell) {
            $s = trim((string) $cell);
            if ($s === '') {
                continue;
            }
            $lower = strtolower($s);
            if (strpos($lower, 'grand total') !== false) {
                return true;
            }
            if ($lower === 'total (sanction)') {
                return true;
            }
            if (preg_match('/^total\s*\(\s*[^)]+\s*\)\s*:?\s*$/i', $s)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Normalize sanction date from Excel (e.g. 17-Jul-2025) to Y-m-d.
     */
    private function normalizeSanctionDate($value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }
        $value = trim((string) $value);
        $d = \DateTime::createFromFormat('d-M-Y', $value)
            ?: \DateTime::createFromFormat('d-m-Y', $value)
            ?: \DateTime::createFromFormat('Y-m-d', $value)
            ?: \DateTime::createFromFormat('d/m/Y', $value);
        return $d ? $d->format('Y-m-d') : $value;
    }

    /**
     * Normalize amount (handle numeric or string; if large assume rupees and convert to lakhs).
     */
    private function normalizeAmount($value): float
    {
        $num = is_numeric($value) ? (float) $value : (float) preg_replace('/[^0-9.-]/', '', (string) $value);
        if ($num >= 100000) {
            $num = round($num / 100000, 2);
        }
        return round($num, 2);
    }

    /**
     * Format Function Head + Object Head to budget head code (e.g. 3601.06.101.45.00.31).
     */
    private function formatBudgetHead(string $functionHead, string $objectHead): string
    {
        $digits = preg_replace('/[^0-9]/', '', $functionHead);
        if (strlen($digits) < 10) {
            return $functionHead . ($objectHead !== '' ? '.' . $objectHead : '');
        }
        $parts = [
            substr($digits, 0, 4),
            substr($digits, 4, 2),
            substr($digits, 6, 3),
            substr($digits, 9, 2),
            substr($digits, 11, 2),
        ];
        $code = implode('.', $parts);
        return $objectHead !== '' ? $code . '.' . $objectHead : $code;
    }

    /**
     * Format preview rows to match output: Function Head as dotted (e.g. 3601.06.101.45.00.31), Sanction Amount as string with 2 decimals (e.g. 390346.00).
     */
    private function formatPreviewRowsToMatchOutput(array $rows): array
    {
        if (empty($rows)) {
            return $rows;
        }
        $first = $rows[0];
        $funcHeadKey = null;
        $objectHeadKey = null;
        $sanctionAmountKey = null;
        foreach (array_keys($first) as $k) {
            $lower = strtolower(trim((string) $k));
            $lower = preg_replace('/_\d+$/', '', $lower);
            if ($funcHeadKey === null && strpos($lower, 'function') !== false && strpos($lower, 'head') !== false) {
                $funcHeadKey = $k;
            }
            if ($objectHeadKey === null && strpos($lower, 'object') !== false && strpos($lower, 'head') !== false) {
                $objectHeadKey = $k;
            }
            if ($sanctionAmountKey === null && (strpos($lower, 'sanction') !== false && strpos($lower, 'amount') !== false || strpos($lower, 'amount') !== false)) {
                $sanctionAmountKey = $k;
            }
        }
        foreach ($rows as $i => $row) {
            if ($funcHeadKey !== null && $objectHeadKey !== null && isset($row[$funcHeadKey])) {
                $fh = trim((string) $row[$funcHeadKey]);
                $oh = trim((string) ($row[$objectHeadKey] ?? ''));
                if ($fh !== '' && preg_match('/[0-9]/', $fh)) {
                    $rows[$i][$funcHeadKey] = $this->formatBudgetHead($fh, $oh);
                }
            }
            if ($sanctionAmountKey !== null && isset($row[$sanctionAmountKey])) {
                $amt = (float) preg_replace('/[^0-9.]/', '', (string) $row[$sanctionAmountKey]);
                $rows[$i][$sanctionAmountKey] = number_format($amt, 2, '.', '');
            }
        }
        return $rows;
    }

    /**
     * Get value from a preview row by trying multiple possible key names (different parsers use different keys).
     */
    private function getPreviewRowValue(array $row, array $possibleKeys): string
    {
        foreach ($possibleKeys as $k) {
            $v = $row[$k] ?? null;
            if ($v !== null && trim((string) $v) !== '') {
                return trim((string) $v);
            }
        }
        return '';
    }

    /**
     * Enrich preview rows with: Financial Year, State Id, Mother Sanction No., IFd No, Mother Sanction Amount, Available Amount.
     */
    private function enrichPreviewRows(array $rows, array $columns): array
    {
        $newColumns = [
            'SLS Name',
            'Financial Year',
            'State Id',
            'Mother Sanction No.',
            'IFd No',
            'Mother Sanction Amount',
            'Available Amount',
        ];
        $allColumns = array_merge($columns, $newColumns);
        $slsSchemeCache = [];
        $motherSanctionCache = [];
        $motherSanctionShownForScheme = [];

        foreach ($rows as $i => $row) {
            $slsScheme = $this->getPreviewRowValue($row, ['SLS Scheme', 'SLS scheme']);
            $slsName = '';
            if ($slsScheme !== '') {
                $pos = strpos($slsScheme, '-');
                $slsName = $pos !== false ? trim(substr($slsScheme, $pos + 1)) : $slsScheme;
            }
            $sanctionDate = $this->getPreviewRowValue($row, ['Sanction Date', 'Sanction date']);
            $dailySanctionNo = $this->getPreviewRowValue($row, ['Daily Sanction Number', 'Daily sanction number']);
            $sNoSanction = $this->getPreviewRowValue($row, ['S. No. (Sanction)', 'S. No. (Sanction)', "S. No.\n(Sanction)"]);

            $financialYear = '';
            if ($sanctionDate !== '') {
                $parsed = \DateTime::createFromFormat('d-M-Y', $sanctionDate)
                    ?: \DateTime::createFromFormat('d-m-Y', $sanctionDate)
                    ?: \DateTime::createFromFormat('Y-m-d', $sanctionDate)
                    ?: \DateTime::createFromFormat('d/m/Y', $sanctionDate);
                if ($parsed) {
                    $y = (int) $parsed->format('Y');
                    $m = (int) $parsed->format('m');
                    $financialYear = $m >= 4 ? $y . '-' . ($y + 1) : ($y - 1) . '-' . $y;
                }
            }

            $stateId = null;
            if ($slsScheme !== '') {
                $cacheKey = $slsScheme;
                if (!isset($slsSchemeCache[$cacheKey])) {
                    $comp = SlsPDComponent::where('full_sls_name', $slsScheme)->first();
                    $slsSchemeCache[$cacheKey] = $comp ? (int) $comp->state_id : null;
                }
                $stateId = $slsSchemeCache[$cacheKey];
            }

            $motherSanctionNo = '';
            if ($dailySanctionNo !== '' && $sNoSanction !== '') {
                $pos = strpos($dailySanctionNo, '-');
                $prefix = $pos !== false ? substr($dailySanctionNo, 0, $pos + 1) : $dailySanctionNo;
                $motherSanctionNo = $prefix . $sNoSanction;
            }

            $ifdNo = '';
            if ($dailySanctionNo !== '') {
                $pos = strpos($dailySanctionNo, '-');
                $ifdNo = $pos !== false ? substr($dailySanctionNo, 0, $pos) : $dailySanctionNo;
            }

            $motherSanctionAmount = '';
            $availableAmount = '';
            // dd($slsScheme);
            if ($slsScheme !== '') {
                $cacheKey = $slsScheme;
                if (!isset($motherSanctionCache[$cacheKey])) {
                    $pdc = SlsPDComponent::where('full_sls_name', $slsScheme)->first();
                    // if($pdc){
                    //     dd($pdc);

                    // }
                    if (!$pdc && trim($slsScheme) !== '') {
                        $pdc = SlsPDComponent::whereRaw('TRIM(COALESCE(full_sls_name,\'\')) = ?', [trim($slsScheme)])->first();
                    }
                    if ($pdc) {
                        $pdcId = (int) $pdc->id;
                        $stateIdVal = (int) $pdc->state_id;
                        $nameVal = trim((string) $pdc->name);
                        $slsPDVal = trim((string) $pdc->slsPD);
                        $fullSlsVal = trim((string) ($pdc->full_sls_name ?? ''));
                        // dd($pdcId,$stateIdVal, $nameVal, $slsPDVal, $fullSlsVal);
                        $ms = MotherSanction::where('mother_sanction.state_id', $stateIdVal)
                            ->where(function ($q) use ($nameVal, $fullSlsVal) {
                                $q->whereRaw('TRIM(COALESCE(mother_sanction.sls_name,\'\')) = ?', [$nameVal]);
                                if ($fullSlsVal !== '' && $fullSlsVal !== $nameVal) {
                                    $q->orWhereRaw('TRIM(COALESCE(mother_sanction.sls_name,\'\')) = ?', [$fullSlsVal]);
                                }
                            })
                            ->whereRaw('TRIM(COALESCE(mother_sanction.pd_component,\'\')) = ?', [$slsPDVal])
                            ->where(function ($q) {
                                $q->where('mother_sanction.status', 1)->orWhere('mother_sanction.status', '1');
                            })
                            ->orderByDesc('mother_sanction.id')
                            ->first();
                        // dd($ms);
                        if (!$ms && $pdcId > 0) {
                            $msRow = DB::table('mother_sanction as ms')
                                ->join('pd_and_sls_comp as pdc', function ($j) {
                                    $j->on('ms.state_id', '=', 'pdc.state_id')
                                      ->on('ms.pd_component', '=', 'pdc.slsPD')
                                      ->whereRaw('(ms.sls_name = pdc.name OR (pdc.full_sls_name IS NOT NULL AND pdc.full_sls_name != \'\' AND ms.sls_name = pdc.full_sls_name))');
                                })
                                ->where('pdc.id', $pdcId)
                                ->where(function ($q) {
                                    $q->where('ms.status', 1)->orWhere('ms.status', '1');
                                })
                                ->orderByDesc('ms.id')
                                ->select('ms.mother_sanction_amount', 'ms.available_fund')
                                ->first();
                            if ($msRow) {
                                $ms = (object) [
                                    'mother_sanction_amount' => $msRow->mother_sanction_amount,
                                    'available_fund' => $msRow->available_fund,
                                ];
                            }
                        }
                        $motherSanctionCache[$cacheKey] = $ms ? [
                            'mother_sanction_amount' => $ms->mother_sanction_amount !== null ? number_format((float) $ms->mother_sanction_amount, 2, '.', '') : '',
                            'available_fund' => $ms->available_fund !== null ? number_format((float) $ms->available_fund, 2, '.', '') : '',
                        ] : ['mother_sanction_amount' => '', 'available_fund' => ''];
                    } else {
                        $motherSanctionCache[$cacheKey] = ['mother_sanction_amount' => '', 'available_fund' => ''];
                    }
                }
                if (!isset($motherSanctionShownForScheme[$cacheKey])) {
                    $motherSanctionAmount = $motherSanctionCache[$cacheKey]['mother_sanction_amount'];
                    $availableAmount = $motherSanctionCache[$cacheKey]['available_fund'];
                    $motherSanctionShownForScheme[$cacheKey] = true;
                }
            }

            $rows[$i]['SLS Name'] = $slsName;
            $rows[$i]['Financial Year'] = $financialYear;
            $rows[$i]['State Id'] = $stateId !== null ? (string) $stateId : '';
            $rows[$i]['Mother Sanction No.'] = $motherSanctionNo;
            $rows[$i]['IFd No'] = $ifdNo;
            $rows[$i]['Mother Sanction Amount'] = $motherSanctionAmount;
            $rows[$i]['Available Amount'] = $availableAmount;
        }

        return ['rows' => $rows, 'columns' => $allColumns];
    }

    /**
     * Map preview rows to daily_sanction table columns using the defined key mapping.
     * Mapping: Available Amount→available_amount, Daily Sanction Number→daily_sanction_no,
     * Financial Year→financial_year, Function Head→budget_head, IFd No→ifd_no,
     * Mother Sanction Amount→mother_sanction_amount, Mother Sanction No.→mother_sanction,
     * SLS Scheme→sls_name, Sanction Amount→center_share_amount, Sanction Date→ds_date,
     * Sanction Status→status (closed=0 else 1), State Id→state_id.
     */
    private function mapRawRowsToDailySanction(array $headerData, array $rawRows): array
    {
        $stateName = trim((string) ($headerData['state'] ?? ''));
        $headerStateId = null;
        if ($stateName !== '') {
            $state = State::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($stateName)])->first();
            $headerStateId = $state ? (int) $state->id : null;
        }

        $headerFinancialYear = trim((string) ($headerData['financial_year'] ?? ''));
        if ($headerFinancialYear === '') {
            $fromDateStr = trim((string) ($headerData['from_date'] ?? ''));
            if ($fromDateStr !== '') {
                $parsed = \DateTime::createFromFormat('d-m-Y', $fromDateStr)
                    ?: \DateTime::createFromFormat('d/m/Y', $fromDateStr)
                    ?: \DateTime::createFromFormat('Y-m-d', $fromDateStr);
                if ($parsed) {
                    $y = (int) $parsed->format('Y');
                    $m = (int) $parsed->format('m');
                    $headerFinancialYear = $m >= 4 ? $y . '-' . ($y + 1) : ($y - 1) . '-' . $y;
                }
            }
        }
        if ($headerFinancialYear === '') {
            $y = (int) date('Y');
            $headerFinancialYear = $y . '-' . ($y + 1);
        }

        $mapped = [];
        foreach ($rawRows as $raw) {
            $raw = is_array($raw) ? $raw : [];
            if ($this->isTotalOrGrandTotalRow($raw)) {
                continue;
            }

            $stateId = $this->getPreviewRowValue($raw, ['State Id', 'State id']);
            if ($stateId !== '' && is_numeric($stateId)) {
                $stateId = (int) $stateId;
            } else {
                $stateId = $headerStateId;
            }
            if ($stateId === null || $stateId === '') {
                continue;
            }

            $financialYear = $this->getPreviewRowValue($raw, ['Financial Year', 'Financial year']);
            if ($financialYear === '') {
                $financialYear = $headerFinancialYear;
            }

            $dsDate = $this->normalizeSanctionDate($this->getPreviewRowValue($raw, ['Sanction Date', 'Sanction date']));
            $dailySanctionNo = $this->getPreviewRowValue($raw, ['Daily Sanction Number', 'Daily sanction number']);
            $motherSanction = $this->getPreviewRowValue($raw, ['Mother Sanction No.', 'Mother Sanction No']);
            $ifdNo = $this->getPreviewRowValue($raw, ['IFd No', 'IFd no']);
            $slsName = $this->getPreviewRowValue($raw, ['SLS Name', 'SLS name']);
            if ($slsName === '') {
                $slsName = $this->getPreviewRowValue($raw, ['SLS Scheme', 'SLS scheme']);
            }
            $functionHead = $this->getPreviewRowValue($raw, ['Function Head', 'Function head']);
            $objectHead = $this->getPreviewRowValue($raw, ['Object Head', 'Object head']);
            $sanctionStatus = $this->getPreviewRowValue($raw, ['Sanction Status', 'Sanction status']);

            $budgetHead = $functionHead;
            if ($budgetHead !== '' && $objectHead !== '') {
                $budgetHead = $this->formatBudgetHead($functionHead, $objectHead);
            }

            $sanctionAmount = $this->getPreviewRowValue($raw, ['Sanction Amount', 'Sanction amount']);
            $centerShareAmount = $this->parseAmount($sanctionAmount);

            $motherSanctionAmountStr = $this->getPreviewRowValue($raw, ['Mother Sanction Amount', 'Mother Sanction amount']);
            $motherSanctionAmount = $this->parseAmount($motherSanctionAmountStr);

            $availableAmountStr = $this->getPreviewRowValue($raw, ['Available Amount', 'Available amount']);
            $availableAmount = $this->parseAmount($availableAmountStr);

            $status = 1;
            if (stripos($sanctionStatus, 'closed') !== false) {
                $status = 0;
            }

            $mapped[] = [
                'financial_year' => $financialYear,
                'state_id' => $stateId,
                'ds_date' => $dsDate,
                'daily_sanction_no' => $dailySanctionNo,
                'mother_sanction' => $motherSanction,
                'ifd_no' => $ifdNo,
                'sls_name' => $slsName,
                'budget_head' => $budgetHead,
                'mother_sanction_amount' => $motherSanctionAmount,
                'available_amount' => $availableAmount,
                'center_share_amount' => $centerShareAmount,
                'remark' => $sanctionStatus !== '' ? $sanctionStatus : null,
                'status' => $status,
            ];
        }
        return $mapped;
    }

    /**
     * Parse amount from preview value (string with commas or number).
     */
    private function parseAmount($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        $num = is_numeric($value) ? (float) $value : (float) preg_replace('/[^0-9.-]/', '', (string) $value);
        return round($num, 2);
    }

    /**
     * Store bulk daily sanction rows (from preview confirm). Accepts header_data + raw Excel rows.
     */
    public function bulkStore(Request $request)
    {
        try {
            $request->validate([
                'header_data' => 'required|array',
                'header_data.report_title' => 'nullable|string',
                'header_data.financial_year' => ['nullable', 'string', 'max:40', 'regex:/^\d{4}-\d{2,4}$/'],
                'header_data.state' => ['required', 'string', 'max:255', 'regex:' . self::SAFE_TEXT_PATTERN],
                'header_data.scheme_css' => 'nullable|string',
                'header_data.scheme_sls' => 'nullable|string',
                'header_data.from_date' => 'nullable|string',
                'header_data.to_date' => 'nullable|string',
                'header_data.isdbt_payment_mode' => 'nullable|string',
                'header_data.figures_in' => 'nullable|string',
                'header_data.total_sanction' => 'nullable|string',
                'rows' => 'required|array|min:1',
            ], [
                'header_data.financial_year.regex' => 'Financial year format must be like 2025-26.',
                'header_data.state.regex' => 'State contains invalid special characters.',
            ]);

            $mapped = $this->mapRawRowsToDailySanction($request->header_data, $request->rows);
            if (empty($mapped)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not resolve state from header. Ensure state name in Excel matches master.',
                ], 422);
            }

            $inserted = 0;
            foreach ($mapped as $row) {
                if (empty($row['ds_date']) || empty($row['state_id'])) {
                    continue;
                }
                $record = DailySanction::create([
                    'financial_year' => $row['financial_year'] ?? null,
                    'state_id' => $row['state_id'],
                    'ds_date' => $row['ds_date'],
                    'daily_sanction_no' => $row['daily_sanction_no'] ?? '',
                    'mother_sanction' => $row['mother_sanction'] ?? '',
                    'ifd_no' => $row['ifd_no'] ?? '',
                    'sls_name' => $row['sls_name'] ?? '',
                    'budget_head' => $row['budget_head'] ?? '',
                    'mother_sanction_amount' => $row['mother_sanction_amount'] ?? 0,
                    'available_amount' => $row['available_amount'] ?? 0,
                    'center_share_amount' => $row['center_share_amount'] ?? 0,
                    'remark' => $row['remark'] ?? null,
                    'status' => isset($row['status']) ? (int) $row['status'] : 1,
                ]);
                $this->saveDailySanctionHistory($record, 'CREATE', 'Daily sanction entry created via bulk upload');
                $inserted++;
            }

            return response()->json([
                'success' => true,
                'message' => $inserted . ' daily sanction record(s) saved successfully.',
                'inserted' => $inserted,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Daily sanction bulk store error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to save: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save history record for daily sanction changes
     */
    private function saveDailySanctionHistory($record, $actionType, $description = null, $oldCenterShare = null, $newCenterShare = null)
    {
        $changedBy = Auth::check() ? Auth::user()->name : 'System';
        DailySanctionHistory::create([
            'daily_sanction_id' => $record->id,
            'financial_year' => $record->financial_year,
            'state_id' => $record->state_id,
            'ds_date' => $record->ds_date,
            'daily_sanction_no' => $record->daily_sanction_no,
            'mother_sanction' => $record->mother_sanction,
            'ifd_no' => $record->ifd_no,
            'sls_name' => $record->sls_name,
            'budget_head' => $record->budget_head,
            'mother_sanction_amount' => $record->mother_sanction_amount,
            'available_amount' => $record->available_amount,
            'center_share_amount' => $record->center_share_amount,
            'remark' => $record->remark,
            'status' => $record->status,
            'action_type' => $actionType,
            'changed_by' => $changedBy,
            'change_description' => $description,
            'old_center_share_amount' => $oldCenterShare ?? $record->center_share_amount,
            'new_center_share_amount' => $newCenterShare ?? $record->center_share_amount,
        ]);
    }

    /**
     * Get daily sanction history list
     */
    public function historyList()
    {
        try {
            $history = DailySanctionHistory::with('state')
                ->orderBy('history_timestamp', 'desc')
                ->get();

            // Group by daily_sanction_no and state_id (one row per sanction no in UI)
            $groupedData = $history->groupBy(function ($item) {
                return ($item->state_id ?? '') . '|' . ($item->daily_sanction_no ?? '');
            });

            $transformedData = $groupedData->map(function ($group) {
                $firstItem = $group->first();

                $budgetHeadMap = [];
                foreach ($group as $item) {
                    if (empty($item->budget_head)) {
                        continue;
                    }
                    $budgetKey = $item->budget_head;
                    if (! isset($budgetHeadMap[$budgetKey])) {
                        $budgetHeadMap[$budgetKey] = [
                            'budget_head' => $item->budget_head,
                            'old_center_share_amount' => 0,
                            'new_center_share_amount' => 0,
                            'center_share_amount' => 0,
                            'action_type' => $item->action_type,
                            'change_description' => $item->change_description,
                            'changed_by' => $item->changed_by,
                            'history_timestamp' => $item->history_timestamp,
                        ];
                    }
                    $budgetHeadMap[$budgetKey]['center_share_amount'] += floatval($item->center_share_amount ?? 0);
                    $budgetHeadMap[$budgetKey]['old_center_share_amount'] += floatval($item->old_center_share_amount ?? 0);
                    $budgetHeadMap[$budgetKey]['new_center_share_amount'] += floatval($item->new_center_share_amount ?? 0);
                }

                $budgetHeads = collect($budgetHeadMap)->values();

                return [
                    'id' => $firstItem->history_id,
                    'financial_year' => $firstItem->financial_year,
                    'state_id' => $firstItem->state_id,
                    'daily_sanction_no' => $firstItem->daily_sanction_no,
                    'ds_date' => $firstItem->ds_date,
                    'mother_sanction' => $firstItem->mother_sanction,
                    'sls_name' => $firstItem->sls_name,
                    'ifd_no' => $firstItem->ifd_no,
                    'budget_heads' => $budgetHeads,
                    'action_type' => $firstItem->action_type,
                    'changed_by' => $firstItem->changed_by,
                    'history_timestamp' => $firstItem->history_timestamp,
                    'change_description' => $firstItem->change_description,
                    'state' => [
                        'id' => $firstItem->state_id,
                        'name' => $firstItem->state?->name ?? '',
                    ],
                ];
            })->values();

            return response()->json($transformedData);
        } catch (\Exception $e) {
            Log::error('Error fetching daily sanction history:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'An error occurred while fetching history',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
