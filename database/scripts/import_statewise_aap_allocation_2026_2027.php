<?php

/**
 * Import Final AAP allocation FY 2026-27.xlsx into statewise_aap_allocation.
 *
 * Usage: php database/scripts/import_statewise_aap_allocation_2026_2027.php
 */

ini_set('memory_limit', '512M');
set_time_limit(0);

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

const FINANCIAL_YEAR = '2026-2027';
const EXCEL_PATH = 'C:/Users/piyush_singh/Documents/AGRICULTURE/KYAS-documents/kyas_upload/2026-2027/Final AAP allocation FY 2026-27.xlsx';

/**
 * Excel columns → pd_id + p_sub_id
 * p_sub_id 0 = unsplit PD; 1 = SLS-1; 2 = SLS-2 (matches vw_statewise_aap_allocation).
 */
const COLUMN_MAP = [
    'C' => ['pd_id' => 2,  'p_sub_id' => 0], // Agriculture Extension
    'D' => ['pd_id' => 6,  'p_sub_id' => 0], // NFSNM
    'E' => ['pd_id' => 10, 'p_sub_id' => 1], // Seed SLS-1
    'F' => ['pd_id' => 10, 'p_sub_id' => 2], // Seed SLS-2
    'G' => ['pd_id' => 3,  'p_sub_id' => 0], // MIDH
    'H' => ['pd_id' => 5,  'p_sub_id' => 0], // Bamboo
    'I' => ['pd_id' => 4,  'p_sub_id' => 0], // MOVCDNER
    'J' => ['pd_id' => 9,  'p_sub_id' => 0], // Digital Agriculture
    'K' => ['pd_id' => 7,  'p_sub_id' => 1], // Oil Palm SLS-1
    'L' => ['pd_id' => 7,  'p_sub_id' => 2], // Oil Palm SLS-2
    'M' => ['pd_id' => 8,  'p_sub_id' => 1], // Oil Seeds SLS-1
    'N' => ['pd_id' => 8,  'p_sub_id' => 2], // Oil Seeds SLS-2
    'O' => ['pd_id' => 12, 'p_sub_id' => 1], // Pulses SLS-1
    'P' => ['pd_id' => 12, 'p_sub_id' => 2], // Pulses SLS-2
    'Q' => ['pd_id' => 13, 'p_sub_id' => 0], // Mission Cotton
];

