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
     *
     * Default (list / Daily Sanction / close): open-chain create/revise nets only (CF excluded).
     * Fully closed scopes contribute the closed MS Amount (= Expenditure for that generation).
     *
     * When $includeClosedGenerations is true (Current Available Fund on create):
     * also subtract closed-generation MS Amount from Annual Allocation even if a new open
     * MS exists for the same BH + PD after a recent close.
     */
    public function totalMs(
        string $budgetHead,
        ?string $pdComponent,
        ?string $financialYear = null,
        ?int $stateId = null,
        ?string $slsName = null,
        bool $includeClosedGenerations = false
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

        $byScope = $records->groupBy(
            fn ($record) => ($record->state_id ?? '') . '|' . trim((string) ($record->sls_name ?? ''))
        );

        $total = 0.0;
        $creationNetById = $this->loadCreationNetAmountsByRecordId(
            $records->pluck('id')->unique()->filter()->values()->all()
        );

        foreach ($byScope as $scopeRecords) {
            $closedRecords = $scopeRecords->filter(
                fn ($r) => strtoupper((string) ($r->action_type ?? '')) === 'CLOSED'
            );
            $openRecords = $scopeRecords->filter(
                fn ($r) => strtoupper((string) ($r->action_type ?? '')) !== 'CLOSED'
            );

            $includeClosedForScope = $includeClosedGenerations
                || ($closedRecords->isNotEmpty() && $openRecords->isEmpty());

            if ($includeClosedForScope && $closedRecords->isNotEmpty()) {
                $sample = $closedRecords->first();
                $total += $this->closedGenerationMsAmount(
                    $budgetHead,
                    $financialYear ?: ($sample->financial_year ?? null),
                    $sample->state_id ? (int) $sample->state_id : $stateId,
                    $closedRecords
                );
            }

            if ($openRecords->isNotEmpty()) {
                $total += floatval(
                    $openRecords
                        ->unique('id')
                        ->sum(fn ($record) => $this->netMotherSanctionAmountForTotal($record, $creationNetById))
                );
            }
        }

        return floatval($total);
    }

    /**
     * MS Amount locked by a closed generation (= Expenditure after close; matches list closed row).
     *
     * @param  \Illuminate\Support\Collection<int, object>  $closedRecords
     */
    private function closedGenerationMsAmount(
        string $budgetHead,
        ?string $financialYear,
        ?int $stateId,
        $closedRecords
    ): float {
        $closedKyMsNos = $closedRecords
            ->pluck('ky_ms_no')
            ->map(fn ($no) => trim((string) $no))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($closedKyMsNos)) {
            return 0.0;
        }

        $dsQuery = DB::table('daily_sanction')
            ->whereRaw('TRIM(budget_head) = TRIM(?)', [$budgetHead])
            ->whereIn('mother_sanction', $closedKyMsNos);

        if ($stateId) {
            $dsQuery->where('state_id', $stateId);
        }

        if ($financialYear) {
            $yearVariants = $this->normalizeFinancialYearVariants($financialYear);
            if (!empty($yearVariants)) {
                $dsQuery->whereIn('financial_year', $yearVariants);
            }
        }

        return floatval($dsQuery->sum('center_share_amount') ?? 0);
    }

    /**
     * Expenditure for a Budget Head (+ PD): sum of DS for the open MS chain when one exists.
     * Closed-generation MS numbers are excluded so Balanced Fund matches Mother Sanction List
     * after close + new MS for the same SLS/PD.
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

        $msRecords = $msQuery->get([
            'id',
            'ky_ms_no',
            'status',
            'action_type',
            'state_id',
            'sls_name',
        ]);

        $kyMsNos = $this->resolveKyMsNosForExpenditure($msRecords);

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

            // Only fall back to SLS-wide match when there are no MS numbers at all
            if (empty($kyMsNos) && $slsName !== null && $slsName !== '') {
                $query->whereRaw('TRIM(sls_name) = TRIM(?)', [$slsName]);
            }
        });

        return floatval($dsQuery->sum('center_share_amount') ?? 0);
    }

    /**
     * Prefer open-chain (non-CLOSED) MS numbers when an open generation exists.
     * If the scope is fully closed, use closed MS numbers (Total MS = Expenditure path).
     *
     * @param  \Illuminate\Support\Collection<int, object>  $msRecords
     * @return array<int, string>
     */
    private function resolveKyMsNosForExpenditure($msRecords): array
    {
        if ($msRecords->isEmpty()) {
            return [];
        }

        $byScope = $msRecords->groupBy(
            fn ($record) => ($record->state_id ?? '') . '|' . trim((string) ($record->sls_name ?? ''))
        );

        $kyMsNos = [];
        foreach ($byScope as $scopeRecords) {
            $open = $scopeRecords->filter(
                fn ($r) => strtoupper((string) ($r->action_type ?? '')) !== 'CLOSED'
            );

            $source = $open->isNotEmpty() ? $open : $scopeRecords;
            foreach ($source->pluck('ky_ms_no')->unique()->filter() as $no) {
                $kyMsNos[] = (string) $no;
            }
        }

        return array_values(array_unique($kyMsNos));
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

            // Mother Sanctioned Amount = Total MS of open chain (CF excluded; CLOSED generations excluded)
            $totalMs = $this->totalMs($bh, $pdComponent, $financialYear, $stateId, $slsName);
            // Expenditure = DS linked to open-chain MS numbers only (not prior closed generation)
            $expenditure = $this->expenditure($bh, $pdComponent, $financialYear, $stateId, $slsName);

            $data[$bh] = [
                'total_ms_amount' => $totalMs,
                'total_daily_sanctioned' => $expenditure,
                // Balanced Fund Available = Total MS - Expenditure (same as MS List Available Fund)
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
     * Public wrappers used by MotherSanctionController list chains.
     */
    public function netAmountForRecord(object $record, array $creationNetById = []): float
    {
        return $this->netMotherSanctionAmountForTotal($record, $creationNetById);
    }

    /**
     * @return array<int, float>
     */
    public function loadCreationNetAmountsByRecordIdPublic(array $recordIds): array
    {
        return $this->loadCreationNetAmountsByRecordId($recordIds);
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
