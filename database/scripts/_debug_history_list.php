<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$start = microtime(true);

$latestIds = DB::table('daily_sanction_history')
    ->selectRaw('MAX(history_id) as history_id')
    ->groupBy('state_id', 'daily_sanction_no');

$headers = DB::table('daily_sanction_history as h')
    ->joinSub($latestIds, 'latest', 'h.history_id', '=', 'latest.history_id')
    ->leftJoin('states as s', 'h.state_id', '=', 's.id')
    ->orderByDesc('h.history_timestamp')
    ->orderByDesc('h.history_id')
    ->get([
        'h.history_id',
        'h.financial_year',
        'h.state_id',
        'h.daily_sanction_no',
        'h.ds_date',
        'h.mother_sanction',
        'h.sls_name',
        'h.ifd_no',
        'h.action_type',
        'h.changed_by',
        'h.history_timestamp',
        'h.change_description',
        's.name as state_name',
    ]);

$budgetRows = DB::table('daily_sanction_history')
    ->select([
        'state_id',
        'daily_sanction_no',
        'budget_head',
        DB::raw('SUM(COALESCE(center_share_amount, 0)) as center_share_amount'),
        DB::raw('SUM(COALESCE(old_center_share_amount, 0)) as old_center_share_amount'),
        DB::raw('SUM(COALESCE(new_center_share_amount, 0)) as new_center_share_amount'),
        DB::raw('MAX(history_id) as latest_history_id'),
    ])
    ->whereNotNull('budget_head')
    ->where('budget_head', '<>', '')
    ->groupBy('state_id', 'daily_sanction_no', 'budget_head')
    ->get();

$meta = DB::table('daily_sanction_history')
    ->whereIn('history_id', $budgetRows->pluck('latest_history_id')->unique()->values())
    ->get(['history_id', 'action_type', 'change_description', 'changed_by', 'history_timestamp'])
    ->keyBy('history_id');

$budgetsByKey = [];
foreach ($budgetRows as $row) {
    $key = ($row->state_id ?? '') . '|' . ($row->daily_sanction_no ?? '');
    $m = $meta[$row->latest_history_id] ?? null;
    $budgetsByKey[$key][] = [
        'budget_head' => $row->budget_head,
        'old' => (float) $row->old_center_share_amount,
        'new' => (float) $row->new_center_share_amount,
        'center' => (float) $row->center_share_amount,
        'action_type' => $m->action_type ?? null,
        'ts' => $m->history_timestamp ?? null,
    ];
}

foreach ($budgetsByKey as &$list) {
    usort($list, function ($a, $b) {
        return strcmp((string) $b['ts'], (string) $a['ts']);
    });
}
unset($list);

echo 'groups=' . $headers->count() . PHP_EOL;
echo 'budget_rows=' . $budgetRows->count() . PHP_EOL;
echo 'time=' . round(microtime(true) - $start, 2) . PHP_EOL;
echo 'mem_mb=' . round(memory_get_peak_usage(true) / 1048576, 1) . PHP_EOL;

$old = json_decode(app(App\Http\Controllers\DailySanctionController::class)->historyList()->getContent(), true);
echo 'old_groups=' . count($old) . PHP_EOL;

$mismatch = 0;
$checked = 0;
$oldByKey = [];
foreach ($old as $item) {
    $oldByKey[($item['state_id'] ?? '') . '|' . ($item['daily_sanction_no'] ?? '')] = $item;
}

foreach ($headers as $item) {
    $key = ($item->state_id ?? '') . '|' . ($item->daily_sanction_no ?? '');
    $prev = $oldByKey[$key] ?? null;
    if (!$prev) {
        $mismatch++;
        echo "missing old key $key\n";
        continue;
    }
    $checked++;
    if ((string) $prev['id'] !== (string) $item->history_id) {
        $mismatch++;
        if ($mismatch < 8) {
            echo "id mismatch $key old={$prev['id']} new={$item->history_id}\n";
        }
    }
    $newBudgets = $budgetsByKey[$key] ?? [];
    $oldBudgets = $prev['budget_heads'] ?? [];
    if (count($newBudgets) !== count($oldBudgets)) {
        $mismatch++;
        if ($mismatch < 8) {
            echo "bh count $key old=" . count($oldBudgets) . ' new=' . count($newBudgets) . "\n";
        }
        continue;
    }
    foreach ($oldBudgets as $i => $ob) {
        $nb = $newBudgets[$i];
        if ($ob['budget_head'] !== $nb['budget_head']) {
            $mismatch++;
            if ($mismatch < 8) {
                echo "bh order $key idx=$i old={$ob['budget_head']} new={$nb['budget_head']}\n";
            }
            break;
        }
        $od = abs((float) $ob['new_center_share_amount'] - $nb['new']);
        if ($od > 0.02) {
            $mismatch++;
            if ($mismatch < 8) {
                echo "amt $key {$ob['budget_head']} old={$ob['new_center_share_amount']} new={$nb['new']}\n";
            }
            break;
        }
    }
}

echo "checked=$checked mismatch=$mismatch\n";
echo 'mem_after_old_mb=' . round(memory_get_peak_usage(true) / 1048576, 1) . PHP_EOL;
