<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Shared Total MS / Expenditure totals used by Mother Sanction List and Daily Sanction.
 *
 * Total MS (per BH + PD): sum of each create/revise tranche MS, excluding carry forward.
 * Expenditure (per BH + PD): sum of all daily sanction amounts across every tranche.
 */
class MotherSanctionTotalCalculator
{
    /**
     * Total MS / MS Amount for a Budget Head (+ PD / FY / state / SLS).
     */
    public function totalMs(
        string $budgetHead,
        ?string $pdComponent,
        ?string $financialYear = null,
        ?int $stateId = null,
        ?string $slsName = null
    ): float {
        if ($budgetHead === '') {
            return 0.0;
        }

        $query = DB::table('mother_sanction')
            ->whereRaw('TRIM(budget_head) = TRIM(?)', [$budgetHead])
            ->whereNotNull('mother_sanction_amount');

        $this->applyCommonMsFilters($query, $pdComponent, $financialYear, $stateId, $slsName);

        $records = $query->get();
        if ($records->isEmpty()) {
            return 0.0;
        }

        $creationNetById = $this->loadCreationNetAmountsByRecordId(
            $records->pluck('id')->unique()->filter()->values()->all()
        );

        // Each mother_sanction row is one tranche — do not unique by ky_ms_no.
        return floatval(
            $records
                ->unique('id')
                ->sum(fn ($record) => $this->netMotherSanctionAmountForTotal($record, $creationNetById))
        );
    }

    /**
     * Expenditure for a Budget Head (+ PD): sum of all DS across every MS tranche.
     */
    public function expenditure(
        string $budgetHead,
        ?string $pdComponent,
        ?string $financialYear = null,
        ?int $stateId = null,
        ?string $slsName = null
    ): float {
        if ($budgetHead === '') {
            return 0.0;
        }

        $msQuery = DB::table('mother_sanction')
            ->whereRaw('TRIM(budget_head) = TRIM(?)', [$budgetHead]);

        $this->applyCommonMsFilters($msQuery, $pdComponent, $financialYear, $stateId, $slsName);

        $kyMsNos = $msQuery->pluck('ky_ms_no')->unique()->filter()->values()->all();

        $dsQuery = DB::table('daily_sanction')
            ->whereRaw('TRIM(budget_head) = TRIM(?)', [$budgetHead]);

        if ($stateId) {
            $dsQuery->where('state_id', $stateId);
        }

        if ($financialYear) {
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);
            if (!empty($yearVariants)) {
                $dsQuery->whereIn('financial_year', $yearVariants);
            }
        }

        $dsQuery->where(function ($query) use ($kyMsNos, $slsName) {
            if (!empty($kyMsNos)) {
                $query->whereIn('mother_sanction', $kyMsNos);
            }

            if ($slsName !== null && $slsName !== '') {
                if (!empty($kyMsNos)) {
                    $query->orWhereRaw('TRIM(sls_name) = TRIM(?)', [$slsName]);
                } else {
                    $query->whereRaw('TRIM(sls_name) = TRIM(?)', [$slsName]);
                }
            }
        });

