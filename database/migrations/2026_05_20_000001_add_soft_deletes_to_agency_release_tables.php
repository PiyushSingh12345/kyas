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
        Schema::table('agency_release_tsa', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('agency_release_loa', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('agency_release_administrative_expenditure', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency_release_tsa', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('agency_release_loa', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('agency_release_administrative_expenditure', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
