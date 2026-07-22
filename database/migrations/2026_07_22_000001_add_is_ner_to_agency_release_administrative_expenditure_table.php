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
        Schema::table('agency_release_administrative_expenditure', function (Blueprint $table) {
            $table->boolean('is_ner')->default(false)->after('agency_vendor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency_release_administrative_expenditure', function (Blueprint $table) {
            $table->dropColumn('is_ner');
        });
    }
};
