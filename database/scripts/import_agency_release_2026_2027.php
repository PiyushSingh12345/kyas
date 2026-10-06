<?php

/**
 * Import Agency Module data from KY RoG CSNA 2026-27.xlsx
 * - White rows  → agency_release_tsa
 * - Red rows    → agency_release_loa
 * - Blue rows   → agency_release_administrative_expenditure
 *
 * Usage: php database/scripts/import_agency_release_2026_2027.php [excel-path]
 */

ini_set('memory_limit', '512M');
set_time_limit(0);

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AgencyReleaseAdministrativeExpenditure;
use App\Models\AgencyReleaseHistory;
use App\Models\AgencyReleaseLOA;
use App\Models\AgencyReleaseTSA;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

const EXCEL_PATH = 'C:/Users/piyush_singh/Documents/AGRICULTURE/KYAS-documents/kyas_upload/2026-2027/secondTrunch/KY RoG CSNA - Copy.xlsx';

const COLOR_TSA = 'FFFFFF';
const COLOR_LOA = 'F4CCCC';
const COLOR_ADMIN = 'C9DAF8';

const PD_ALIASES = [
    'agriculture extension' => 2,
    'sub-mission on agriculture extension' => 2,
    'sub mission on agriculture extension' => 2,
    'smae' => 2,
    'digital agriculture' => 9,
    'digital agriculture mission' => 9,
    'mission pulses' => 12,
    'agriculture marketing' => 11,
    'integrated scheme for agricultural marketing' => 11,
    'oilpalm' => 7,
    'oil palm' => 7,
    'nmeo-op' => 7,
    'nmeo op' => 7,
    'fns' => 6,
    'f&ns' => 6,
    'f ns' => 6,
    'nfsm' => 6,
    'nfsnm' => 6,
    'national food security and nutrition mission' => 6,
    'oilseeds' => 8,
    'oil seeds' => 8,
    'nmeo-os' => 8,
    'nmeo os' => 8,
    'midh' => 3,
    'mission for integrated development of horticulture' => 3,
    'nbm' => 5,
    'national bamboo mission' => 5,
    'bamboo' => 5,
    'movcdner' => 4,
    'mission cotton' => 13,
    'cotton' => 13,
    'seeds' => 10,
    'seed' => 10,
    'sub mission on seed and planting' => 10,
    'sub-mission on seed and planting' => 10,
];

const UT_FORM_VALUES = [
    32 => 'Andaman and Nicobar',
    33 => 'Chandigarh',
    34 => 'Dadra & Nagar Haveli and Daman & Diu',
    35 => 'Ladakh',
];

function cell($sheet, string $col, int $row): string
{
    $value = $sheet->getCell($col . $row)->getFormattedValue();

    return trim(str_replace(["\xc2\xa0", "\n", "\r"], [' ', ' ', ''], (string) $value));
}

function rowColor($sheet, int $row): string
{
    $fill = $sheet->getStyle('A' . $row)->getFill();
    $rgb = strtoupper((string) $fill->getStartColor()->getRGB());
    $type = (string) $fill->getFillType();

    if ($type === '' || $type === 'none' || $rgb === '' || $rgb === '000000') {
        return COLOR_TSA;
    }

    return $rgb;
}

function normalizeKey(string $name): string
{
    $name = strtolower(trim($name));
    $name = str_replace(['&', '.', ',', '(', ')', '[', ']', '-', '_', '/'], ' ', $name);
    $name = preg_replace('/\s+/', ' ', $name);

    return trim($name);
}

function parseAmount($value): ?string
{
    if ($value === null) {
        return null;
    }
    $str = trim((string) $value);
    if ($str === '' || strcasecmp($str, '#NAME?') === 0 || strcasecmp($str, '#VALUE!') === 0 || strcasecmp($str, '#REF!') === 0) {
        return null;
    }
    $str = preg_replace('/[^0-9.\-]/', '', $str);
    if ($str === '' || $str === '-' || $str === '.') {
        return null;
    }

    return number_format((float) $str, 5, '.', '');
}

