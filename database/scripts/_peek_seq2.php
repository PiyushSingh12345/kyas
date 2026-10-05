<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

echo "=== existing seq>=2 ===\n";
$rows = DB::table('mother_sanction as m')
    ->join('states as s', 's.id', '=', 'm.state_id')
    ->where('m.ms_sequence_no', '!=', '1')
    ->orderBy('m.state_id')
    ->orderBy('m.sls_name')
    ->orderBy('m.ms_sequence_no')
    ->get(['m.id', 's.name as state', 'm.ms_sequence_no', 'm.ifd_no', 'm.ky_ms_no', 'm.sanction_date', 'm.sls_name', 'm.pd_component', 'm.budget_head', 'm.mother_sanction_amount', 'm.total_mother_sanction_amount', 'm.remark', 'm.action_type', 'm.status', 'm.last_id']);
foreach ($rows as $r) {
    echo "{$r->id}\t{$r->state}\tseq={$r->ms_sequence_no}\t{$r->sanction_date}\tifd={$r->ifd_no}\tsls={$r->sls_name}\tpd={$r->pd_component}\tbh={$r->budget_head}\tms={$r->mother_sanction_amount}\ttotal={$r->total_mother_sanction_amount}\tremark={$r->remark}\taction={$r->action_type}\tlast={$r->last_id}\n";
}

$path = 'C:/Users/piyush_singh/Documents/AGRICULTURE/KYAS-documents/kyas_upload/2026-2027/secondTrunch/2nd tranche data of Mother Sanctions - Copy.xlsx';
$ss = IOFactory::load($path);

function cellStr($sheet, int $col, int $row): string
{
    $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row);
    $v = $cell->getCalculatedValue();
    if ($v === null) {
        $v = $cell->getValue();
    }
    return trim(str_replace(["\xc2\xa0", "\n", "\r"], [' ', ' ', ''], (string) $v));
}

echo "\n=== excel non-zero summary ===\n";
foreach ($ss->getAllSheets() as $sheet) {
    $title = $sheet->getTitle();
    $highestRow = (int) $sheet->getHighestDataRow();
    $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
    echo "\n--- {$title} rows={$highestRow} cols={$highestCol} ---\n";

    // print any row that looks like a header (contains S.No or State or Mother)
    $headerHits = 0;
    for ($r = 1; $r <= min($highestRow, 30); $r++) {
        $line = [];
        for ($c = 1; $c <= $highestCol; $c++) {
            $v = cellStr($sheet, $c, $r);
            if ($v !== '') {
                $line[] = Coordinate::stringFromColumnIndex($c) . ':' . mb_substr($v, 0, 80);
            }
        }
        if ($line) {
            echo "R{$r}: " . implode(' || ', $line) . "\n";
        }
    }

    $states = [];
    $nonzero = 0;
    $zeroOrBlank = 0;
    $state = '';
    for ($r = 4; $r <= $highestRow; $r++) {
        $st = cellStr($sheet, 2, $r);
        if ($st !== '' && !preg_match('/^total/i', $st) && !preg_match('/^s\.?no/i', $st)) {
            $state = $st;
        }
        $sls = cellStr($sheet, 4, $r);
        if ($sls === '' || preg_match('/^total/i', $sls) || preg_match('/sls/i', $sls)) {
            continue;
        }
        $sum = 0.0;
        $any = false;
        for ($c = 7; $c <= min($highestCol, 13); $c++) {
            $raw = cellStr($sheet, $c, $r);
            $num = preg_replace('/[^0-9.\-]/', '', $raw);
            if ($num !== '' && $num !== '-' && $num !== '.') {
                $any = true;
                $sum += (float) $num;
            }
        }
        $totalCol = cellStr($sheet, 6, $r);
        if ($sum > 0.00001) {
            $nonzero++;
            $states[$state] = ($states[$state] ?? 0) + 1;
        } else {
            $zeroOrBlank++;
        }
    }
    echo "nonzero SLS rows: {$nonzero}, skipped/zero: {$zeroOrBlank}\n";
    foreach ($states as $name => $cnt) {
        echo "  {$name}: {$cnt}\n";
    }
}