        return floatval($dsQuery->sum('center_share_amount') ?? 0);
    }

    /**
     * @param  array<int, string>  $budgetHeads
     * @return array<string, array{total_ms_amount: float, total_daily_sanctioned: float, available_fund: float}>
     */
    public function amountsByBudgetHeads(
        array $budgetHeads,
        ?string $pdComponent,
        ?string $financialYear = null,
        ?int $stateId = null,
        ?string $slsName = null
    ): array {
        $data = [];

        foreach ($budgetHeads as $budgetHead) {
            $bh = trim((string) $budgetHead);
            if ($bh === '') {
                continue;
            }

            $totalMs = $this->totalMs($bh, $pdComponent, $financialYear, $stateId, $slsName);
            $expenditure = $this->expenditure($bh, $pdComponent, $financialYear, $stateId, $slsName);

            $data[$bh] = [
                'total_ms_amount' => $totalMs,
                'total_daily_sanctioned' => $expenditure,
                'available_fund' => max(0.0, $totalMs - $expenditure),
            ];
        }

        return $data;
    }

    private function applyCommonMsFilters(
        $query,
        ?string $pdComponent,
        ?string $financialYear,
        ?int $stateId,
        ?string $slsName
    ): void {
        if ($stateId) {
            $query->where('state_id', $stateId);
        }

        if ($slsName !== null && $slsName !== '') {
            $query->where('sls_name', $slsName);
        }

        if ($financialYear) {
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);
            if (!empty($yearVariants)) {
                $query->whereIn('financial_year', $yearVariants);
            }
        }

        if (!$pdComponent) {
            return;
        }

        $pdMatchValues = $this->getPdComponentMatchValues($pdComponent);
        if (!empty($pdMatchValues)) {
            $query->where(function ($pdQuery) use ($pdMatchValues) {
                foreach ($pdMatchValues as $pdValue) {
                    $pdQuery->orWhereRaw(
                        'TRIM(pd_component) COLLATE utf8mb4_unicode_ci = ?',
                        [trim($pdValue)]
                    );
                }
            });
        } else {
            $query->whereRaw(
                'TRIM(pd_component) COLLATE utf8mb4_unicode_ci = TRIM(?)',
                [trim($pdComponent)]
            );
        }
    }

    private function netMotherSanctionAmountForTotal(object $record, array $creationNetById = []): float
    {
        $recordId = (int) ($record->id ?? 0);

        if ($recordId && array_key_exists($recordId, $creationNetById)) {
            return $creationNetById[$recordId];
        }

        $snapshot = $this->getCreationNetAmountForRecord($recordId);
        if ($snapshot !== null) {
            return $snapshot;
        }

        return $this->netAmountExcludingCarryForward($record);
    }

    /**
     * @return array<int, float>
     */
    private function loadCreationNetAmountsByRecordId(array $recordIds): array
    {
        if (empty($recordIds)) {
            return [];
        }

        $histories = DB::table('mother_sanction_history')
            ->whereIn('mother_sanction_id', $recordIds)
            ->whereIn('action_type', ['FRESH_CREATE', 'REVISED'])
            ->orderBy('history_timestamp')
            ->orderBy('history_id')
            ->get()
            ->groupBy('mother_sanction_id');

        $result = [];
        foreach ($histories as $recordId => $entries) {
            $created = $entries->first(
                fn ($entry) => str_contains(strtolower((string) ($entry->change_description ?? '')), 'record created')
            );
            $result[(int) $recordId] = $this->netAmountExcludingCarryForward($created ?? $entries->first());
        }

        return $result;
    }

    private function getCreationNetAmountForRecord(int $recordId): ?float
    {
        if (!$recordId) {
            return null;
        }

        $created = DB::table('mother_sanction_history')
            ->where('mother_sanction_id', $recordId)
            ->whereIn('action_type', ['FRESH_CREATE', 'REVISED'])
            ->where('change_description', 'like', '%record created%')
            ->orderBy('history_timestamp')
            ->orderBy('history_id')
            ->first();

        if ($created) {
            return $this->netAmountExcludingCarryForward($created);
        }

        $earliest = DB::table('mother_sanction_history')
            ->where('mother_sanction_id', $recordId)
            ->whereIn('action_type', ['FRESH_CREATE', 'REVISED'])
            ->orderBy('history_timestamp')
            ->orderBy('history_id')
            ->first();

        return $earliest ? $this->netAmountExcludingCarryForward($earliest) : null;
    }

    private function netAmountExcludingCarryForward(object $record): float
    {
        $ms = floatval($record->mother_sanction_amount ?? 0);
        $carryForward = floatval($record->carry_forward_amount ?? 0);
        $actionType = strtoupper((string) ($record->action_type ?? ''));

        if (in_array($actionType, ['REVISED', 'REVISE'], true)) {
            return max(0.0, $ms - $carryForward);
        }

        return $ms;
    }

    /**
     * @return array<int, string>
     */
    public function getPdComponentMatchValues(?string $pdComponent): array
    {
        if (!$pdComponent) {
            return [];
        }

        $trimmed = trim($pdComponent);
        $values = [$trimmed];

        $divisionId = DB::table('md_program_divisions')
            ->whereRaw('division_name COLLATE utf8mb4_unicode_ci = ?', [$trimmed])
            ->value('division_id');

        if (!$divisionId) {
            $divisionId = DB::table('pd_and_sls_comp as psc')
                ->join('md_program_divisions as md', function ($join) {
                    $join->on(
                        DB::raw('psc.slsPD COLLATE utf8mb4_unicode_ci'),
                        '=',
                        DB::raw('md.division_name COLLATE utf8mb4_unicode_ci')
                    );
                })
                ->whereRaw('TRIM(psc.slsPD) COLLATE utf8mb4_unicode_ci = ?', [$trimmed])
                ->value('md.division_id');
        }

        if ($divisionId) {
            $divisionName = DB::table('md_program_divisions')
                ->where('division_id', $divisionId)
                ->value('division_name');

            if ($divisionName) {
                $values[] = trim($divisionName);
            }

            $slsPdAliases = DB::table('pd_and_sls_comp as psc')
                ->join('md_program_divisions as md', function ($join) {
                    $join->on(
                        DB::raw('psc.slsPD COLLATE utf8mb4_unicode_ci'),
                        '=',
                        DB::raw('md.division_name COLLATE utf8mb4_unicode_ci')
                    );
                })
                ->where('md.division_id', $divisionId)
                ->pluck('psc.slsPD')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->all();

            $values = array_merge($values, $slsPdAliases);
        }

        return array_values(array_unique(array_filter($values)));
    }

    /**
     * @return array<int, string>
     */
    public function normalizeFinancialYearVariants(?string $year): array
    {
        if (empty($year)) {
            return [];
        }

        $year = trim($year);
        $variants = [$year];

        if (preg_match('/^\d{4}-\d{4}$/', $year)) {
            [$start, $end] = explode('-', $year);
            $variants[] = $start . '-' . substr($end, -2);
        } elseif (preg_match('/^\d{4}-\d{2}$/', $year)) {
            [$start, $end] = explode('-', $year);
            $variants[] = $start . '-20' . $end;
        }

        return array_values(array_unique($variants));
    }
}
