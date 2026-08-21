<?php

/**
 * Import Active mother sanctions for FY 2026-2027.
 *
 * SPARSH Excel (3 sheets) supplies SLS / PD / Total MS / budget-head amounts.
 * Drill1_Page.xlsx supplies Mother Sanction Number, date, and Active status.
 * Rows are matched by State + SLS.
 *
 * Usage: php database/scripts/import_mother_sanction_2026_2027.php
 */

ini_set('memory_limit', '1024M');
set_time_limit(0);

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MotherSanction;
use App\Models\MotherSanctionHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

const FINANCIAL_YEAR = '2026-2027';
const SPARSH_PATH = 'C:/Users/piyush_singh/Documents/AGRICULTURE/KYAS-documents/kyas_upload/2026-2027/_KY - SNA SPARSH Upload Statewise Budget Headwise Daily Sanction Expenditure report 2026-2027 - Copy.xlsx';
const DRILL1_PATH = 'C:/Users/piyush_singh/Documents/AGRICULTURE/KYAS-documents/kyas_upload/2026-2027/Drill1_Page.xlsx';

const PD_ALIASES = [
    'smsp' => 'Sub Mission on Seed and Planting',
    'nmeo-op' => 'National Mission on Edible Oils-Oil Palm',
    'nmeo op' => 'National Mission on Edible Oils-Oil Palm',
    'oilpalm' => 'National Mission on Edible Oils-Oil Palm',
    'oil palm' => 'National Mission on Edible Oils-Oil Palm',
    'nbm' => 'National Bamboo Mission',
    'bamboo' => 'National Bamboo Mission',
    'digital agriculture' => 'Digital Agriculture Mission',
    'digital agriculture mission' => 'Digital Agriculture Mission',
    'nmeo-os' => 'National Mission on Edible Oils-Oil Seeds',
    'nmeo os' => 'National Mission on Edible Oils-Oil Seeds',
    'oilseeds' => 'National Mission on Edible Oils-Oil Seeds',
    'oil seeds' => 'National Mission on Edible Oils-Oil Seeds',
    'midh' => 'Mission for Integrated Development of Horticulture',
    'agriculture extension' => 'Sub-mission on Agriculture Extension',
    'smae' => 'Sub-mission on Agriculture Extension',
    'nfsnm' => 'National Food Security and Nutrition Mission',
    'nfsm' => 'National Food Security and Nutrition Mission',
    'pulses' => 'Mission Pulses',
    'cotton mission' => 'Mission Cotton',
    'mission cotton' => 'Mission Cotton',
    'cotton' => 'Mission Cotton',
    'movcdner' => 'Mission Organic Value Chain Development for North East Region',
];

const STATE_ALIASES = [
    'tamilnadu' => 'tamilnadu',
    'uttrakhand' => 'uttarakhand',
    'nctofdelhi' => 'delhi',
    'ofdelhi' => 'delhi',
    'pondicherry' => 'puducherry',
    'orissa' => 'odisha',
    'andamannicobar' => 'andamannicobar',
    'andamanandnicobar' => 'andamannicobar',
];

function cellValue($sheet, int $col, int $row)
{
    $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row);
    $old = $cell->getOldCalculatedValue();
    if ($old !== null && $old !== '') {
        $oldStr = is_scalar($old) ? trim((string) $old) : '';
        if ($oldStr !== '' && !in_array($oldStr, ['#NAME?', '#VALUE!', '#REF!', '#DIV/0!', '#N/A'], true)) {
            return $old;
        }
    }

    $raw = $cell->getValue();
    if (is_string($raw) && str_starts_with($raw, '=')) {
        return '';
    }

    return $raw;
}

function cellStr($sheet, int $col, int $row): string
{
    $value = cellValue($sheet, $col, $row);

    return trim(str_replace(["\xc2\xa0", "\n", "\r"], [' ', ' ', ''], (string) $value));
}

function parseAmountToLakhs($value): float
{
    if ($value === null || $value === '') {
        return 0.0;
    }
    $str = preg_replace('/[^0-9.\-]/', '', (string) $value);
    if ($str === '' || $str === '-' || $str === '.') {
        return 0.0;
    }

    return round(((float) $str) / 100000, 5);
}

