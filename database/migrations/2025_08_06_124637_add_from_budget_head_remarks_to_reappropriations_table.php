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
        Schema::table('reappropriations', function (Blueprint $table) {
            $table->text('from_budget_head_remarks')->nullable()->after('from_budget_head_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reappropriations', function (Blueprint $table) {
            $table->dropColumn('from_budget_head_remarks');
        });
    }
};
