<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$codes = ['BR353','BR316','JH341','JH365','KA411','MP526','OR312','UK2431','UK2425','UP360','AP485','BR18'];
foreach ($codes as $code) {
    $digits = preg_replace('/\D/', '', $code);
    $letters = preg_replace('/\d/', '', $code);
    $rows = DB::table('pd_and_sls_comp')
        ->where('sls_code', 'like', '%' . $digits . '%')
        ->orWhere('sls_code', 'like', '%' . $code . '%')
        ->limit(8)
        ->get(['state_id', 'sls_code', 'name', 'slsPD']);
    echo "\n== {$code} ==\n";
    foreach ($rows as $r) {
        echo "{$r->state_id}\t{$r->sls_code}\t{$r->slsPD}\t{$r->name}\n";
    }
    if ($rows->isEmpty()) {
        echo "(none)\n";
    }
}