function numericCell($sheet, string $col, int $row): ?string
{
    $cell = $sheet->getCell($col . $row);

    $formatted = parseAmount($cell->getFormattedValue());
    if ($formatted !== null) {
        return $formatted;
    }

    $old = $cell->getOldCalculatedValue();
    if (is_numeric($old)) {
        return number_format((float) $old, 5, '.', '');
    }
    $parsedOld = parseAmount($old);
    if ($parsedOld !== null) {
        return $parsedOld;
    }

    try {
        $raw = $cell->getCalculatedValue();
    } catch (Throwable $e) {
        $raw = null;
    }
    if (is_numeric($raw)) {
        return number_format((float) $raw, 5, '.', '');
    }

    return parseAmount($raw);
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
    foreach (['d.m.Y', 'd-m-Y', 'd/m/Y', 'd.m.y', 'd-M-Y', 'd-M-y'] as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $str);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d');
        }
    }
    if (is_numeric($str)) {
        try {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $str))->format('Y-m-d');
        } catch (Throwable $e) {
            // fall through
        }
    }
    try {
        return Carbon::parse($str)->format('Y-m-d');
    } catch (Throwable $e) {
        return null;
    }
}

function saveHistory(string $type, $record, string $description, string $actionType = 'CREATE'): void
{
    AgencyReleaseHistory::create([
        'release_type' => $type,
        'release_id' => $record->id,
        'sanction_number' => $record->sanction_number ?? null,
        'date' => $record->date ?? null,
        'budget_head' => $record->budget_head ?? null,
        'purpose_of_grant' => $record->purpose_of_grant ?? null,
        'program_division_id' => $record->program_division_id ?? null,
        'amount' => $record->amount ?? null,
        'expenditure' => $type === 'tsa' ? ($record->expenditure ?? null) : null,
        'central_implementing_agency' => $record->central_implementing_agency ?? null,
        'ut' => $record->ut ?? null,
        'agency_vendor' => $record->agency_vendor ?? null,
        'status' => $record->status ?? 1,
        'action_type' => $actionType,
        'changed_by' => 'System',
        'change_description' => $description,
    ]);
}

function identityKey(array $row): string
{
    $extra = $row['central_implementing_agency'] ?? $row['ut'] ?? $row['agency_vendor'] ?? '';

    return strtolower($row['sanction_number']) . '|' . $row['budget_head'] . '|' . $row['program_division_id'] . '|' . strtolower(trim((string) $extra));
}

function valuesChanged($record, array $payload): bool
{
    foreach ($payload as $field => $value) {
        $current = $record->{$field};
        if ($field === 'date') {
            $current = $current instanceof DateTimeInterface ? $current->format('Y-m-d') : substr((string) $current, 0, 10);
        } elseif (in_array($field, ['amount', 'expenditure'], true)) {
            $curNum = $current === null || $current === '' ? null : number_format((float) $current, 5, '.', '');
            $newNum = $value === null || $value === '' ? null : number_format((float) $value, 5, '.', '');
            if ($curNum !== $newNum) {
                return true;
            }
            continue;
        } elseif (in_array($field, ['is_ner', 'status', 'program_division_id'], true)) {
            if ((int) $current !== (int) $value) {
                return true;
            }
            continue;
        }
        if ((string) ($current ?? '') !== (string) ($value ?? '')) {
            return true;
        }
    }

    return false;
}

if (!is_file(EXCEL_PATH)) {
    fwrite(STDERR, 'Excel file not found: ' . EXCEL_PATH . PHP_EOL);
    exit(1);
}

echo 'Excel: ' . EXCEL_PATH . PHP_EOL;

echo "Loading master data...\n";

$divisions = DB::table('md_program_divisions')->select('division_id', 'division_name')->get();
$pdByName = [];
foreach ($divisions as $division) {
    $pdByName[normalizeKey($division->division_name)] = (int) $division->division_id;
}

