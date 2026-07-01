<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NEW_VALUES = [
        'FRESH_CREATE',
        'UPDATED',
        'CLOSED',
        'REVISED',
        'DEACTIVATED',
        'ACTIVATED',
    ];

    private const VALUE_MAP = [
        'CREATE' => 'FRESH_CREATE',
        'UPDATE' => 'UPDATED',
        'CLOSE' => 'CLOSED',
        'REVISE' => 'REVISED',
        'DEACTIVATE' => 'DEACTIVATED',
        'ACTIVATE' => 'ACTIVATED',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->migrateActionTypeColumn('mother_sanction');
        $this->migrateActionTypeColumn('mother_sanction_history');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->revertActionTypeColumn('mother_sanction');
        $this->revertActionTypeColumn('mother_sanction_history');
    }

    private function migrateActionTypeColumn(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'action_type')) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `action_type` VARCHAR(50) NOT NULL DEFAULT 'FRESH_CREATE'");

        foreach (self::VALUE_MAP as $oldValue => $newValue) {
            DB::table($table)->where('action_type', $oldValue)->update(['action_type' => $newValue]);
        }

        $enumList = "'" . implode("','", self::NEW_VALUES) . "'";
        DB::statement(
            "ALTER TABLE `{$table}` MODIFY `action_type` ENUM({$enumList}) NOT NULL DEFAULT 'FRESH_CREATE'"
        );
    }

    private function revertActionTypeColumn(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'action_type')) {
            return;
        }

        $oldValues = array_keys(self::VALUE_MAP);

        DB::statement("ALTER TABLE `{$table}` MODIFY `action_type` VARCHAR(50) NOT NULL DEFAULT 'CREATE'");

        foreach (self::VALUE_MAP as $oldValue => $newValue) {
            DB::table($table)->where('action_type', $newValue)->update(['action_type' => $oldValue]);
        }

        $enumList = "'" . implode("','", $oldValues) . "'";
        DB::statement(
            "ALTER TABLE `{$table}` MODIFY `action_type` ENUM({$enumList}) NOT NULL DEFAULT 'CREATE'"
        );
    }
};
