<?php

require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = 'C:/Users/piyush_singh/Documents/AGRICULTURE/KYAS-documents/kyas_upload/2026-2027/secondTrunch/2nd tranche data of Mother Sanctions - Copy.xlsx';
$ss = IOFactory::load($path);

foreach ($ss->getAllSheets() as $i => $sheet) {
    echo "=== SHEET {$i}: " . $sheet->getTitle()
        . ' rows=' . $sheet->getHighestDataRow()
        . ' cols=' . $sheet->getHighestDataColumn() . " ===\n";
    $maxR = min(12, (int) $sheet->getHighestDataRow());
    $maxC = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
    for ($r = 1; $r <= $maxR; $r++) {
        $cells = [];
        for ($c = 1; $c <= $maxC; $c++) {
            $v = $sheet->getCell(Coordinate::stringFromColumnIndex($c) . $r)->getFormattedValue();
            $cells[] = str_replace(["\n", "\r"], ' ', (string) $v);
        }
        echo "R{$r}: " . implode(' | ', $cells) . "\n";
    }
    echo "\n";
}