$resolvePdId = function (string $excelPd) use ($pdByName): ?int {
    $key = normalizeKey($excelPd);
    if ($key === '') {
        return null;
    }
    if (isset(PD_ALIASES[$key])) {
        return PD_ALIASES[$key];
    }
    if (isset($pdByName[$key])) {
        return $pdByName[$key];
    }
    foreach ($pdByName as $dbKey => $id) {
        if ($key !== '' && (str_contains($dbKey, $key) || str_contains($key, $dbKey))) {
            return $id;
        }
    }

    return null;
};

$budgetHeads = DB::table('budget_heads')->pluck('budget')->map(fn ($v) => trim((string) $v))->flip();

$states = DB::table('states')->select('id', 'name', 'description')->get();
$utStates = [];
foreach ($states as $state) {
    $desc = strtolower(trim((string) $state->description));
    if (str_contains($desc, 'ut')) {
        $utStates[(int) $state->id] = $state;
    }
}

$resolveUt = function (string $agency) use ($utStates): ?array {
    $u = strtoupper($agency);
    $matchId = null;
    $formValue = null;
    if (str_contains($u, 'LADAKH')) {
        $matchId = 35;
    } elseif (str_contains($u, 'A&N') || str_contains($u, 'ANDAMAN') || str_contains($u, 'NICOBAR')) {
        $matchId = 32;
    } elseif (str_contains($u, 'LAKSHADWEEP')) {
        $formValue = 'Lakshadweep';
        foreach ($utStates as $id => $state) {
            if (str_contains(strtoupper($state->name), 'LAKSHADWEEP')) {
                $matchId = $id;
                break;
            }
        }
        return [
            'id' => $matchId,
            'name' => $matchId !== null ? ($utStates[$matchId]->name ?? 'Lakshadweep') : 'Lakshadweep',
            'form_value' => $formValue,
        ];
    } elseif (str_contains($u, 'CHANDIGARH')) {
        $matchId = 33;
    } elseif (str_contains($u, 'DADRA') || str_contains($u, 'DAMAN') || str_contains($u, 'DIU')) {
        $matchId = 34;
    }

    if ($matchId === null || (!isset($utStates[$matchId]) && !isset(UT_FORM_VALUES[$matchId]))) {
        return null;
    }

    return [
        'id' => $matchId,
        'name' => $utStates[$matchId]->name ?? '',
        'form_value' => UT_FORM_VALUES[$matchId] ?? $utStates[$matchId]->name,
    ];
};

$nerPdIds = [];
foreach ($divisions as $division) {
    $name = strtoupper($division->division_name);
    if (str_contains($name, 'NORTH EAST') || str_contains($name, 'MOVCD')) {
        $nerPdIds[(int) $division->division_id] = true;
    }
}

// is_ner from BH + PD:
// - Major head 2552 is NER
// - MOVCDNER (PD 4) is the NER programme division
// - 2435 heads that are the 2552→2435 map are only NER when PD is NER
$resolveIsNer = function (string $budgetHead, int $pdId) use ($nerPdIds): int {
    $digits = preg_replace('/[^0-9]/', '', $budgetHead) ?: $budgetHead;
    if (str_starts_with($digits, '2552')) {
        return 1;
    }
    if (isset($nerPdIds[$pdId])) {
        return 1;
    }

    return 0;
};

echo "Loading Excel...\n";
$spreadsheet = IOFactory::load(EXCEL_PATH);
$sheet = $spreadsheet->getSheetByName('KY Cell') ?: $spreadsheet->getSheet(0);
$highestRow = (int) $sheet->getHighestRow();

$skippedColors = [];
$skipped = [];
$unmatchedPd = [];
$unmatchedUt = [];
$unknownBh = [];
$tsaRows = [];
$loaRows = [];
$adminRows = [];

