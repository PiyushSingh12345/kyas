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
        Schema::create('daily_sanction_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('daily_sanction_id');
            $table->string('financial_year', 20)->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->date('ds_date')->nullable();
            $table->string('daily_sanction_no', 100)->nullable();
            $table->string('mother_sanction', 100)->nullable();
            $table->string('ifd_no', 100)->nullable();
            $table->string('sls_name', 255)->nullable();
            $table->string('budget_head', 255)->nullable();
            $table->decimal('mother_sanction_amount', 15, 2)->nullable();
            $table->decimal('available_amount', 15, 2)->nullable();
            $table->decimal('center_share_amount', 15, 2)->nullable();
            $table->text('remark')->nullable();
            $table->tinyInteger('status')->default(1);

            // History tracking
            $table->string('action_type', 50);
            $table->string('changed_by', 100)->nullable();
            $table->timestamp('history_timestamp')->useCurrent();
            $table->text('change_description')->nullable();
            $table->decimal('old_center_share_amount', 15, 2)->nullable();
            $table->decimal('new_center_share_amount', 15, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_sanction_history');
    }
};
