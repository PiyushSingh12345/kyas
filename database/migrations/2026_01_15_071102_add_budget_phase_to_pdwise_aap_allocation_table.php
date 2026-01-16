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
        Schema::table('pdwise_aap_allocation', function (Blueprint $table) {
            // Add budget_phase column
            $table->string('budget_phase', 10)->nullable()->after('financial_year');
        });
        
        // Drop the existing composite index by column names
        try {
            Schema::table('pdwise_aap_allocation', function (Blueprint $table) {
                $table->dropIndex(['financial_year', 'bh_id', 'pd_id']);
            });
        } catch (\Exception $e) {
            // Index might not exist or have different name, continue
        }
        
        // Create new composite index including budget_phase
        Schema::table('pdwise_aap_allocation', function (Blueprint $table) {
            $table->index(['financial_year', 'budget_phase', 'bh_id', 'pd_id'], 'pdwise_aap_allocation_composite_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdwise_aap_allocation', function (Blueprint $table) {
            // Drop the new composite index
            $table->dropIndex('pdwise_aap_allocation_composite_index');
            
            // Restore original index
            $table->index(['financial_year', 'bh_id', 'pd_id']);
            
            $table->dropColumn('budget_phase');
        });
    }
};
