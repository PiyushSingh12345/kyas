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
        Schema::table('pd_and_sls_comp', function (Blueprint $table) {
            $table->string('sharing_patter_center')->nullable()->default('0')->change();
            $table->string('sharing_patter_state')->nullable()->default('0')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pd_and_sls_comp', function (Blueprint $table) {
            $table->string('sharing_patter_center')->nullable(false)->change();
            $table->string('sharing_patter_state')->nullable(false)->change();
        });
    }
};
