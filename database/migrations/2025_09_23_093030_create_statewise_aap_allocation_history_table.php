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
        Schema::create('statewise_aap_allocation_history', function (Blueprint $table) {
            // $table->id();
            // $table->timestamps();
            $table->id('history_id');
            $table->integer('id'); 
            $table->string('financial_year', 50);
            $table->integer('state_id');
            $table->bigInteger('pd_id');
            $table->decimal('amount', 15, 5);
            $table->tinyInteger('status');
            $table->string('remark', 255)->nullable();
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
        Schema::dropIfExists('statewise_aap_allocation_history');
    }
};