function formatAmount(float $n, int $decimals = 5): string
{
    return number_format($n, $decimals, '.', '');
}

function parseDate($value): ?string
{
    $str = trim((string) $value);
    if ($str === '') {
        return null;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
        return $str;
    }
    foreach (['d-M-Y', 'd-m-Y', 'd/m/Y', 'Y-m-d', 'd-M-y'] as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $str);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d');
        }
    }
    try {
        return Carbon::parse($str)->format('Y-m-d');
    } catch (Throwable $e) {
        return null;
    }
}

function normalizeMsNo(string $value): string
{
    $value = strtoupper(trim(preg_replace('/\s+/', ' ', $value)));
    $value = preg_replace('/\(\s+/', '(', $value);
    $value = preg_replace('/\s+\)/', ')', $value);

    return $value;
}

function slsNameAfterDash(string $slsScheme): string
{
    $pos = strpos($slsScheme, '-');

    return $pos === false ? trim($slsScheme) : trim(substr($slsScheme, $pos + 1));
}

function extractSlsCode(string $sls): string
{
    $s = strtoupper(trim($sls));
    $s = str_replace(['_', '.'], ' ', $s);
    if (preg_match('/^([A-Z]{2,3})\s*-?\s*(\d+)/', $s, $m)) {
        return $m[1] . $m[2];
    }
    if (preg_match('/^(\d+)/', $s, $m)) {
        return $m[1];
    }

    return '';
}

function normalizeSlsCode(string $code): string
{
    $code = strtoupper(trim($code));
    $code = str_replace([' ', '&', '-', '_', '.'], '', $code);
    if (str_starts_with($code, 'JANDK')) {
        $code = 'JK' . substr($code, 5);
    } elseif (str_starts_with($code, 'JNK')) {
        $code = 'JK' . substr($code, 3);
    }

    return $code;
}

function extractSlsCodeFromMsNo(string $msNo): string
{
    if (preg_match('/\(([^)]+)\)/', $msNo, $m)) {
        return normalizeSlsCode($m[1]);
    }

    return '';
}

function slsLookupCodes(string $code): array
{
    $norm = normalizeSlsCode($code);
    if ($norm === '') {
        return [];
    }

    $codes = [$norm];
    // SPARSH "2425" and Drill1 "UK2425" / "(UK 2425)"
    if (preg_match('/^[A-Z]+(\d+)$/', $norm, $m)) {
        $codes[] = $m[1];
    }

    return array_values(array_unique($codes));
}

