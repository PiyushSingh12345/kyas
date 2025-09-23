<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('budget_phase_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('budget_phase_id'); // reference to original budget_phase.id
            $table->string('financial_year', 20);
            $table->string('budget_phase', 10);
            $table->unsignedBigInteger('budget_head_id');
            $table->decimal('budget_amount', 15, 5);
            $table->tinyInteger('status');
            $table->tinyInteger('draft_flag');
            $table->timestamps(); // creates nullable created_at & updated_at

            // Extra fields for history tracking
            $table->enum('action_type', ['UPDATE', 'DELETE']);
            $table->string('changed_by', 100);
            $table->timestamp('history_timestamp')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_phase_history');
    }
};
