<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$codes = ['BR353','BR316','JH341','JH365','KA411','MP526','OR312','UP360','UK2431','2425','2431'];
foreach ($codes as $code) {
    $rows = DB::table('mother_sanction')
        ->where('ky_ms_no', 'like', '%' . $code . '%')
        ->orWhere('ifd_no', 'like', '%' . $code . '%')
        ->limit(3)
        ->get(['state_id','ms_sequence_no','ky_ms_no','sls_name','pd_component']);
    echo "\n== {$code} ms rows " . $rows->count() . " ==\n";
    foreach ($rows as $r) {
        echo "{$r->state_id} seq={$r->ms_sequence_no} {$r->ky_ms_no} | {$r->pd_component} | {$r->sls_name}\n";
    }
}
