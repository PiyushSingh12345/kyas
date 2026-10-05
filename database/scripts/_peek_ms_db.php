<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

echo "=== mother_sanction summary ===\n";
echo 'total rows: ' . DB::table('mother_sanction')->count() . "\n";
$seqs = DB::table('mother_sanction')
    ->select('financial_year', 'ms_sequence_no', DB::raw('count(*) as c'), DB::raw('count(distinct state_id) as states'))
    ->groupBy('financial_year', 'ms_sequence_no')
    ->get();
foreach ($seqs as $s) {
    echo "FY={$s->financial_year} seq={$s->ms_sequence_no} rows={$s->c} states={$s->states}\n";
}

echo "\n=== sample first tranche ===\n";
$rows = DB::table('mother_sanction')
    ->where('financial_year', '2026-2027')
    ->orderBy('id')
    ->limit(8)
    ->get();
foreach ($rows as $r) {
    echo json_encode([
        'id' => $r->id,
        'state_id' => $r->state_id,
        'seq' => $r->ms_sequence_no,
        'ifd' => $r->ifd_no,
        'date' => $r->sanction_date,
        'ky' => $r->ky_ms_no,
        'sls' => $r->sls_name,
        'pd' => $r->pd_component,
        'total' => $r->total_mother_sanction_amount,
        'bh' => $r->budget_head,
        'cat' => $r->category,
        'avail' => $r->available_fund,
        'ms' => $r->mother_sanction_amount,
        'cf' => $r->carry_forward_amount,
        'status' => $r->status,
        'action' => $r->action_type,
        'last_id' => $r->last_id,
        'remark' => $r->remark,
    ], JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== distinct budget heads in table ===\n";
$bhs = DB::table('mother_sanction')->select('budget_head', 'category', DB::raw('count(*) c'))->groupBy('budget_head', 'category')->orderBy('budget_head')->get();
foreach ($bhs as $b) {
    echo "{$b->budget_head} | {$b->category} | {$b->c}\n";
}

echo "\n=== states ===\n";
$states = DB::table('states')->select('id', 'name')->orderBy('name')->get();
foreach ($states as $st) {
    echo "{$st->id}\t{$st->name}\n";
}

echo "\n=== budget_head master ===\n";
$master = DB::table('budget_heads')->select('id', 'budget', 'category', 'status')->orderBy('budget')->get();
foreach ($master as $b) {
    echo "{$b->id}\t{$b->budget}\t{$b->category}\tstatus={$b->status}\n";
}
