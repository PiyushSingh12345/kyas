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
        Schema::create('ky_user_details', function (Blueprint $table) {
            $table->bigIncrements('user_id');
            $table->string('first_name', 50);
            $table->string('last_name', 50)->nullable();
            $table->string('password_hash', 255);
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->unsignedBigInteger('program_division_id')->nullable();
            $table->string('mobile_number', 15)->nullable();
            $table->string('email', 100)->unique()->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('designation_id')->references('designation_id')->on('md_designations');
            $table->foreign('program_division_id')->references('division_id')->on('md_program_divisions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ky_user_details');
    }
};
