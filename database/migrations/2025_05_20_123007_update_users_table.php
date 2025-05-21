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
            // Adding new columns
            $table->string('first_name', 50)->after('id');
            $table->string('last_name', 50)->nullable()->after('first_name');
            $table->string('mobile_number', 15)->nullable()->after('last_name');
            $table->unsignedBigInteger('designation_id')->nullable()->after('mobile_number');
            $table->unsignedBigInteger('program_division_id')->nullable()->after('designation_id');
            $table->unsignedBigInteger('user_type_id')->nullable()->after('program_division_id');
            $table->boolean('is_active')->default(true)->after('user_type_id');

            // Adding foreign key constraints
            $table->foreign('designation_id')->references('designation_id')->on('md_designations');
            $table->foreign('program_division_id')->references('division_id')->on('md_program_divisions');
            $table->foreign('user_type_id')->references('md_user_type_id')->on('md_user_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Dropping foreign key constraints
            $table->dropForeign(['designation_id']);
            $table->dropForeign(['program_division_id']);
            $table->dropForeign(['user_type_id']);

            // Dropping columns
            $table->dropColumn([
                'first_name',
                'last_name',
                'mobile_number',
                'designation_id',
                'program_division_id',
                'user_type_id',
                'is_active',
            ]);
        });
    }
};
