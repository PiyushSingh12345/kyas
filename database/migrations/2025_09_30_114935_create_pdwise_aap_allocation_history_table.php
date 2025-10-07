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
        Schema::create('pdwise_aap_allocation_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->integer('id'); 
            $table->string('financial_year', 20);
            $table->integer('bh_id');
            $table->unsignedBigInteger('pd_id');
            $table->decimal('amount', 15, 3);
            $table->integer('status');
            $table->text('remark')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

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
        Schema::dropIfExists('pdwise_aap_allocation_history');
    }
};
