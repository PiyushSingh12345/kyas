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
        Schema::create('mother_sanction_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('mother_sanction_id'); // reference to original mother_sanction.id
            $table->string('financial_year', 20);
            $table->unsignedBigInteger('state_id');
            $table->string('ms_sequence_no', 50)->nullable();
            $table->string('file_no', 100)->nullable();
            $table->string('ifd_no', 100)->nullable();
            $table->date('sanction_date')->nullable();
            $table->string('ky_ms_no', 100);
            $table->string('sls_name', 255)->nullable();
            $table->string('pd_component', 255)->nullable();
            $table->decimal('total_mother_sanction_amount', 15, 2)->nullable();
            $table->string('budget_head', 255)->nullable();
            $table->string('category', 100)->nullable();
            $table->decimal('available_fund', 15, 2)->nullable();
            $table->decimal('mother_sanction_amount', 15, 2)->nullable();
            $table->decimal('carry_forward_amount', 15, 5)->nullable();
            $table->string('uc_received_from_State', 255)->nullable();
            $table->string('signed_copy_of_mother_sanction', 255)->nullable();
            $table->integer('last_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->text('remark')->nullable();
            $table->timestamps();

            // Extra fields for history tracking
            $table->enum('action_type', ['CREATE', 'UPDATE', 'CLOSE', 'REVISE', 'DEACTIVATE', 'ACTIVATE']);
            $table->string('changed_by', 100)->nullable();
            $table->timestamp('history_timestamp')->useCurrent();
            $table->text('change_description')->nullable(); // Description of what changed
            $table->decimal('old_mother_sanction_amount', 15, 2)->nullable(); // Previous MS amount
            $table->decimal('new_mother_sanction_amount', 15, 2)->nullable(); // New MS amount
            $table->decimal('old_available_fund', 15, 2)->nullable(); // Previous available fund
            $table->decimal('new_available_fund', 15, 2)->nullable(); // New available fund
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mother_sanction_history');
    }
};

