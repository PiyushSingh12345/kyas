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
        Schema::create('agency_release_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->string('release_type', 50); // tsa, loa, administrative-expenditure
            $table->unsignedBigInteger('release_id');

            $table->string('sanction_number', 255)->nullable();
            $table->date('date')->nullable();
            $table->string('budget_head', 255)->nullable();
            $table->text('purpose_of_grant')->nullable();
            $table->unsignedBigInteger('program_division_id')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('central_implementing_agency', 255)->nullable();
            $table->string('ut', 255)->nullable();
            $table->string('agency_vendor', 255)->nullable();
            $table->tinyInteger('status')->default(1);

            // History tracking
            $table->string('action_type', 50); // CREATE, CLOSE, REVISE, UPDATE, etc.
            $table->string('changed_by', 100)->nullable();
            $table->timestamp('history_timestamp')->useCurrent();
            $table->text('change_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_release_history');
    }
};