for ($row = 5; $row <= $highestRow; $row++) {
    $sanction = cell($sheet, 'B', $row);
    $dateRaw = cell($sheet, 'C', $row);
    $budgetHead = cell($sheet, 'D', $row);
    $purpose = cell($sheet, 'E', $row);
    $pdName = cell($sheet, 'G', $row);
    $agency = cell($sheet, 'J', $row);
    $remark = cell($sheet, 'V', $row);
    $remark = $remark === '' ? null : $remark;

    if ($sanction === '' && $budgetHead === '' && $pdName === '') {
        continue;
    }

    $color = rowColor($sheet, $row);
    if (!in_array($color, [COLOR_TSA, COLOR_LOA, COLOR_ADMIN], true)) {
        $skippedColors[$color] = ($skippedColors[$color] ?? 0) + 1;
        continue;
    }

    if ($sanction === '') {
        $skipped[] = "Row {$row}: missing sanction number";
        continue;
    }

    $date = parseDate($dateRaw);
    if ($date === null) {
        $skipped[] = "Row {$row}: invalid date '{$dateRaw}'";
        continue;
    }

    if ($budgetHead === '') {
        $skipped[] = "Row {$row}: missing budget head";
        continue;
    }
    if (!isset($budgetHeads[$budgetHead])) {
        $unknownBh[$budgetHead] = true;
    }

    $pdId = $resolvePdId($pdName);
    if ($pdId === null) {
        $unmatchedPd[] = "Row {$row}: PD '{$pdName}'";
        $skipped[] = "Row {$row}: unmatched PD '{$pdName}'";
        continue;
    }

    $amount = numericCell($sheet, 'H', $row);
    if ($amount === null) {
        $skipped[] = "Row {$row}: missing/invalid amount";
        continue;
    }

    $base = [
        'excel_row' => $row,
        'sanction_number' => $sanction,
        'date' => $date,
        'budget_head' => $budgetHead,
        'purpose_of_grant' => $purpose !== '' ? $purpose : 'NA',
        'program_division_id' => $pdId,
        'amount' => $amount,
        'remark' => $remark,
        'status' => 1,
    ];

    if ($color === COLOR_TSA) {
        $expenditure = numericCell($sheet, 'U', $row);
        if ($expenditure !== null && (float) $expenditure > (float) $amount) {
            $expenditure = $amount;
        }
        $tsaRows[] = $base + [
            'expenditure' => $expenditure,
            'central_implementing_agency' => $agency !== '' ? $agency : 'NA',
            'is_ner' => $resolveIsNer($budgetHead, $pdId),
        ];
        continue;
    }

    if ($color === COLOR_LOA) {
        $ut = $resolveUt($agency);
        if ($ut === null) {
            $unmatchedUt[] = "Row {$row}: agency '{$agency}'";
            $skipped[] = "Row {$row}: unmatched UT from agency '{$agency}'";
            continue;
        }
        $loaRows[] = $base + [
            'ut' => $ut['form_value'],
            'ut_id' => $ut['id'],
            'ut_name' => $ut['name'],
        ];
        continue;
    }

    $adminRows[] = $base + [
        'agency_vendor' => $agency !== '' ? $agency : 'NA',
        'is_ner' => $resolveIsNer($budgetHead, $pdId),
    ];
}

echo "Parsed TSA: " . count($tsaRows) . " | LOA: " . count($loaRows) . " | Admin: " . count($adminRows) . "\n";
if ($skippedColors) {
    echo "Skipped other colours: " . json_encode($skippedColors) . "\n";
}
if ($unknownBh) {
    echo "Budget heads not in budget_heads table: " . implode(', ', array_keys($unknownBh)) . "\n";
}
if ($unmatchedPd) {
    echo "Unmatched PDs:\n  " . implode("\n  ", $unmatchedPd) . "\n";
}
if ($unmatchedUt) {
    echo "Unmatched UTs:\n  " . implode("\n  ", $unmatchedUt) . "\n";
}

