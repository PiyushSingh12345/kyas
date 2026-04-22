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
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'designation_id')) {
                $table->string('designation_id')->nullable()->change();
            }

            if (Schema::hasColumn('users', 'program_division_id')) {
                $table->string('program_division_id')->nullable()->change();
            }

            if (Schema::hasColumn('users', 'user_type_id')) {
                $table->string('user_type_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'designation_id')) {
                $table->unsignedBigInteger('designation_id')->nullable()->change();
            }

            if (Schema::hasColumn('users', 'program_division_id')) {
                $table->unsignedBigInteger('program_division_id')->nullable()->change();
            }

            if (Schema::hasColumn('users', 'user_type_id')) {
                $table->unsignedBigInteger('user_type_id')->nullable()->change();
            }
        });
    }
};
