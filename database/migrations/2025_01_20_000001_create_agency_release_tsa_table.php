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
        Schema::create('agency_release_tsa', function (Blueprint $table) {
            $table->id();
            $table->string('sanction_number');
            $table->date('date');
            $table->string('budget_head');
            $table->text('purpose_of_grant');
            $table->unsignedBigInteger('program_division_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('central_implementing_agency');
            $table->boolean('status')->default(1);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('program_division_id', 'agency_tsa_pd_foreign')->references('division_id')->on('md_program_divisions')->onDelete('set null');

            // Indexes for better performance
            $table->index('sanction_number');
            $table->index('date');
            $table->index('budget_head');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_release_tsa');
    }
};

