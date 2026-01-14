<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update all NULL values to 0
        DB::table('statewise_aap_allocation')
            ->whereNull('tentative_amount')
            ->update(['tentative_amount' => 0]);

        // Then modify the column to match the amount column constraints (NOT NULL, default 0)
        Schema::table('statewise_aap_allocation', function (Blueprint $table) {
            $table->decimal('tentative_amount', 15, 5)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statewise_aap_allocation', function (Blueprint $table) {
            $table->decimal('tentative_amount', 15, 5)->nullable()->change();
        });
    }
};
