<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('mother_sanction') || Schema::hasColumn('mother_sanction', 'action_type')) {
            return;
        }

        Schema::table('mother_sanction', function (Blueprint $table) {
            $table->enum('action_type', ['FRESH_CREATE', 'UPDATED', 'CLOSED', 'REVISED', 'DEACTIVATED', 'ACTIVATED'])
                ->default('FRESH_CREATE')
                ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('mother_sanction') || ! Schema::hasColumn('mother_sanction', 'action_type')) {
            return;
        }

        Schema::table('mother_sanction', function (Blueprint $table) {
            $table->dropColumn('action_type');
        });
    }
};
