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
        Schema::table('pdwise_aap_allocation_history', function (Blueprint $table) {
            $table->string('budget_phase', 10)->nullable()->after('financial_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdwise_aap_allocation_history', function (Blueprint $table) {
            $table->dropColumn('budget_phase');
        });
    }
};
