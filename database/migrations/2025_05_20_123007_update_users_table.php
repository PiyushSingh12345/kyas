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
        Schema::table('users', function (Blueprint $table) {
            // Add only missing columns so test migrations stay stable.
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 50)->after('id');
            }

            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 50)->nullable()->after('first_name');
            }

            if (! Schema::hasColumn('users', 'mobile_number')) {
                $table->string('mobile_number', 15)->nullable()->after('last_name');
            }

            if (! Schema::hasColumn('users', 'designation_id')) {
                $table->unsignedBigInteger('designation_id')->nullable()->after('mobile_number');
            }

            if (! Schema::hasColumn('users', 'program_division_id')) {
                $table->unsignedBigInteger('program_division_id')->nullable()->after('designation_id');
            }

            if (! Schema::hasColumn('users', 'user_type_id')) {
                $table->unsignedBigInteger('user_type_id')->nullable()->after('program_division_id');
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('user_type_id');
            }
        });

        // Foreign keys are intentionally skipped here to avoid failures on
        // environments where legacy schema types were adjusted directly.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop FK constraints defensively when present.
            try {
                $table->dropForeign(['designation_id']);
            } catch (\Throwable $e) {
                // Ignore missing constraint.
            }

            try {
                $table->dropForeign(['program_division_id']);
            } catch (\Throwable $e) {
                // Ignore missing constraint.
            }

            try {
                $table->dropForeign(['user_type_id']);
            } catch (\Throwable $e) {
                // Ignore missing constraint.
            }

            // Dropping columns
            $columnsToDrop = [];

            foreach ([
                'first_name',
                'last_name',
                'mobile_number',
                'designation_id',
                'program_division_id',
                'user_type_id',
                'is_active',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
