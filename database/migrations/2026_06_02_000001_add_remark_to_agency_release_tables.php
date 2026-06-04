<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agency_release_tsa', function (Blueprint $table) {
            $table->text('remark')->nullable()->after('central_implementing_agency');
        });

        Schema::table('agency_release_loa', function (Blueprint $table) {
            $table->text('remark')->nullable()->after('ut');
        });

        Schema::table('agency_release_administrative_expenditure', function (Blueprint $table) {
            $table->text('remark')->nullable()->after('agency_vendor');
        });
    }

    public function down(): void
    {
        Schema::table('agency_release_tsa', function (Blueprint $table) {
            $table->dropColumn('remark');
        });

        Schema::table('agency_release_loa', function (Blueprint $table) {
            $table->dropColumn('remark');
        });

        Schema::table('agency_release_administrative_expenditure', function (Blueprint $table) {
            $table->dropColumn('remark');
        });
    }
};

