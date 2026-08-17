<?php

/**
 * Import Active mother sanctions from Drill1_Page.xlsx and corresponding
 * daily sanctions from Drill3_Page.xlsx into mother_sanction / daily_sanction.
 *
 * Usage: php database/scripts/import_sparsh_sanctions_2026_2027.php
 */

ini_set('memory_limit', '1024M');
set_time_limit(0);

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DailySanction;
use App\Models\DailySanctionHistory;
use App\Models\MotherSanction;
use App\Models\MotherSanctionHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

const FINANCIAL_YEAR = '2026-2027';
const DRILL1 = 'C:/Users/piyush_singh/Documents/AGRICULTURE/KYAS-documents/kyas_upload/2026-2027/Drill1_Page.xlsx';
const DRILL3 = 'C:/Users/piyush_singh/Documents/AGRICULTURE/KYAS-documents/kyas_upload/2026-2027/Drill3_Page.xlsx';

function cell($sheet, int $col, int $row): string
{
    $value = $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row)->getFormattedValue();
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
    } catch (\Throwable $e) {
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

function msPrefixFromDailySanctionNo(string $dailySanctionNo): string
{
    $pos = strpos($dailySanctionNo, '-');
    return $pos === false ? trim($dailySanctionNo) : trim(substr($dailySanctionNo, 0, $pos));
}

function formatBudgetHead(string $functionHead, string $objectHead): string
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

function slsNameAfterDash(string $slsScheme): string
{
    $pos = strpos($slsScheme, '-');
    return $pos === false ? trim($slsScheme) : trim(substr($slsScheme, $pos + 1));
}

function extractSlsCode(string $motherSanctionNo, string $slsScheme = ''): string
{
    if (preg_match('/\(([^)]+)\)/', $motherSanctionNo, $m)) {
        return strtoupper(str_replace(' ', '', $m[1]));
    }
    $pos = strpos($slsScheme, '-');
    if ($pos !== false) {
        return strtoupper(str_replace(' ', '', trim(substr($slsScheme, 0, $pos))));
    }
    return '';
}

function normalizeStateName(string $name): string
{
    $name = strtoupper(trim($name));
    $name = preg_replace('/\[[^\]]*\]/', '', $name);
    $name = preg_replace('/\((UT|NCT)\)/i', '', $name);
    $name = str_replace(['&', ','], ' ', $name);
    $name = preg_replace('/\bAND\b/', ' ', $name);
    $name = preg_replace('/[^A-Z0-9 ]/', ' ', $name);
    return trim(preg_replace('/\s+/', ' ', $name));
}

function inferProgramDivision(string $slsName): string
{
    $u = strtoupper($slsName);
    if (str_contains($u, 'BAMBOO')) {
        return 'National Bamboo Mission';
    }
    if (str_contains($u, 'OIL PALM') || str_contains($u, 'OILPALM') || str_contains($u, 'NMEO-OP') || str_contains($u, 'SEED GARDEN')) {
        return 'National Mission on Edible Oils-Oil Palm';
    }
    if (str_contains($u, 'COTTON')) {
        return 'Mission Cotton';
    }
    if (str_contains($u, 'PULSE')) {
        return 'Mission Pulses';
    }
    if (str_contains($u, 'ORGANIC') || str_contains($u, 'MOVCD')) {
        return 'Mission Organic Value Chain Development for North East Region';
    }
    if (str_contains($u, 'OILSEED') || str_contains($u, 'OIL SEED') || str_contains($u, 'NMEO-OS') || str_contains($u, 'EDIBLE OIL')) {
        return 'National Mission on Edible Oils-Oil Seeds';
    }
    if (str_contains($u, 'HORTICULTURE') || str_contains($u, 'HMNEH') || str_contains($u, 'MIDH')) {
        return 'Mission for Integrated Development of Horticulture';
    }
    if (str_contains($u, 'EXTENSION') || str_contains($u, 'SMAE')) {
        return 'Sub-mission on Agriculture Extension';
    }
    if (str_contains($u, 'DIGITAL') || str_contains($u, 'NEGP') || str_contains($u, 'E- GOVERNANCE') || str_contains($u, 'EGOVERNANCE')) {
        return 'Digital Agriculture Mission';
    }
    if (str_contains($u, 'NFSM') || str_contains($u, 'FOOD SECURITY') || str_contains($u, 'NUTRITION')) {
        return 'National Food Security and Nutrition Mission';
    }
    if (str_contains($u, 'SEED') || str_contains($u, 'PLANTING')) {
        return 'Sub Mission on Seed and Planting';
    }
    if (str_contains($u, 'MARKETING')) {
        return 'Integrated Scheme for Agricultural Marketing';
    }
    return '';
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

echo "Loading master data...\n";
DB::connection()->disableQueryLog();

$states = DB::table('states')->select('id', 'name')->get();
$stateIdByNorm = [];
foreach ($states as $state) {
    $stateIdByNorm[normalizeStateName($state->name)] = (int) $state->id;
}

$pdSls = DB::table('pd_and_sls_comp')->get();
$pdByFull = [];
$pdByStateName = [];
$pdByCode = [];
foreach ($pdSls as $row) {
    $full = strtoupper(trim((string) $row->full_sls_name));
    $name = strtoupper(trim((string) $row->name));
    $code = strtoupper(str_replace(' ', '', trim((string) $row->sls_code)));
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

$budgetHeads = DB::table('budget_heads')->get(['id', 'budget', 'category']);
$bhCategory = [];
$bhId = [];
foreach ($budgetHeads as $bh) {
    $bhCategory[$bh->budget] = (string) $bh->category;
    $bhId[$bh->budget] = (int) $bh->id;
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
    ->select('pda.pd_id', 'bh.budget', DB::raw('SUM(pda.amount) as amt'))
    ->groupBy('pda.pd_id', 'bh.budget')
    ->get();
foreach ($allocRows as $row) {
    $allocations[$row->pd_id . '|' . $row->budget] = (float) $row->amt;
}

function matchStateId(string $excelState, array $stateIdByNorm): ?int
{
    $norm = normalizeStateName($excelState);
    if (isset($stateIdByNorm[$norm])) {
        return $stateIdByNorm[$norm];
    }
    foreach ($stateIdByNorm as $dbNorm => $id) {
        if ($norm !== '' && (str_starts_with($dbNorm, $norm) || str_starts_with($norm, $dbNorm))) {
            return $id;
        }
    }
    return null;
}

function matchPdSls(
    string $slsScheme,
    string $slsName,
    string $slsCode,
    ?int $stateId,
    array $pdByFull,
    array $pdByStateName,
    array $pdByCode
): ?object {
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
    if ($slsCode !== '') {
        $codeKey = strtoupper($slsCode);
        if ($stateId && isset($pdByCode[$stateId . '|' . $codeKey])) {
            return $pdByCode[$stateId . '|' . $codeKey];
        }
        if (isset($pdByCode[$codeKey])) {
            return $pdByCode[$codeKey];
        }
    }
    return null;
}

echo "Parsing Drill1 (mother sanction)...\n";
if (!file_exists(DRILL1) || !file_exists(DRILL3)) {
    fwrite(STDERR, "Excel file not found.\n");
    exit(1);
}

$ss1 = IOFactory::load(DRILL1);
$sheet1 = $ss1->getActiveSheet();
$highest1 = $sheet1->getHighestDataRow();

$allMs = [];
$currentIndex = -1;
$occurrenceByNorm = [];

for ($r = 12; $r <= $highest1; $r++) {
    $msNo = cell($sheet1, 3, $r);
    $stateName = cell($sheet1, 4, $r);
    $statusText = cell($sheet1, 6, $r);
    $sanctionDate = cell($sheet1, 7, $r);
    $msAmount = cell($sheet1, 8, $r);
    $cfAmount = cell($sheet1, 9, $r);
    $slsScheme = cell($sheet1, 19, $r);

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
            'cf_amount_lakhs' => parseAmountToLakhs($cfAmount),
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

$activeMs = [];
$warnings = [];
foreach ($allMs as $ms) {
    if (strcasecmp((string) $ms['status_text'], 'Active') !== 0) {
        continue;
    }
    $stateId = matchStateId($ms['state_name'], $stateIdByNorm);
    if (!$stateId) {
        $warnings[] = "Unmatched state '{$ms['state_name']}' for MS {$ms['ms_no']}";
        continue;
    }
    $slsName = slsNameAfterDash($ms['sls_scheme']);
    $slsCode = extractSlsCode($ms['ms_no'], $ms['sls_scheme']);
    $pdRow = matchPdSls($ms['sls_scheme'], $slsName, $slsCode, $stateId, $pdByFull, $pdByStateName, $pdByCode);
    $pdComponent = $pdRow->slsPD ?? inferProgramDivision($slsName !== '' ? $slsName : $ms['sls_scheme']);
    if ($pdRow && trim((string) $pdRow->name) !== '') {
        $slsName = trim((string) $pdRow->name);
    }
    $ms['state_id'] = $stateId;
    $ms['sls_name'] = $slsName;
    $ms['pd_component'] = $pdComponent;
    $ms['pd_id'] = $pdIdByName[strtoupper(trim((string) $pdComponent))] ?? null;
    $activeMs[$ms['ms_norm']] = $ms;
}

echo 'Active mother sanctions parsed: ' . count($activeMs) . "\n";

echo "Parsing Drill3 (daily sanction)...\n";
$ss3 = IOFactory::load(DRILL3);
$sheet3 = $ss3->getActiveSheet();
$highest3 = $sheet3->getHighestDataRow();
unset($ss1, $sheet1);

$curSls = '';
$curDsNo = '';
$curDate = '';
$curStatus = '';
$curObj = '';
$dsRows = [];
$dsBhTotals = [];

for ($r = 11; $r <= $highest3; $r++) {
    $sls = cell($sheet3, 3, $r);
    $dsNo = cell($sheet3, 6, $r);
    $date = cell($sheet3, 9, $r);
    $status = cell($sheet3, 10, $r);
    $obj = cell($sheet3, 12, $r);
    $fn = cell($sheet3, 13, $r);
    $amt = cell($sheet3, 14, $r);

    if ($sls !== '') {
        $curSls = $sls;
    }
    if ($dsNo !== '') {
        $curDsNo = $dsNo;
    }
    if ($date !== '') {
        $curDate = $date;
    }
    if ($status !== '') {
        $curStatus = $status;
    }
    if ($obj !== '' && stripos($obj, 'Total') === false) {
        $curObj = $obj;
    }

    if ($curDsNo === '' || $fn === '' || stripos($fn, 'Total') !== false || stripos($fn, 'Grand') !== false) {
        continue;
    }
    $fnDigits = preg_replace('/[^0-9]/', '', $fn);
    if (strlen($fnDigits) < 10) {
        continue;
    }

    $amountLakhs = parseAmountToLakhs($amt);
    if ($amountLakhs <= 0) {
        continue;
    }

    $msPrefix = msPrefixFromDailySanctionNo($curDsNo);
    $msNorm = normalizeMsNo($msPrefix);
    if (!isset($activeMs[$msNorm])) {
        continue;
    }

    $budgetHead = formatBudgetHead($fn, $curObj);
    $dsDate = parseDate($curDate) ?: date('Y-m-d');
    $key = $curDsNo . '|' . $budgetHead . '|' . $dsDate;
    if (!isset($dsRows[$key])) {
        $dsRows[$key] = [
            'ms_norm' => $msNorm,
            'daily_sanction_no' => $curDsNo,
            'ms_prefix' => $msPrefix,
            'ds_date' => $dsDate,
            'budget_head' => $budgetHead,
            'center_share_amount' => 0.0,
            'sls_scheme' => $curSls,
            'status_text' => $curStatus,
        ];
    }
    $dsRows[$key]['center_share_amount'] += $amountLakhs;
    $dsBhTotals[$msNorm][$budgetHead] = ($dsBhTotals[$msNorm][$budgetHead] ?? 0.0) + $amountLakhs;
}
unset($ss3, $sheet3);

echo 'Daily sanction BH rows parsed: ' . count($dsRows) . "\n";

$now = Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s');

echo "Clearing existing 2026-2027 sanction rows (if any)...\n";
DB::beginTransaction();
try {
    $existingMsIds = DB::table('mother_sanction')->where('financial_year', FINANCIAL_YEAR)->pluck('id');
    if ($existingMsIds->isNotEmpty()) {
        DB::table('mother_sanction_history')->whereIn('mother_sanction_id', $existingMsIds)->delete();
        DB::table('mother_sanction')->where('financial_year', FINANCIAL_YEAR)->delete();
    }
    $existingDsIds = DB::table('daily_sanction')->where('financial_year', FINANCIAL_YEAR)->pluck('id');
    if ($existingDsIds->isNotEmpty()) {
        DB::table('daily_sanction_history')->whereIn('daily_sanction_id', $existingDsIds)->delete();
        DB::table('daily_sanction')->where('financial_year', FINANCIAL_YEAR)->delete();
    }

    echo "Inserting mother_sanction rows...\n";
    $msInserted = 0;
    $msBhAmountByNorm = [];

    foreach ($activeMs as $msNorm => $ms) {
        $bhWeights = $dsBhTotals[$msNorm] ?? [];
        if (empty($bhWeights)) {
            $bhWeights = ['' => 1.0];
        }
        $msAmounts = splitAmountByWeights((float) $ms['ms_amount_lakhs'], $bhWeights);
        $msBhAmountByNorm[$msNorm] = $msAmounts;

        $cfAssigned = false;
        $firstId = null;
        $pdId = $ms['pd_id'];

        foreach ($msAmounts as $budgetHead => $msAmount) {
            $category = $budgetHead !== '' ? ($bhCategory[$budgetHead] ?? '') : '';
            $allocation = 0.0;
            if ($pdId && $budgetHead !== '' && isset($allocations[$pdId . '|' . $budgetHead])) {
                $allocation = $allocations[$pdId . '|' . $budgetHead];
            }
            $availableFund = max(0.0, round($allocation - $msAmount, 5));
            $carryForward = 0.0;
            if (!$cfAssigned) {
                $carryForward = (float) $ms['cf_amount_lakhs'];
                $cfAssigned = true;
            }

            $record = MotherSanction::create([
                'financial_year' => FINANCIAL_YEAR,
                'state_id' => $ms['state_id'],
                'ms_sequence_no' => $ms['ms_sequence_no'],
                'file_no' => '',
                'ifd_no' => $ms['ms_no'],
                'sanction_date' => parseDate($ms['sanction_date']) ?: date('Y-m-d'),
                'ky_ms_no' => $ms['ms_no'],
                'sls_name' => $ms['sls_name'],
                'pd_component' => $ms['pd_component'],
                'total_mother_sanction_amount' => formatAmount((float) $ms['ms_amount_lakhs']),
                'budget_head' => $budgetHead,
                'category' => $category,
                'available_fund' => formatAmount($availableFund),
                'mother_sanction_amount' => formatAmount($msAmount),
                'carry_forward_amount' => $carryForward,
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

            $msInserted++;
        }
    }

    echo "Inserted mother_sanction rows: {$msInserted}\n";
    echo "Inserting daily_sanction rows...\n";

    $dsInserted = 0;
    foreach ($dsRows as $ds) {
        $ms = $activeMs[$ds['ms_norm']];
        $bhMsAmount = $msBhAmountByNorm[$ds['ms_norm']][$ds['budget_head']] ?? 0.0;
        $centerShare = round((float) $ds['center_share_amount'], 5);

        $record = DailySanction::create([
            'financial_year' => FINANCIAL_YEAR,
            'state_id' => $ms['state_id'],
            'ds_date' => $ds['ds_date'],
            'mother_sanction' => $ms['ms_no'],
            'daily_sanction_no' => $ds['daily_sanction_no'],
            'ifd_no' => $ms['ms_no'],
            'sls_name' => $ms['sls_name'],
            'budget_head' => $ds['budget_head'],
            'mother_sanction_amount' => formatAmount($bhMsAmount),
            'available_amount' => formatAmount($bhMsAmount),
            'center_share_amount' => formatAmount($centerShare),
            'remark' => '',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

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
            'action_type' => 'CREATE',
            'changed_by' => 'System',
            'change_description' => 'Daily sanction entry created via SPARSH Excel import',
            'old_center_share_amount' => $record->center_share_amount,
            'new_center_share_amount' => $record->center_share_amount,
            'history_timestamp' => $now,
        ]);

        $dsInserted++;
        if ($dsInserted % 500 === 0) {
            echo "  daily_sanction inserted: {$dsInserted}\n";
        }
    }

    DB::commit();

    echo "\n=== IMPORT COMPLETE ===\n";
    echo 'Active mother sanctions: ' . count($activeMs) . "\n";
    echo "mother_sanction rows inserted: {$msInserted}\n";
    echo "daily_sanction rows inserted: {$dsInserted}\n";
    echo 'mother_sanction table count: ' . DB::table('mother_sanction')->count() . "\n";
    echo 'daily_sanction table count: ' . DB::table('daily_sanction')->count() . "\n";
    if ($warnings) {
        echo "Warnings:\n";
        foreach ($warnings as $w) {
            echo "  - {$w}\n";
        }
    }
} catch (\Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, 'IMPORT FAILED: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
    exit(1);
}
