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
        if (!Schema::hasTable('budget_phase')) {
            Schema::create('budget_phase', function (Blueprint $table) {
                $table->id();
                $table->string('financial_year', 20);
                $table->string('budget_phase', 10);
                $table->unsignedBigInteger('budget_head_id');
                $table->decimal('budget_amount', 15, 5);
                $table->tinyInteger('status');
                $table->tinyInteger('draft_flag');
                $table->timestamps();

                // Foreign key constraint
                $table->foreign('budget_head_id')->references('id')->on('budget_heads')->onDelete('cascade');
                
                // Indexes for better performance
                $table->index(['financial_year', 'budget_phase']);
                $table->index('budget_head_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_phase');
    }
};
