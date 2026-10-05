<?php

require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = 'C:/Users/piyush_singh/Documents/AGRICULTURE/KYAS-documents/kyas_upload/2026-2027/secondTrunch/RptCSSTSA03_MotherSanctionVsCentralRelease_Drill1_Page (13).xlsx';
$ss = IOFactory::load($path);
$sheet = $ss->getActiveSheet();
$maxC = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
echo "cols={$maxC} rows=" . $sheet->getHighestDataRow() . "\n";
for ($r = 11; $r <= 20; $r++) {
    echo "---- R{$r} ----\n";
    for ($c = 1; $c <= $maxC; $c++) {
        $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($c) . $r);
        $v = $cell->getFormattedValue();
        $v = trim(str_replace(["\n", "\r"], ' ', (string) $v));
        if ($v !== '') {
            echo Coordinate::stringFromColumnIndex($c) . "({$c}): " . mb_substr($v, 0, 120) . "\n";
        }
    }
}
