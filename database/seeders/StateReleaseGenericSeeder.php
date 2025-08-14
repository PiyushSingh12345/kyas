<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StateReleaseGeneric;
use Illuminate\Support\Facades\DB;

class StateReleaseGenericSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // First, delete existing data (can't truncate due to foreign key constraints)
            StateReleaseGeneric::query()->delete();
            
            // Reset auto-increment
            DB::statement('ALTER TABLE state_release_generic AUTO_INCREMENT = 1');
            
            // Fixed budget heads for the first 3 columns
            $fixedBudgetHeads = [
                'Total Allocation for FY 2025-26',
                'Allocation for FY 2025-26 (State Share)',
                'Allocation for FY 2025-26 (Center Share)'
            ];

            foreach ($fixedBudgetHeads as $budgetHead) {
                StateReleaseGeneric::create([
                    'allocation_name' => $budgetHead,
                    'status' => 1
                ]);
            }

            // Add more static budget heads for columns 3, 4, 5 (if needed)
            $additionalStaticBudgetHeads = [
                'Annual Allocation (Center Share) General Component',
                'Annual Allocation (Center Share) SCSP',
                'Annual Allocation (Center Share) TSP'
            ];

            foreach ($additionalStaticBudgetHeads as $budgetHead) {
                StateReleaseGeneric::create([
                    'allocation_name' => $budgetHead,
                    'status' => 1
                ]);
            }

            // Dynamic budget heads from budget_heads table
            $dynamicBudgetHeads = DB::table('budget_heads')
                ->select('id', 'budget', 'description', 'category')
                ->where('status', 1)
                ->orderBy('budget')
                ->get();

            foreach ($dynamicBudgetHeads as $budgetHead) {
                StateReleaseGeneric::create([
                    'allocation_name' => "Annual Allocation (Center Share) {$budgetHead->description}",
                    'status' => 1
                ]);
            }

            $this->command->info('StateReleaseGeneric seeded with ' . StateReleaseGeneric::count() . ' budget heads');
            
        } catch (\Exception $e) {
            $this->command->error('Error seeding StateReleaseGeneric: ' . $e->getMessage());
            throw $e;
        }
    }
}