$existingTsa = [];
foreach (DB::table('agency_release_tsa')->whereNull('deleted_at')->get(['id', 'sanction_number', 'date', 'budget_head', 'program_division_id', 'central_implementing_agency']) as $r) {
    $existingTsa[identityKey([
        'sanction_number' => $r->sanction_number,
        'budget_head' => $r->budget_head,
        'program_division_id' => $r->program_division_id,
        'central_implementing_agency' => $r->central_implementing_agency,
    ])] = (int) $r->id;
}
$existingLoa = [];
foreach (DB::table('agency_release_loa')->whereNull('deleted_at')->get(['id', 'sanction_number', 'date', 'budget_head', 'program_division_id', 'ut']) as $r) {
    $existingLoa[identityKey([
        'sanction_number' => $r->sanction_number,
        'budget_head' => $r->budget_head,
        'program_division_id' => $r->program_division_id,
        'ut' => $r->ut,
    ])] = (int) $r->id;
}
$existingAdmin = [];
foreach (DB::table('agency_release_administrative_expenditure')->whereNull('deleted_at')->get(['id', 'sanction_number', 'date', 'budget_head', 'program_division_id', 'agency_vendor']) as $r) {
    $existingAdmin[identityKey([
        'sanction_number' => $r->sanction_number,
        'budget_head' => $r->budget_head,
        'program_division_id' => $r->program_division_id,
        'agency_vendor' => $r->agency_vendor,
    ])] = (int) $r->id;
}

$inserted = ['tsa' => 0, 'loa' => 0, 'admin' => 0];
$updated = ['tsa' => 0, 'loa' => 0, 'admin' => 0];
$unchanged = ['tsa' => 0, 'loa' => 0, 'admin' => 0];
$nerCounts = ['tsa' => 0, 'admin' => 0];
$utCounts = [];
$sourceLabel = 'KY RoG CSNA - Copy.xlsx (second tranche)';

