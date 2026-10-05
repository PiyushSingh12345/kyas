<?php

/**
 * Import 2nd-tranche mother sanctions for FY 2026-2027.
 *
 * Amount workbook (sheet1 = 20 states, Sheet2 = NER, Sheet3 = UT) is the
 * source of SLS-wise and budget-head-wise mother sanction amounts, in lakhs.
 * Drill1 supplies the mother sanction number and sanction date when PFMS has
 * issued a separate sanction for that tranche.
 *
 * A row is skipped when the same state + SLS already has those budget-head
 * amounts. Existing rows are not updated or deleted.
 *
 * Usage:
 *   php database/scripts/import_second_tranche_mother_sanction_2026_2027.php
 *   php database/scripts/import_second_tranche_mother_sanction_2026_2027.php --commit
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
const AMOUNT_PATH = 'C:/Users/piyush_singh/Documents/AGRICULTURE/KYAS-documents/kyas_upload/2026-2027/secondTrunch/2nd tranche data of Mother Sanctions - Copy.xlsx';
const DRILL1_PATH = 'C:/Users/piyush_singh/Documents/AGRICULTURE/KYAS-documents/kyas_upload/2026-2027/secondTrunch/RptCSSTSA03_MotherSanctionVsCentralRelease_Drill1_Page (13).xlsx';
const IMPORT_SHEETS = ['sheet1', 'Sheet2', 'Sheet3'];
const AMOUNT_TOLERANCE = 0.05;

const PD_ALIASES = [
    'smsp' => 'Sub Mission on Seed and Planting',
    'seeds smsp' => 'Sub Mission on Seed and Planting',
    'seeds (smsp)' => 'Sub Mission on Seed and Planting',
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
    'extension' => 'Sub-mission on Agriculture Extension',
    'smae' => 'Sub-mission on Agriculture Extension',
    'nfsnm' => 'National Food Security and Nutrition Mission',
    'nfsm' => 'National Food Security and Nutrition Mission',
    'pulses' => 'Mission Pulses',
    'mission pulses' => 'Mission Pulses',
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
    'jammuandkashmir' => 'jammukashmir',
    'jammukashmir' => 'jammukashmir',
];

$commit = in_array('--commit', $argv ?? [], true);

function cellValue($sheet, int $col, int $row)
{
    $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row);
    try {
        $calculated = $cell->getCalculatedValue();
    } catch (Throwable $e) {
        $calculated = null;
    }
    if ($calculated !== null && $calculated !== '' && !(is_string($calculated) && str_starts_with($calculated, '='))) {
        $calcStr = is_scalar($calculated) ? trim((string) $calculated) : '';
        if ($calcStr !== '' && !in_array($calcStr, ['#NAME?', '#VALUE!', '#REF!', '#DIV/0!', '#N/A'], true)) {
            return $calculated;
        }
    }

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

function parseLakhs($value): float
{
    if ($value === null || $value === '') {
        return 0.0;
    }
    if (is_string($value) && in_array(trim($value), ['#NAME?', '#VALUE!', '#REF!', '#DIV/0!', '#N/A', '-'], true)) {
        return 0.0;
    }
    $str = preg_replace('/[^0-9.\-]/', '', (string) $value);
    if ($str === '' || $str === '-' || $str === '.') {
        return 0.0;
    }

    return round((float) $str, 5);
}

function parseRupeesToLakhs($value): float
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

function formatAmount(float $n): string
{
    return number_format($n, 5, '.', '');
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
    foreach (['d-M-Y', 'd-m-Y', 'd/m/Y', 'Y-m-d', 'd-M-y', 'j-M-Y'] as $fmt) {
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

function displayMsNo(string $value): string
{
    $value = strtoupper(trim(preg_replace('/\s+/', ' ', $value)));
    $value = preg_replace('/\(\s+/', '(', $value);
    $value = preg_replace('/\s+\)/', ')', $value);

    return preg_replace('/\s+/', ' ', $value);
}

function msKey(string $value): string
{
    return strtoupper(preg_replace('/[^A-Z0-9]/', '', $value));
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

function codeKeys(string $code): array
{
    $norm = normalizeSlsCode($code);
    if ($norm === '') {
        return [];
    }
    $keys = [$norm];
    if (preg_match('/^[A-Z]+(\d+)$/', $norm, $m)) {
        $keys[] = $m[1];
    }

    return array_values(array_unique($keys));
}

function extractSlsCode(string $sls): string
{
    $s = strtoupper(trim($sls));
    $s = str_replace(['_', '.'], ' ', $s);
    if (preg_match('/^([A-Z]{2,3})\s*-?\s*(\d+)/', $s, $m)) {
        return normalizeSlsCode($m[1] . $m[2]);
    }
    if (preg_match('/^(\d+)/', $s, $m)) {
        return $m[1];
    }

    return '';
}

function extractSlsCodeFromMsNo(string $msNo): string
{
    if (preg_match('/\(([^)]+)\)/', $msNo, $m)) {
        return normalizeSlsCode($m[1]);
    }

    return '';
}

function schemeName(string $slsScheme): string
{
    $pos = strpos($slsScheme, '-');

    return $pos === false ? trim($slsScheme) : trim(substr($slsScheme, $pos + 1));
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
    $key = str_replace(['_', '/', '.', '(', ')'], ' ', $key);
    $key = preg_replace('/\s+/', ' ', $key);
    $key = trim($key);

    return PD_ALIASES[$key] ?? trim($pdShort);
}

function amountsClose(float $a, float $b, float $tolerance = AMOUNT_TOLERANCE): bool
{
    return abs($a - $b) <= $tolerance;
}

function sameBudgetAmounts(array $stored, array $incoming): bool
{
    $keys = array_unique(array_merge(array_keys($stored), array_keys($incoming)));
    $diff = 0.0;
    foreach ($keys as $key) {
        $diff += abs((float) ($stored[$key] ?? 0) - (float) ($incoming[$key] ?? 0));
    }

    return $diff <= 0.5;
}

if (!is_file(AMOUNT_PATH) || !is_file(DRILL1_PATH)) {
    fwrite(STDERR, "Excel file not found.\n");
    exit(1);
}

echo $commit ? "COMMIT mode: rows will be inserted.\n" : "DRY RUN: no database writes. Pass --commit to insert.\n";

DB::connection()->disableQueryLog();

$states = DB::table('states')->select('id', 'name')->get();
$stateIdByKey = [];
foreach ($states as $state) {
    $key = normalizeStateKey($state->name);
    $stateIdByKey[$key] = (int) $state->id;
}
foreach (STATE_ALIASES as $alias => $canonical) {
    if (isset($stateIdByKey[$canonical])) {
        $stateIdByKey[$alias] = $stateIdByKey[$canonical];
    }
}

$matchStateId = function (string $excelState) use ($stateIdByKey): ?int {
    $key = normalizeStateKey($excelState);
    if ($key !== '' && isset($stateIdByKey[$key])) {
        return $stateIdByKey[$key];
    }
    foreach ($stateIdByKey as $dbKey => $id) {
        if ($key !== '' && (str_starts_with($dbKey, $key) || str_starts_with($key, $dbKey))) {
            return $id;
        }
    }

    return null;
};

$pdByStateCode = [];
foreach (DB::table('pd_and_sls_comp')->get() as $row) {
    foreach (codeKeys((string) $row->sls_code) as $code) {
        $pdByStateCode[(int) $row->state_id . '|' . $code] = $row;
    }
}

$bhCategory = [];
foreach (DB::table('budget_heads')->get(['budget', 'category']) as $bh) {
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

echo "Parsing Drill1...\n";
$drillBook = IOFactory::load(DRILL1_PATH);
$drillSheet = $drillBook->getActiveSheet();
$drillLast = (int) $drillSheet->getHighestDataRow();
$drillRows = [];
$currentIndex = -1;

for ($r = 12; $r <= $drillLast; $r++) {
    $msNo = cellStr($drillSheet, 3, $r);
    $stateName = cellStr($drillSheet, 4, $r);
    $statusText = cellStr($drillSheet, 6, $r);
    $sanctionDate = cellStr($drillSheet, 7, $r);
    $msAmount = cellValue($drillSheet, 8, $r);
    $slsScheme = cellStr($drillSheet, 19, $r);

    if ($msNo !== '' && $stateName !== '') {
        $fromMs = extractSlsCodeFromMsNo($msNo);
        $drillRows[] = [
            'ms_no' => displayMsNo($msNo),
            'ms_key' => msKey($msNo),
            'state_name' => $stateName,
            'state_id' => $matchStateId($stateName),
            'status_text' => $statusText,
            'sanction_date' => parseDate($sanctionDate),
            'amount_lakhs' => parseRupeesToLakhs($msAmount),
            'codes' => codeKeys($fromMs),
            'scheme_name' => '',
            'is_active' => strcasecmp($statusText, 'Active') === 0,
        ];
        $currentIndex = count($drillRows) - 1;
        continue;
    }

    if ($currentIndex >= 0 && $slsScheme !== '' && stripos($slsScheme, 'Net Daily') === false) {
        foreach (codeKeys(extractSlsCode($slsScheme)) as $code) {
            if (!in_array($code, $drillRows[$currentIndex]['codes'], true)) {
                $drillRows[$currentIndex]['codes'][] = $code;
            }
        }
        $name = schemeName($slsScheme);
        if ($name !== '') {
            $drillRows[$currentIndex]['scheme_name'] = $name;
        }
    }
}
unset($drillBook);

$drillByStateCode = [];
foreach ($drillRows as $idx => $row) {
    if (!$row['state_id']) {
        continue;
    }
    foreach ($row['codes'] as $code) {
        $drillByStateCode[$row['state_id'] . '|' . $code][] = $idx;
    }
}
echo 'Drill1 mother sanctions: ' . count($drillRows) . "\n";

echo "Loading existing mother_sanction rows...\n";
$existingRows = DB::table('mother_sanction')->where('financial_year', FINANCIAL_YEAR)->get();
$existingMsKeys = [];
$existingByStateCode = [];
$releasedByPdBh = [];

foreach ($existingRows as $row) {
    $key = msKey((string) $row->ky_ms_no);
    $existingMsKeys[$key] = true;
    $code = extractSlsCodeFromMsNo((string) $row->ky_ms_no);
    if ($code === '') {
        $code = extractSlsCodeFromMsNo((string) $row->ifd_no);
    }
    $groupKey = $key !== '' ? $key : ('id:' . $row->id);
    if (!isset($existingByStateCode['_groups'][$groupKey])) {
        $existingByStateCode['_groups'][$groupKey] = [
            'state_id' => (int) $row->state_id,
            'codes' => codeKeys($code),
            'ky_ms_no' => displayMsNo((string) $row->ky_ms_no),
            'sls_name' => (string) $row->sls_name,
            'pd_component' => (string) $row->pd_component,
            'sanction_date' => (string) $row->sanction_date,
            'sequence' => (int) $row->ms_sequence_no,
            'amounts' => [],
        ];
    }
    $group = &$existingByStateCode['_groups'][$groupKey];
    $bh = trim((string) $row->budget_head);
    $group['amounts'][$bh] = ($group['amounts'][$bh] ?? 0) + (float) $row->mother_sanction_amount;
    $group['sequence'] = max($group['sequence'], (int) $row->ms_sequence_no);
    if ((string) $row->sanction_date > $group['sanction_date']) {
        $group['sanction_date'] = (string) $row->sanction_date;
        $group['ky_ms_no'] = displayMsNo((string) $row->ky_ms_no);
    }
    unset($group);

    if (strtoupper((string) $row->action_type) === 'CLOSED') {
        continue;
    }
    $releasedKey = strtoupper(trim((string) $row->pd_component)) . '|' . trim((string) $row->budget_head);
    $releasedByPdBh[$releasedKey] = ($releasedByPdBh[$releasedKey] ?? 0) + (float) $row->mother_sanction_amount;
}

$groupsByStateCode = [];
foreach ($existingByStateCode['_groups'] as $group) {
    foreach ($group['codes'] as $code) {
        $groupsByStateCode[$group['state_id'] . '|' . $code][] = $group;
    }
}

$lookupGroups = function (int $stateId, string $code) use ($groupsByStateCode): array {
    $full = $groupsByStateCode[$stateId . '|' . $code] ?? [];
    if ($full) {
        return $full;
    }
    if (preg_match('/^[A-Z]+(\d+)$/', $code, $m)) {
        return $groupsByStateCode[$stateId . '|' . $m[1]] ?? [];
    }

    return [];
};

$lookupPd = function (int $stateId, string $code) use ($pdByStateCode) {
    if (isset($pdByStateCode[$stateId . '|' . $code])) {
        return $pdByStateCode[$stateId . '|' . $code];
    }
    if (preg_match('/^[A-Z]+(\d+)$/', $code, $m) && isset($pdByStateCode[$stateId . '|' . $m[1]])) {
        return $pdByStateCode[$stateId . '|' . $m[1]];
    }

    return null;
};

$lookupDrill = function (int $stateId, string $code) use ($drillByStateCode, $drillRows): array {
    $idxs = [];
    $keys = codeKeys($code);
    if (isset($drillByStateCode[$stateId . '|' . $code])) {
        foreach ($drillByStateCode[$stateId . '|' . $code] as $idx) {
            $idxs[$idx] = true;
        }
    }
    if (!$idxs) {
        foreach ($keys as $key) {
            foreach ($drillByStateCode[$stateId . '|' . $key] ?? [] as $idx) {
                $idxs[$idx] = true;
            }
        }
    }
    $rows = [];
    foreach (array_keys($idxs) as $idx) {
        $rows[] = $drillRows[$idx];
    }

    return $rows;
};

echo "Parsing amount workbook...\n";
$amountBook = IOFactory::load(AMOUNT_PATH);
$excelRows = [];
$warnings = [];

foreach ($amountBook->getAllSheets() as $sheet) {
    $title = $sheet->getTitle();
    if (!in_array($title, IMPORT_SHEETS, true)) {
        echo "  skip sheet: {$title}\n";
        continue;
    }

    $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
    $highestRow = (int) $sheet->getHighestDataRow();
    $bhCols = [];
    for ($c = 7; $c <= $highestCol; $c++) {
        $bh = extractBudgetHead(cellStr($sheet, $c, 3));
        if ($bh !== '') {
            $bhCols[$c] = $bh;
        }
    }
    echo "  {$title}: BH " . implode(', ', $bhCols) . "\n";

    $stateName = '';
    for ($r = 4; $r <= $highestRow; $r++) {
        $stateCell = cellStr($sheet, 2, $r);
        if ($stateCell !== '' && !preg_match('/^(s\.?no|state|total)\b/i', $stateCell)) {
            $stateName = $stateCell;
        }
        $slsCodeRaw = cellStr($sheet, 4, $r);
        $pdShort = cellStr($sheet, 3, $r);
        if ($slsCodeRaw === '' || preg_match('/^(s\.?no|sls|total)\b/i', $slsCodeRaw)) {
            continue;
        }

        $bhAmounts = [];
        foreach ($bhCols as $col => $bh) {
            $amt = parseLakhs(cellValue($sheet, $col, $r));
            if ($amt > 0) {
                $bhAmounts[$bh] = ($bhAmounts[$bh] ?? 0) + $amt;
            }
        }
        $sheetTotal = parseLakhs(cellValue($sheet, 6, $r));
        $bhTotal = round(array_sum($bhAmounts), 5);
        if ($bhTotal <= 0) {
            continue;
        }
        if ($sheetTotal > 0 && !amountsClose($sheetTotal, $bhTotal, 0.02)) {
            $warnings[] = "{$title} R{$r} {$stateName} {$slsCodeRaw}: column F {$sheetTotal} != BH sum {$bhTotal}";
        }

        $stateId = $matchStateId($stateName);
        if (!$stateId) {
            $warnings[] = "{$title} R{$r} unmatched state '{$stateName}' for {$slsCodeRaw}";
            continue;
        }

        $excelRows[] = [
            'sheet' => $title,
            'row' => $r,
            'state_name' => $stateName,
            'state_id' => $stateId,
            'pd_short' => $pdShort,
            'sls_code' => normalizeSlsCode($slsCodeRaw),
            'sheet_total' => $sheetTotal,
            'total' => $bhTotal,
            'bh_amounts' => $bhAmounts,
        ];
    }
}
unset($amountBook);
echo 'Excel SLS rows with a released amount: ' . count($excelRows) . "\n";

$pending = [];
$skippedExisting = 0;
$usedDrill = [];
$withDrillNumber = 0;
$withoutDrillNumber = 0;

foreach ($excelRows as $excel) {
    $prior = $lookupGroups($excel['state_id'], $excel['sls_code']);
    $already = false;
    foreach ($prior as $group) {
        if (sameBudgetAmounts($group['amounts'], $excel['bh_amounts'])) {
            $already = true;
            break;
        }
    }
    if ($already) {
        $skippedExisting++;
        continue;
    }

    $pdRow = $lookupPd($excel['state_id'], $excel['sls_code']);
    $drillCandidates = [];
    foreach ($lookupDrill($excel['state_id'], $excel['sls_code']) as $drill) {
        if (isset($existingMsKeys[$drill['ms_key']]) || isset($usedDrill[$drill['ms_key']])) {
            continue;
        }
        $drillCandidates[] = $drill;
    }

    $amountMatches = array_values(array_filter($drillCandidates, function ($drill) use ($excel) {
        return amountsClose($drill['amount_lakhs'], $excel['total'])
            || ($excel['sheet_total'] > 0 && amountsClose($drill['amount_lakhs'], $excel['sheet_total']));
    }));
    $pool = $amountMatches ?: $drillCandidates;
    usort($pool, function ($a, $b) {
        if ($a['is_active'] !== $b['is_active']) {
            return $a['is_active'] ? -1 : 1;
        }

        return strcmp((string) $b['sanction_date'], (string) $a['sanction_date']);
    });
    $drill = $pool[0] ?? null;
    $amountMatched = $drill && $amountMatches;

    if ($drill) {
        $usedDrill[$drill['ms_key']] = true;
        $withDrillNumber++;
        if (!$amountMatched) {
            $warnings[] = "{$excel['sheet']} R{$excel['row']} {$excel['state_name']} / {$excel['sls_code']}: Excel {$excel['total']} saved under Drill1 {$drill['ms_no']} ({$drill['amount_lakhs']} lakhs, {$drill['status_text']})";
        }
    } else {
        $withoutDrillNumber++;
        $warnings[] = "{$excel['sheet']} R{$excel['row']} {$excel['state_name']} / {$excel['sls_code']}: no separate Drill1 sanction; saved as the next sequence under the existing sanction number";
    }

    $slsName = '';
    $pdComponent = '';
    $maxSequence = 0;
    $baseMsNo = '';
    $baseDate = null;
    if ($prior) {
        usort($prior, fn ($a, $b) => $b['sequence'] <=> $a['sequence']);
        $slsName = $prior[0]['sls_name'];
        $pdComponent = $prior[0]['pd_component'];
        $maxSequence = max(array_map(fn ($g) => (int) $g['sequence'], $prior));
        $baseMsNo = $prior[0]['ky_ms_no'];
        $baseDate = $prior[0]['sanction_date'] ?: null;
    }
    if ($slsName === '' && $pdRow) {
        $slsName = trim((string) $pdRow->name);
    }
    if ($slsName === '' && $drill && $drill['scheme_name'] !== '') {
        $slsName = $drill['scheme_name'];
    }
    if ($pdComponent === '') {
        $pdComponent = $pdRow && trim((string) $pdRow->slsPD) !== ''
            ? trim((string) $pdRow->slsPD)
            : mapProgramDivision($excel['pd_short']);
    }
    if ($slsName === '') {
        $slsName = $excel['sls_code'];
        $warnings[] = "{$excel['sheet']} R{$excel['row']} {$excel['state_name']} / {$excel['sls_code']}: SLS name not in master; stored the SLS code as the name";
    }

    $sequence = $maxSequence > 0 ? $maxSequence + 1 : 2;
    if ($drill) {
        $kyMsNo = $drill['ms_no'];
        $sanctionDate = $drill['sanction_date'] ?: ($baseDate ?: date('Y-m-d'));
        $remark = $amountMatched
            ? '2nd tranche'
            : '2nd tranche; Excel amount differs from Drill1 sanction of ' . formatAmount($drill['amount_lakhs']) . ' lakhs';
    } else {
        $suffix = 'T' . $sequence;
        $kyMsNo = $baseMsNo !== '' ? ($baseMsNo . ' ' . $suffix) : ($suffix . ' (' . $excel['sls_code'] . ')');
        $sanctionDate = $baseDate ?: date('Y-m-d');
        $remark = '2nd tranche; Drill1 has no separate sanction number';
    }

    $pending[] = [
        'excel' => $excel,
        'ky_ms_no' => $kyMsNo,
        'sanction_date' => $sanctionDate,
        'sls_name' => $slsName,
        'pd_component' => $pdComponent,
        'pd_id' => $pdIdByName[strtoupper(trim($pdComponent))] ?? null,
        'sequence' => $sequence,
        'remark' => $remark,
        'from_drill' => (bool) $drill,
    ];
}

echo 'Ready to insert SLS tranches: ' . count($pending) . "\n";
echo "  with a Drill1 sanction number: {$withDrillNumber}\n";
echo "  next sequence, no separate Drill1 number: {$withoutDrillNumber}\n";
echo "Already stored, skipped: {$skippedExisting}\n";

$bhLines = 0;
$amountTotal = 0.0;
$seqCounts = [];
$sheetCounts = [];
foreach ($pending as $item) {
    $bhLines += count($item['excel']['bh_amounts']);
    $amountTotal += $item['excel']['total'];
    $seq = (string) $item['sequence'];
    $seqCounts[$seq] = ($seqCounts[$seq] ?? 0) + 1;
    $sheet = $item['excel']['sheet'];
    $sheetCounts[$sheet] = ($sheetCounts[$sheet] ?? 0) + 1;
}
echo "Budget-head rows: {$bhLines}\n";
echo 'Amount total (lakhs): ' . formatAmount($amountTotal) . "\n";
foreach ($sheetCounts as $sheet => $count) {
    echo "  {$sheet}: {$count} SLS\n";
}
foreach ($seqCounts as $seq => $count) {
    echo "  sequence {$seq}: {$count} SLS\n";
}

if ($warnings) {
    echo "\nWarnings (" . count($warnings) . "):\n";
    foreach ($warnings as $warning) {
        echo "  - {$warning}\n";
    }
}

if (!$commit) {
    echo "\nDry run finished. No rows inserted.\n";
    exit(0);
}

if ($pending === []) {
    echo "\nNothing to insert.\n";
    exit(0);
}

$now = Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s');
$inserted = 0;

DB::beginTransaction();
try {
    foreach ($pending as $item) {
        $excel = $item['excel'];
        $firstId = null;

        foreach ($excel['bh_amounts'] as $budgetHead => $msAmount) {
            $category = $bhCategory[$budgetHead] ?? '';
            $releasedKey = strtoupper(trim($item['pd_component'])) . '|' . $budgetHead;
            $allocation = 0.0;
            if ($item['pd_id'] && isset($allocations[$item['pd_id'] . '|' . $budgetHead])) {
                $allocation = $allocations[$item['pd_id'] . '|' . $budgetHead];
            }
            $alreadyReleased = $releasedByPdBh[$releasedKey] ?? 0.0;
            $availableFund = max(0.0, round($allocation - $alreadyReleased, 5));

            $record = MotherSanction::create([
                'financial_year' => FINANCIAL_YEAR,
                'state_id' => $excel['state_id'],
                'ms_sequence_no' => (string) $item['sequence'],
                'file_no' => '',
                'ifd_no' => $item['ky_ms_no'],
                'sanction_date' => $item['sanction_date'],
                'ky_ms_no' => $item['ky_ms_no'],
                'sls_name' => $item['sls_name'],
                'pd_component' => $item['pd_component'],
                'total_mother_sanction_amount' => formatAmount($excel['total']),
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
                'remark' => $item['remark'],
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
                'change_description' => 'New mother sanction record created (2nd tranche Excel import)',
                'old_mother_sanction_amount' => $record->mother_sanction_amount,
                'new_mother_sanction_amount' => $record->mother_sanction_amount,
                'old_available_fund' => $record->available_fund,
                'new_available_fund' => $record->available_fund,
                'history_timestamp' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $releasedByPdBh[$releasedKey] = $alreadyReleased + $msAmount;
            $inserted++;
        }
    }

    DB::commit();
    echo "\n=== IMPORT COMPLETE ===\n";
    echo "mother_sanction rows inserted: {$inserted}\n";
    echo 'mother_sanction table count: ' . DB::table('mother_sanction')->count() . "\n";
} catch (Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, 'IMPORT FAILED: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
    exit(1);
}