function normalizeStateKey(string $name): string
{
    $name = strtolower(trim($name));
    $name = str_replace(['&', '.', ',', '(', ')', '[', ']', '-', '_', '/'], ' ', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    $name = preg_replace('/\band\b/', ' ', $name);
    $name = preg_replace('/\b(ut|nct|national capital territory)\b/', ' ', $name);
    $name = preg_replace('/\s+/', '', $name);

    return STATE_ALIASES[$name] ?? $name;
}

function extractBudgetHead(string $header): string
{
    if (preg_match('/(\d{4}\.\d{2}\.\d{2,3}\.\d{2}\.\d{2}\.\d{2})/', $header, $m)) {
        return $m[1];
    }

    return '';
}

function mapProgramDivision(string $pdShort): string
{
    $key = strtolower(trim($pdShort));
    $key = str_replace(['_', '/', '.'], ' ', $key);
    $key = preg_replace('/\s+/', ' ', $key);

    return PD_ALIASES[$key] ?? trim($pdShort);
}

function splitAmountByWeights(float $total, array $weights): array
{
    $sum = array_sum($weights);
    $result = [];
    if ($sum <= 0) {
        $count = count($weights);
        $share = $count > 0 ? round($total / $count, 5) : 0.0;
        foreach ($weights as $key => $_) {
            $result[$key] = $share;
        }
        if ($count > 0) {
            $firstKey = array_key_first($result);
            $result[$firstKey] = round($total - (array_sum($result) - $result[$firstKey]), 5);
        }

        return $result;
    }

    $allocated = 0.0;
    $largestKey = array_key_first($weights);
    $largestWeight = -1;
    foreach ($weights as $key => $weight) {
        $share = round($total * ($weight / $sum), 5);
        $result[$key] = $share;
        $allocated += $share;
        if ($weight > $largestWeight) {
            $largestWeight = $weight;
            $largestKey = $key;
        }
    }
    $result[$largestKey] = round($result[$largestKey] + ($total - $allocated), 5);

    return $result;
}

if (!is_file(SPARSH_PATH) || !is_file(DRILL1_PATH)) {
    fwrite(STDERR, "Excel file not found.\n");
    exit(1);
}

echo "Loading master data...\n";
DB::connection()->disableQueryLog();

$states = DB::table('states')->select('id', 'name')->get();
$stateIdByKey = [];
foreach ($states as $state) {
    $key = normalizeStateKey($state->name);
    $stateIdByKey[$key] = (int) $state->id;
    if (isset(STATE_ALIASES[$key])) {
        $stateIdByKey[STATE_ALIASES[$key]] = (int) $state->id;
    }
}
foreach (STATE_ALIASES as $alias => $canonical) {
    if (isset($stateIdByKey[$canonical])) {
        $stateIdByKey[$alias] = $stateIdByKey[$canonical];
    }
}

$matchStateId = function (string $excelState) use ($stateIdByKey): ?int {
    $key = normalizeStateKey($excelState);
    if (isset($stateIdByKey[$key])) {
        return $stateIdByKey[$key];
    }
    if (isset(STATE_ALIASES[$key]) && isset($stateIdByKey[STATE_ALIASES[$key]])) {
        return $stateIdByKey[STATE_ALIASES[$key]];
    }
    foreach ($stateIdByKey as $dbKey => $id) {
        if ($key !== '' && (str_starts_with($dbKey, $key) || str_starts_with($key, $dbKey))) {
            return $id;
        }
    }

    return null;
};

$pdSls = DB::table('pd_and_sls_comp')->get();
$pdByFull = [];
$pdByStateName = [];
$pdByCode = [];
foreach ($pdSls as $row) {
    $full = strtoupper(trim((string) $row->full_sls_name));
    $name = strtoupper(trim((string) $row->name));
    $code = normalizeSlsCode((string) $row->sls_code);
    if ($full !== '') {
        $pdByFull[$full] = $row;
    }
    if ($name !== '') {
        $pdByStateName[(int) $row->state_id . '|' . $name] = $row;
    }
    if ($code !== '') {
        $pdByCode[$code] = $row;
        $pdByCode[(int) $row->state_id . '|' . $code] = $row;
    }
}

$matchPdSls = function (string $slsScheme, string $slsName, string $slsCode, ?int $stateId) use ($pdByFull, $pdByStateName, $pdByCode): ?object {
    foreach (slsLookupCodes($slsCode) as $codeKey) {
        if ($stateId && isset($pdByCode[$stateId . '|' . $codeKey])) {
            return $pdByCode[$stateId . '|' . $codeKey];
        }
    }
    $fullKey = strtoupper(trim($slsScheme));
    if ($fullKey !== '' && isset($pdByFull[$fullKey])) {
        return $pdByFull[$fullKey];
    }
    if ($stateId && $slsName !== '') {
        $key = $stateId . '|' . strtoupper(trim($slsName));
        if (isset($pdByStateName[$key])) {
            return $pdByStateName[$key];
        }
    }
    foreach (slsLookupCodes($slsCode) as $codeKey) {
        if (isset($pdByCode[$codeKey])) {
            return $pdByCode[$codeKey];
        }
    }

    return null;
};

$budgetHeads = DB::table('budget_heads')->get(['id', 'budget', 'category']);
$bhCategory = [];
foreach ($budgetHeads as $bh) {
    $bhCategory[$bh->budget] = (string) $bh->category;
}

$pdIdByName = [];
foreach (DB::table('md_program_divisions')->select('division_id', 'division_name')->get() as $pd) {
    $pdIdByName[strtoupper(trim($pd->division_name))] = (int) $pd->division_id;
}

$allocations = [];
$allocRows = DB::table('pdwise_aap_allocation as pda')
    ->join('budget_heads as bh', 'bh.id', '=', 'pda.bh_id')
    ->where('pda.status', 1)
    ->whereIn('pda.financial_year', ['2026-27', '2026-2027'])
    ->where(function ($q) {
        $q->where('pda.budget_phase', 'BE')->orWhereNull('pda.budget_phase');
    })
    ->select('pda.pd_id', 'bh.budget', DB::raw('SUM(pda.amount) as amt'))
    ->groupBy('pda.pd_id', 'bh.budget')
    ->get();
foreach ($allocRows as $row) {
    $allocations[$row->pd_id . '|' . $row->budget] = (float) $row->amt;
}

echo "Parsing Drill1 (mother sanction number / date / Active status)...\n";
$ss1 = IOFactory::load(DRILL1_PATH);
$sheet1 = $ss1->getActiveSheet();
$highest1 = $sheet1->getHighestDataRow();

$allMs = [];
$currentIndex = -1;
$occurrenceByNorm = [];

for ($r = 12; $r <= $highest1; $r++) {
    $msNo = cellStr($sheet1, 3, $r);
    $stateName = cellStr($sheet1, 4, $r);
    $statusText = cellStr($sheet1, 6, $r);
    $sanctionDate = cellStr($sheet1, 7, $r);
    $msAmount = cellValue($sheet1, 8, $r);
    $slsScheme = cellStr($sheet1, 19, $r);

    if ($msNo !== '' && $stateName !== '') {
        $norm = normalizeMsNo($msNo);
        $occurrenceByNorm[$norm] = ($occurrenceByNorm[$norm] ?? 0) + 1;
        $allMs[] = [
            'row' => $r,
            'ms_no' => $msNo,
            'ms_norm' => $norm,
            'state_name' => $stateName,
            'status_text' => $statusText,
            'sanction_date' => $sanctionDate,
            'ms_amount_lakhs' => parseAmountToLakhs($msAmount),
            'sls_scheme' => '',
            'ms_sequence_no' => (string) $occurrenceByNorm[$norm],
        ];
        $currentIndex = count($allMs) - 1;
        continue;
    }

    if ($currentIndex >= 0 && $slsScheme !== '' && stripos($slsScheme, 'Net Daily') === false) {
        $allMs[$currentIndex]['sls_scheme'] = $slsScheme;
    }
}

$activeByKey = [];
$activeList = [];
$warnings = [];

foreach ($allMs as $ms) {
    if (strcasecmp((string) $ms['status_text'], 'Active') !== 0) {
        continue;
    }

    $stateId = $matchStateId($ms['state_name']);
    if (!$stateId) {
        $warnings[] = "Drill1 unmatched state '{$ms['state_name']}' for MS {$ms['ms_no']}";
        continue;
    }

    $slsScheme = $ms['sls_scheme'];
    $slsName = slsNameAfterDash($slsScheme);
    $slsCode = extractSlsCode($slsScheme);
    $msCode = extractSlsCodeFromMsNo($ms['ms_no']);
    $resolvedCode = $slsCode !== '' ? $slsCode : $msCode;
    $pdRow = $matchPdSls($slsScheme, $slsName, $resolvedCode, $stateId);
    $pdComponent = $pdRow->slsPD ?? '';
    $pdCode = $pdRow ? normalizeSlsCode((string) $pdRow->sls_code) : '';
    if ($pdRow && trim((string) $pdRow->name) !== '' && ($pdCode === '' || in_array($pdCode, slsLookupCodes($resolvedCode), true))) {
        $slsName = trim((string) $pdRow->name);
    }

    $ms['state_id'] = $stateId;
    $ms['sls_name'] = $slsName;
    $ms['sls_code'] = normalizeSlsCode($resolvedCode);
    $ms['pd_component'] = $pdComponent;
    $ms['pd_id'] = $pdComponent !== '' ? ($pdIdByName[strtoupper(trim($pdComponent))] ?? null) : null;
    $activeList[] = $ms;

    $stateKey = normalizeStateKey($ms['state_name']);
    $keys = [];
    $full = strtoupper(preg_replace('/\s+/', ' ', trim($slsScheme)));
    if ($full !== '') {
        $keys[] = $stateKey . '|full|' . $full;
    }
    foreach (array_unique(array_merge(slsLookupCodes($slsCode), slsLookupCodes($msCode))) as $code) {
        $keys[] = $stateKey . '|code|' . $code;
    }
    foreach ($keys as $key) {
        $activeByKey[$key][] = count($activeList) - 1;
    }
}

echo 'Active Drill1 mother sanctions: ' . count($activeList) . "\n";
unset($ss1, $sheet1);

echo "Parsing SPARSH sheets (budget-head wise mother sanction amounts)...\n";
$ss2 = IOFactory::load(SPARSH_PATH);
$sparshRows = [];

foreach ($ss2->getAllSheets() as $sheet) {
    $title = $sheet->getTitle();
    $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
    $highestRow = $sheet->getHighestDataRow();
    $bhCols = [];
    for ($c = 1; $c <= $highestCol; $c++) {
        $header = cellStr($sheet, $c, 3);
        $bh = extractBudgetHead($header);
        if ($bh !== '' && stripos($header, 'Mother') !== false) {
            $bhCols[$c] = $bh;
        }
    }

    echo "  {$title}: BH columns=" . implode(', ', $bhCols) . "\n";
    $stateName = '';

    for ($r = 4; $r <= $highestRow; $r++) {
        $st = cellStr($sheet, 2, $r);
        if ($st !== '') {
            $stateName = $st;
        }

        $slsScheme = cellStr($sheet, 3, $r);
        $pdShort = cellStr($sheet, 4, $r);
        if ($slsScheme === '' || preg_match('/^total\b/i', $slsScheme)) {
            continue;
        }

        $bhAmounts = [];
        foreach ($bhCols as $col => $bh) {
            $amt = parseAmountToLakhs(cellValue($sheet, $col, $r));
            if ($amt > 0) {
                $bhAmounts[$bh] = ($bhAmounts[$bh] ?? 0.0) + $amt;
            }
        }

        $totalMs = parseAmountToLakhs(cellValue($sheet, 6, $r));
        if ($totalMs <= 0 && empty($bhAmounts)) {
            continue;
        }
        if (empty($bhAmounts)) {
            $warnings[] = "{$title} R{$r} {$stateName} / {$slsScheme}: Total MS {$totalMs} but no BH amount > 0";
            continue;
        }
        if ($totalMs <= 0) {
            $totalMs = round(array_sum($bhAmounts), 5);
        }

        $stateId = $matchStateId($stateName);
        if (!$stateId) {
            $warnings[] = "{$title} R{$r} unmatched state '{$stateName}' for SLS {$slsScheme}";
            continue;
        }

        $sparshRows[] = [
            'sheet' => $title,
            'row' => $r,
            'state_name' => $stateName,
            'state_id' => $stateId,
            'sls_scheme' => $slsScheme,
            'pd_short' => $pdShort,
            'total_ms' => $totalMs,
            'bh_amounts' => $bhAmounts,
        ];
    }
}
unset($ss2);

echo 'SPARSH SLS rows with BH amounts: ' . count($sparshRows) . "\n";

$usedActive = [];
$pendingInserts = [];
$unmatchedSparsh = 0;

foreach ($sparshRows as $row) {
    $stateKey = normalizeStateKey($row['state_name']);
    $slsCode = extractSlsCode($row['sls_scheme']);
    $sparshCodes = slsLookupCodes($slsCode);
    $full = strtoupper(preg_replace('/\s+/', ' ', trim($row['sls_scheme'])));
    $lookupKeys = [];
    if ($full !== '') {
        $lookupKeys[] = $stateKey . '|full|' . $full;
    }
    foreach ($sparshCodes as $code) {
        $lookupKeys[] = $stateKey . '|code|' . $code;
    }

    $indexes = [];
    foreach ($lookupKeys as $key) {
        if (!empty($activeByKey[$key])) {
            foreach ($activeByKey[$key] as $idx) {
                $indexes[$idx] = true;
            }
        }
    }

    $matches = [];
    foreach (array_keys($indexes) as $idx) {
        $ms = $activeList[$idx];
        $msCodes = array_unique(array_merge(
            slsLookupCodes($ms['sls_code']),
            slsLookupCodes(extractSlsCodeFromMsNo($ms['ms_no']))
        ));
        if ($sparshCodes && !array_intersect($sparshCodes, $msCodes)) {
            continue;
        }
        $matches[] = $ms;
        $usedActive[$idx] = true;
    }

    $sparshNorm = normalizeSlsCode($slsCode);
    if ($sparshNorm !== '' && count($matches) > 1) {
        $exact = array_values(array_filter($matches, function ($ms) use ($sparshNorm) {
            $msCodes = array_merge(
                slsLookupCodes($ms['sls_code']),
                slsLookupCodes(extractSlsCodeFromMsNo($ms['ms_no']))
            );
            return in_array($sparshNorm, $msCodes, true);
        }));
        if (!empty($exact)) {
            $matches = $exact;
        }
    }

    if (empty($matches)) {
        $unmatchedSparsh++;
        $warnings[] = "No Active Drill1 match for {$row['sheet']} R{$row['row']} {$row['state_name']} / {$row['sls_scheme']}";
        continue;
    }

    $weights = [];
    foreach ($matches as $i => $ms) {
        $weights[$i] = (float) $ms['ms_amount_lakhs'];
    }

    foreach ($row['bh_amounts'] as $budgetHead => $amount) {
        $split = count($matches) === 1 ? [0 => $amount] : splitAmountByWeights($amount, $weights);
        foreach ($matches as $i => $ms) {
            $pendingInserts[] = [
                'ms' => $ms,
                'sparsh' => $row,
                'budget_head' => $budgetHead,
                'mother_sanction_amount' => $split[$i] ?? 0.0,
                'total_ms' => count($matches) === 1
                    ? $row['total_ms']
                    : (float) $ms['ms_amount_lakhs'],
            ];
        }
    }
}

$unmatchedActive = 0;
foreach ($activeList as $idx => $ms) {
    if (!isset($usedActive[$idx])) {
        $unmatchedActive++;
        $warnings[] = "Active Drill1 MS {$ms['ms_no']} ({$ms['state_name']} / {$ms['sls_scheme']}) has no SPARSH BH row";
    }
}

echo 'Matched SPARSH→Drill1 BH lines to insert: ' . count($pendingInserts) . "\n";
echo "Unmatched SPARSH rows: {$unmatchedSparsh}\n";
echo "Active Drill1 without SPARSH: {$unmatchedActive}\n";

$now = Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s');

echo "Clearing existing 2026-2027 mother_sanction rows (if any)...\n";
DB::beginTransaction();
try {
    $existingMsIds = DB::table('mother_sanction')->where('financial_year', FINANCIAL_YEAR)->pluck('id');
    if ($existingMsIds->isNotEmpty()) {
        DB::table('mother_sanction_history')->whereIn('mother_sanction_id', $existingMsIds)->delete();
        DB::table('mother_sanction')->where('financial_year', FINANCIAL_YEAR)->delete();
    }

    $grouped = [];
    foreach ($pendingInserts as $item) {
        $grouped[$item['ms']['ms_norm'] . '|' . $item['ms']['state_id']][] = $item;
    }

    $inserted = 0;
    foreach ($grouped as $items) {
        $firstId = null;
        $ms = $items[0]['ms'];
        $sparsh = $items[0]['sparsh'];

        $slsScheme = $sparsh['sls_scheme'] !== '' ? $sparsh['sls_scheme'] : $ms['sls_scheme'];
        $slsName = slsNameAfterDash($slsScheme);
        $slsCode = extractSlsCode($slsScheme);
        if ($slsCode === '') {
            $slsCode = $ms['sls_code'];
        }
        $msCode = extractSlsCodeFromMsNo($ms['ms_no']);
        $resolvedCode = $slsCode !== '' ? $slsCode : ($msCode !== '' ? $msCode : $ms['sls_code']);
        $pdRow = $matchPdSls($slsScheme, $slsName, $resolvedCode, $ms['state_id']);
        $pdComponent = $pdRow->slsPD ?? ($ms['pd_component'] !== '' ? $ms['pd_component'] : mapProgramDivision($sparsh['pd_short']));
        $pdCode = $pdRow ? normalizeSlsCode((string) $pdRow->sls_code) : '';
        if ($pdRow && trim((string) $pdRow->name) !== '' && ($pdCode === '' || in_array($pdCode, slsLookupCodes($resolvedCode), true))) {
            $slsName = trim((string) $pdRow->name);
        } elseif ($ms['sls_name'] !== '') {
            $slsName = $ms['sls_name'];
        }
        $pdId = $pdIdByName[strtoupper(trim((string) $pdComponent))] ?? $ms['pd_id'];

        foreach ($items as $item) {
            $budgetHead = $item['budget_head'];
            $msAmount = (float) $item['mother_sanction_amount'];
            if ($msAmount <= 0) {
                continue;
            }

            $category = $bhCategory[$budgetHead] ?? '';
            $allocation = 0.0;
            if ($pdId && isset($allocations[$pdId . '|' . $budgetHead])) {
                $allocation = $allocations[$pdId . '|' . $budgetHead];
            }
            $availableFund = max(0.0, round($allocation - $msAmount, 5));

            $record = MotherSanction::create([
                'financial_year' => FINANCIAL_YEAR,
                'state_id' => $ms['state_id'],
                'ms_sequence_no' => $ms['ms_sequence_no'],
                'file_no' => '',
                'ifd_no' => $ms['ms_no'],
                'sanction_date' => parseDate($ms['sanction_date']) ?: date('Y-m-d'),
                'ky_ms_no' => $ms['ms_no'],
                'sls_name' => $slsName,
                'pd_component' => $pdComponent,
                'total_mother_sanction_amount' => formatAmount((float) $item['total_ms']),
                'budget_head' => $budgetHead,
                'category' => $category,
                'available_fund' => formatAmount($availableFund),
                'mother_sanction_amount' => formatAmount($msAmount),
                'carry_forward_amount' => 0,
                'uc_received_from_State' => '',
                'signed_copy_of_mother_sanction' => '',
                'status' => 1,
                'action_type' => 'FRESH_CREATE',
                'last_id' => $firstId,
                'remark' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($firstId === null) {
                $firstId = $record->id;
                $record->last_id = $firstId;
                $record->save();
            }

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
                'action_type' => 'FRESH_CREATE',
                'changed_by' => 'System',
                'change_description' => 'New mother sanction record created via SPARSH Excel import',
                'old_mother_sanction_amount' => $record->mother_sanction_amount,
                'new_mother_sanction_amount' => $record->mother_sanction_amount,
                'old_available_fund' => $record->available_fund,
                'new_available_fund' => $record->available_fund,
                'history_timestamp' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $inserted++;
        }
    }

    DB::commit();

    echo "\n=== IMPORT COMPLETE ===\n";
    echo 'Active Drill1 mother sanctions: ' . count($activeList) . "\n";
    echo "SPARSH SLS rows used: " . (count($sparshRows) - $unmatchedSparsh) . " / " . count($sparshRows) . "\n";
    echo "mother_sanction rows inserted: {$inserted}\n";
    echo 'mother_sanction table count: ' . DB::table('mother_sanction')->count() . "\n";
    echo 'history rows: ' . DB::table('mother_sanction_history')->count() . "\n";
    if ($warnings) {
        echo "\nWarnings (" . count($warnings) . "):\n";
        foreach ($warnings as $w) {
            echo "  - {$w}\n";
        }
    }
} catch (Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, 'IMPORT FAILED: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
    exit(1);
}
