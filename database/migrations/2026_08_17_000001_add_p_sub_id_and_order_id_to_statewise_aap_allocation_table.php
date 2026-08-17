<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('statewise_aap_allocation')) {
            return;
        }

        if (!Schema::hasColumn('statewise_aap_allocation', 'p_sub_id')) {
            Schema::table('statewise_aap_allocation', function (Blueprint $table) {
                $table->integer('p_sub_id')->default(0);
            });
        }

        if (!Schema::hasColumn('statewise_aap_allocation', 'order_id')) {
            Schema::table('statewise_aap_allocation', function (Blueprint $table) {
                $table->integer('order_id')->default(0);
            });
        }
    }

    public function down(): void
    {
        // Keep columns; they are required for SLS-wise allocation.
    }
};
