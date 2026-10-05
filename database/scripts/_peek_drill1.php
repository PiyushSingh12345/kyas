<?php

require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

$files = [
    'C:/Users/piyush_singh/Documents/AGRICULTURE/KYAS-documents/kyas_upload/2026-2027/secondTrunch/RptCSSTSA03_MotherSanctionVsCentralRelease_Drill1_Page (13).xlsx',
];

foreach ($files as $path) {
    echo "FILE {$path}\n";
    $ss = IOFactory::load($path);
    foreach ($ss->getAllSheets() as $i => $sheet) {
        echo "=== SHEET {$i}: " . $sheet->getTitle()
            . ' rows=' . $sheet->getHighestDataRow()
            . ' cols=' . $sheet->getHighestDataColumn() . " ===\n";
        $maxR = min(15, (int) $sheet->getHighestDataRow());
        $maxC = min(18, Coordinate::columnIndexFromString($sheet->getHighestDataColumn()));
        for ($r = 1; $r <= $maxR; $r++) {
            $cells = [];
            for ($c = 1; $c <= $maxC; $c++) {
                $v = $sheet->getCell(Coordinate::stringFromColumnIndex($c) . $r)->getFormattedValue();
                $v = str_replace(["\n", "\r"], ' ', trim((string) $v));
                if ($v !== '') {
                    $cells[] = Coordinate::stringFromColumnIndex($c) . ':' . mb_substr($v, 0, 60);
                }
            }
            if ($cells) {
                echo "R{$r}: " . implode(' | ', $cells) . "\n";
            }
        }
        echo "\n";
    }
}