function normalizeStateKey(string $name): string
{
    $name = strtolower(trim($name));
    $name = str_replace(['&', '.', ',', '(', ')', '[', ']', '-'], ' ', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    $name = trim($name);
    $name = preg_replace('/\band\b/', ' ', $name);
    $name = preg_replace('/\b(ut|nct|national capital territory)\b/', ' ', $name);
    $name = preg_replace('/\s+/', '', $name);

    return $name;
}

function parseLakhs($value): string
{
    if ($value === null || $value === '') {
        return '0.00000';
    }
    $str = preg_replace('/[^0-9.\-]/', '', (string) $value);
    if ($str === '' || $str === '-' || $str === '.') {
        return '0.00000';
    }
    $parts = explode('.', $str, 2);
    $int = $parts[0] === '' || $parts[0] === '-' ? '0' : $parts[0];
    $dec = isset($parts[1]) ? substr($parts[1], 0, 5) : '';
    $dec = str_pad($dec, 5, '0', STR_PAD_RIGHT);

    return $int . '.' . $dec;
}

if (!is_file(EXCEL_PATH)) {
    fwrite(STDERR, "Excel file not found: " . EXCEL_PATH . PHP_EOL);
    exit(1);
}

$states = DB::table('states')->select('id', 'name')->get();
$stateMap = [];
foreach ($states as $state) {
    $stateMap[normalizeStateKey($state->name)] = (int) $state->id;
}

$aliases = [
    'chhattishgarh' => 'chhattisgarh',
    'uttrakhand' => 'uttarakhand',
    'jammu&kashmir' => 'jammuandkashmir',
    'jammuandkashmir' => 'jammuandkashmir',
    'nctofdelhi' => 'delhi',
    'ofdelhi' => 'delhi',
    'andaman&nicobar' => 'andamannicobar',
    'andamannicobar' => 'andamannicobar',
    'dadra&nagarhavelianddaman&diu' => 'dadranagarhavelianddamandiu',
    'puducherry' => 'puducherry',
    'chandigarh' => 'chandigarh',
    'ladakh' => 'ladakh',
];

$resolveStateId = function (string $excelName) use ($stateMap, $aliases): ?int {
    $key = normalizeStateKey($excelName);
    if (isset($stateMap[$key])) {
        return $stateMap[$key];
    }
    if (isset($aliases[$key]) && isset($stateMap[$aliases[$key]])) {
        return $stateMap[$aliases[$key]];
    }
    foreach ($stateMap as $dbKey => $id) {
        if ($key !== '' && (str_contains($dbKey, $key) || str_contains($key, $dbKey))) {
            return $id;
        }
    }
    return null;
};

echo "Loading Excel...\n";
$spreadsheet = IOFactory::load(EXCEL_PATH);
$sheet = $spreadsheet->getSheetByName('Sheet1') ?: $spreadsheet->getSheet(0);
$highestRow = (int) $sheet->getHighestRow();

$now = date('Y-m-d H:i:s');
$inserts = [];
$unmatched = [];
$skipped = [];
$matchedStates = 0;

for ($row = 5; $row <= $highestRow; $row++) {
    $serial = trim((string) $sheet->getCell('A' . $row)->getFormattedValue());
    $stateName = trim((string) $sheet->getCell('B' . $row)->getFormattedValue());
    $stateName = str_replace(["\xc2\xa0", "\n", "\r"], [' ', ' ', ''], $stateName);

    if ($stateName === '' || strcasecmp($stateName, 'Total') === 0) {
        continue;
    }

    $stateId = $resolveStateId($stateName);
    if (!$stateId) {
        $unmatched[] = $stateName;
        $skipped[] = $stateName;
        continue;
    }

    $matchedStates++;
    $orderId = is_numeric($serial) ? (int) $serial : $matchedStates;

    foreach (COLUMN_MAP as $col => $meta) {
        $raw = $sheet->getCell($col . $row)->getCalculatedValue();
        if ($raw === null || $raw === '') {
            $raw = $sheet->getCell($col . $row)->getFormattedValue();
        }
        $amount = parseLakhs($raw);

        $inserts[] = [
            'financial_year' => FINANCIAL_YEAR,
            'state_id' => $stateId,
            'pd_id' => $meta['pd_id'],
            'amount' => $amount,
            'tentative_amount' => '0.00000',
            'status' => 1,
            'remark' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'p_sub_id' => $meta['p_sub_id'],
            'order_id' => $orderId,
        ];
    }
}

echo "Matched states: {$matchedStates}\n";
if ($unmatched) {
    echo "Unmatched / skipped: " . implode(', ', array_unique($unmatched)) . "\n";
}
echo "Rows to insert: " . count($inserts) . "\n";

DB::beginTransaction();
try {
    $deleted = DB::table('statewise_aap_allocation')
        ->whereIn('financial_year', ['2026-2027', '2026-27'])
        ->delete();
    echo "Deleted existing FY 2026-27 rows: {$deleted}\n";

    foreach (array_chunk($inserts, 200) as $chunk) {
        DB::table('statewise_aap_allocation')->insert($chunk);
    }

    DB::commit();
} catch (Throwable $e) {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    fwrite(STDERR, 'Import failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

try {
    DB::statement("
        CREATE OR REPLACE VIEW vw_statewise_aap_allocation AS
        SELECT
            a.financial_year AS financial_year,
            s.name AS Statename,
            SUM(CASE WHEN a.pd_id = 2 AND a.p_sub_id = 0 THEN a.amount END) AS `Agricuture_Extension`,
            SUM(CASE WHEN a.pd_id = 6 AND a.p_sub_id = 0 THEN a.amount END) AS `National_Food_Security_and_Nutrition_Mission`,
            SUM(CASE WHEN a.pd_id = 10 AND a.p_sub_id = 1 THEN a.amount END) AS `Sub Mission on Seed and Planting_1`,
            SUM(CASE WHEN a.pd_id = 10 AND a.p_sub_id = 2 THEN a.amount END) AS `Sub Mission on Seed and Planting_2`,
            SUM(CASE WHEN a.pd_id = 3 AND a.p_sub_id = 0 THEN a.amount END) AS `Mission for Integrated Development of Horticulture`,
            SUM(CASE WHEN a.pd_id = 5 AND a.p_sub_id = 0 THEN a.amount END) AS `National Bamboo Mission`,
            SUM(CASE WHEN a.pd_id = 4 AND a.p_sub_id = 0 THEN a.amount END) AS `MOVCDNER`,
            SUM(CASE WHEN a.pd_id = 9 AND a.p_sub_id = 0 THEN a.amount END) AS `Digital Agriculture Mission`,
            SUM(CASE WHEN a.pd_id = 7 AND a.p_sub_id = 1 THEN a.amount END) AS `National Mission on Edible Oils- Oil Palm_1`,
            SUM(CASE WHEN a.pd_id = 7 AND a.p_sub_id = 2 THEN a.amount END) AS `National Mission on Edible Oils- Oil Palm_2`,
            SUM(CASE WHEN a.pd_id = 8 AND a.p_sub_id = 1 THEN a.amount END) AS `National Mission on Edible Oils- Oil Seeds_1`,
            SUM(CASE WHEN a.pd_id = 8 AND a.p_sub_id = 2 THEN a.amount END) AS `National Mission on Edible Oils- Oil Seeds_2`,
            SUM(CASE WHEN a.pd_id = 12 AND a.p_sub_id = 1 THEN a.amount END) AS `Mission Pulses_1`,
            SUM(CASE WHEN a.pd_id = 12 AND a.p_sub_id = 2 THEN a.amount END) AS `Mission Pulses_2`,
            SUM(CASE WHEN a.pd_id = 13 AND a.p_sub_id = 0 THEN a.amount END) AS `Mission Cotton`,
            MAX(a.order_id) AS order_id
        FROM statewise_aap_allocation a
        LEFT JOIN states s ON s.id = a.state_id
        WHERE a.state_id <> 0
        GROUP BY a.financial_year, s.name
    ");
} catch (Throwable $e) {
    fwrite(STDERR, 'View refresh failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$counts = DB::select("
    SELECT COUNT(*) AS cnt,
           COUNT(DISTINCT state_id) AS states,
           ROUND(SUM(amount), 5) AS total_amount
    FROM statewise_aap_allocation
    WHERE financial_year IN ('2026-2027', '2026-27')
");
$viewSample = DB::select("SELECT Statename, `Digital Agriculture Mission` AS digital FROM vw_statewise_aap_allocation WHERE financial_year = '2026-2027' ORDER BY order_id LIMIT 3");

echo "Imported rows: {$counts[0]->cnt}\n";
echo "Distinct states: {$counts[0]->states}\n";
echo "Sum of final amounts (₹ lakh): {$counts[0]->total_amount}\n";
echo "View sample:\n";
foreach ($viewSample as $row) {
    echo "  {$row->Statename} digital={$row->digital}\n";
}
echo "Done.\n";
