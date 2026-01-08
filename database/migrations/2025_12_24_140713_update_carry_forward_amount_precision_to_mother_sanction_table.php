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
        Schema::table('mother_sanction', function (Blueprint $table) {
            $table->decimal('carry_forward_amount', 15, 5)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mother_sanction', function (Blueprint $table) {
            $table->decimal('carry_forward_amount', 15, 2)->default(0)->change();
        });
    }
};
