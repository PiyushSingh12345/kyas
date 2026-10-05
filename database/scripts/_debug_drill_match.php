<?php

require __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = 'C:/Users/piyush_singh/Documents/AGRICULTURE/KYAS-documents/kyas_upload/2026-2027/secondTrunch/RptCSSTSA03_MotherSanctionVsCentralRelease_Drill1_Page (13).xlsx';
$ss = IOFactory::load($path);
$sheet = $ss->getActiveSheet();
$last = (int) $sheet->getHighestDataRow();

$needles = ['AP 485', 'AP485', 'BR 18', 'BR18', 'CT 39', 'WB 11', 'UP 7', 'JK 107', 'MN 372', 'GJ 205'];
$current = '';
for ($r = 12; $r <= $last; $r++) {
    $ms = trim((string) $sheet->getCell('C' . $r)->getFormattedValue());
    $state = trim((string) $sheet->getCell('D' . $r)->getFormattedValue());
    $status = trim((string) $sheet->getCell('F' . $r)->getFormattedValue());
    $date = trim((string) $sheet->getCell('G' . $r)->getFormattedValue());
    $amt = trim((string) $sheet->getCell('H' . $r)->getFormattedValue());
    $sls = trim(str_replace(["\n", "\r"], ' ', (string) $sheet->getCell('S' . $r)->getFormattedValue()));
    if ($ms !== '') {
        $current = $ms;
    }
    $blob = $current . ' ' . $state . ' ' . $sls;
    foreach ($needles as $n) {
        if (stripos(str_replace(' ', '', $blob), str_replace(' ', '', $n)) !== false) {
            echo "R{$r} ms={$current} state={$state} status={$status} date={$date} amt={$amt} sls={$sls}\n";
            break;
        }
    }
}

echo "\n=== distinct states in Drill1 col D ===\n";
$states = [];
for ($r = 12; $r <= $last; $r++) {
    $state = trim((string) $sheet->getCell('D' . $r)->getFormattedValue());
    if ($state !== '') {
        $states[$state] = ($states[$state] ?? 0) + 1;
    }
}
foreach ($states as $name => $c) {
    echo "{$c}\t{$name}\n";
}