DB::beginTransaction();
try {
    foreach ($tsaRows as $row) {
        $key = identityKey($row);
        $payload = [
            'sanction_number' => $row['sanction_number'],
            'date' => $row['date'],
            'budget_head' => $row['budget_head'],
            'purpose_of_grant' => $row['purpose_of_grant'],
            'program_division_id' => $row['program_division_id'],
            'amount' => $row['amount'],
            'expenditure' => $row['expenditure'],
            'central_implementing_agency' => $row['central_implementing_agency'],
            'remark' => $row['remark'],
            'is_ner' => $row['is_ner'],
            'status' => 1,
        ];
        if (isset($existingTsa[$key])) {
            $record = AgencyReleaseTSA::find($existingTsa[$key]);
            if ($record && valuesChanged($record, $payload)) {
                $record->update($payload);
                $record->refresh();
                saveHistory('tsa', $record, 'Updated from ' . $sourceLabel . ' (white row ' . $row['excel_row'] . ')', 'UPDATE');
                $updated['tsa']++;
            } else {
                $unchanged['tsa']++;
            }
        } else {
            $record = AgencyReleaseTSA::create($payload);
            saveHistory('tsa', $record, 'Imported from ' . $sourceLabel . ' (white row ' . $row['excel_row'] . ')', 'CREATE');
            $existingTsa[$key] = (int) $record->id;
            $inserted['tsa']++;
        }
        if ((int) $row['is_ner'] === 1) {
            $nerCounts['tsa']++;
        }
    }

    foreach ($loaRows as $row) {
        $key = identityKey($row);
        $payload = [
            'sanction_number' => $row['sanction_number'],
            'date' => $row['date'],
            'budget_head' => $row['budget_head'],
            'purpose_of_grant' => $row['purpose_of_grant'],
            'program_division_id' => $row['program_division_id'],
            'amount' => $row['amount'],
            'ut' => $row['ut'],
            'remark' => $row['remark'],
            'status' => 1,
        ];
        $utLabel = $row['ut'] . ' (id ' . ($row['ut_id'] ?? 'n/a') . ')';
        if (isset($existingLoa[$key])) {
            $record = AgencyReleaseLOA::find($existingLoa[$key]);
            if ($record && valuesChanged($record, $payload)) {
                $record->update($payload);
                $record->refresh();
                saveHistory('loa', $record, 'Updated from ' . $sourceLabel . ' (red row ' . $row['excel_row'] . ', ' . $utLabel . ')', 'UPDATE');
                $updated['loa']++;
            } else {
                $unchanged['loa']++;
            }
        } else {
            $record = AgencyReleaseLOA::create($payload);
            saveHistory('loa', $record, 'Imported from ' . $sourceLabel . ' (red row ' . $row['excel_row'] . ', ' . $utLabel . ')', 'CREATE');
            $existingLoa[$key] = (int) $record->id;
            $inserted['loa']++;
        }
        $utCounts[$utLabel] = ($utCounts[$utLabel] ?? 0) + 1;
    }

    foreach ($adminRows as $row) {
        $key = identityKey($row);
        $payload = [
            'sanction_number' => $row['sanction_number'],
            'date' => $row['date'],
            'budget_head' => $row['budget_head'],
            'purpose_of_grant' => $row['purpose_of_grant'],
            'program_division_id' => $row['program_division_id'],
            'amount' => $row['amount'],
            'agency_vendor' => $row['agency_vendor'],
            'is_ner' => $row['is_ner'],
            'remark' => $row['remark'],
            'status' => 1,
        ];
        if (isset($existingAdmin[$key])) {
            $record = AgencyReleaseAdministrativeExpenditure::find($existingAdmin[$key]);
            if ($record && valuesChanged($record, $payload)) {
                $record->update($payload);
                $record->refresh();
                saveHistory('administrative-expenditure', $record, 'Updated from ' . $sourceLabel . ' (blue row ' . $row['excel_row'] . ')', 'UPDATE');
                $updated['admin']++;
            } else {
                $unchanged['admin']++;
            }
        } else {
            $record = AgencyReleaseAdministrativeExpenditure::create($payload);
            saveHistory('administrative-expenditure', $record, 'Imported from ' . $sourceLabel . ' (blue row ' . $row['excel_row'] . ')', 'CREATE');
            $existingAdmin[$key] = (int) $record->id;
            $inserted['admin']++;
        }
        if ((int) $row['is_ner'] === 1) {
            $nerCounts['admin']++;
        }
    }

    DB::commit();
} catch (Throwable $e) {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    fwrite(STDERR, 'Import failed: ' . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
    exit(1);
}

echo "\nTSA: inserted {$inserted['tsa']}, updated {$updated['tsa']}, unchanged {$unchanged['tsa']} (is_ner=1 in file: {$nerCounts['tsa']})\n";
echo "LOA: inserted {$inserted['loa']}, updated {$updated['loa']}, unchanged {$unchanged['loa']}\n";
if ($utCounts) {
    echo "LOA UTs in file: " . json_encode($utCounts) . "\n";
}
echo "Admin Exp: inserted {$inserted['admin']}, updated {$updated['admin']}, unchanged {$unchanged['admin']} (is_ner=1 in file: {$nerCounts['admin']})\n";

if ($skipped) {
    echo "\nSkipped rows (" . count($skipped) . "):\n";
    foreach ($skipped as $line) {
        echo "  {$line}\n";
    }
}

$totals = DB::select("
    SELECT 'tsa' AS t, COUNT(*) AS cnt, ROUND(SUM(amount), 5) AS amt FROM agency_release_tsa WHERE deleted_at IS NULL
    UNION ALL
    SELECT 'loa', COUNT(*), ROUND(SUM(amount), 5) FROM agency_release_loa WHERE deleted_at IS NULL
    UNION ALL
    SELECT 'admin', COUNT(*), ROUND(SUM(amount), 5) FROM agency_release_administrative_expenditure WHERE deleted_at IS NULL
");
echo "\nDB totals:\n";
foreach ($totals as $row) {
    echo "  {$row->t}: {$row->cnt} rows, amount={$row->amt} lakh\n";
}
echo "Done.\n";
