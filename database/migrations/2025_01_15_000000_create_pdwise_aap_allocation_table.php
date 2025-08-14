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
        Schema::create('pdwise_aap_allocation', function (Blueprint $table) {
            $table->id();
            $table->string('financial_year', 20);
            $table->integer('bh_id'); // budget head ID - matches budget_heads.id type
            $table->unsignedBigInteger('pd_id'); // program division ID - matches md_program_divisions.division_id type
            $table->decimal('amount', 15, 3); // amount with 3 decimal places
            $table->integer('status')->default(1);
            $table->text('remark')->nullable();
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['financial_year', 'bh_id', 'pd_id']);
            $table->index('bh_id');
            $table->index('pd_id');

            // Add foreign key constraints
            $table->foreign('bh_id')->references('id')->on('budget_heads')->onDelete('cascade');
            $table->foreign('pd_id')->references('division_id')->on('md_program_divisions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdwise_aap_allocation');
    }
};
