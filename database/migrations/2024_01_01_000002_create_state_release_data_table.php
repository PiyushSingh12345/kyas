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
        Schema::create('state_release_data', function (Blueprint $table) {
            $table->id();
            $table->string('fy'); // Financial year like 2025-26
            $table->integer('state_id');
            $table->integer('SLS_id');
            $table->unsignedBigInteger('budget_head_id');
            $table->decimal('amount', 15, 5); // Decimal with up to 5 decimal places
            $table->boolean('flag')->default(0);
            $table->boolean('isactive')->default(1);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
            $table->foreign('SLS_id')->references('id')->on('pd_and_sls_comp')->onDelete('cascade');
            $table->foreign('budget_head_id')->references('id')->on('state_release_generic')->onDelete('cascade');

            // Indexes for better performance
            $table->index(['fy', 'state_id']);
            $table->index(['SLS_id']);
            $table->index(['budget_head_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('state_release_data');
    }
};
