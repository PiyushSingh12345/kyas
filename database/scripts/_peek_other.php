<?php

require __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

function headers($path, $maxSheets = 2) {
    echo "\nFILE: {$path}\n";
    $reader = new Xlsx();
    $reader->setReadDataOnly(true);
    $book = $reader->load($path);
    $n = 0;
    foreach ($book->getAllSheets() as $sheet) {
        if ($n >= $maxSheets) break;
        $n++;
        echo "SHEET {$sheet->getTitle()} rows={$sheet->getHighestDataRow()} cols={$sheet->getHighestDataColumn()}\n";
        $maxC = min(12, Coordinate::columnIndexFromString($sheet->getHighestDataColumn()));
        $maxR = min(6, (int) $sheet->getHighestDataRow());
        for ($r = 1; $r <= $maxR; $r++) {
            $cells = [];
            for ($c = 1; $c <= $maxC; $c++) {
                $v = trim(str_replace(["\n","\r"], ' ', (string) $sheet->getCell(Coordinate::stringFromColumnIndex($c).$r)->getValue()));
                if ($v !== '') $cells[] = Coordinate::stringFromColumnIndex($c).':'.mb_substr($v, 0, 50);
            }
            if ($cells) echo "R{$r}: ".implode(' | ', $cells)."\n";
        }
    }
}

$base = 'C:/Users/piyush_singh/Documents/AGRICULTURE/KYAS-documents/kyas_upload/2026-2027/secondTrunch/';
headers($base . 'SPARSH 01.xlsx', 1);
headers($base . 'RptCSSTSA03_MotherSanctionVsCentralRelease_Drill3_Page (3).xlsx', 1);
