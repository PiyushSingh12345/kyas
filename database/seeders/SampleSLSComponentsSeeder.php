<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleSLSComponentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some state IDs for testing
        $stateIds = DB::table('states')->pluck('id')->take(5)->toArray();
        
        if (empty($stateIds)) {
            $this->command->error('No states found. Please run states seeder first.');
            return;
        }

        $sampleComponents = [
            // PD Components (Program Divisions)
            [
                'component' => 'PD',
                'slsPD' => null,
                'state_id' => 0, // PD components are not state-specific
                'name' => 'Agricultural Extension',
                'status' => 1
            ],
            [
                'component' => 'PD',
                'slsPD' => null,
                'state_id' => 0,
                'name' => 'Mission for Integrated Development of Horticulture',
                'status' => 1
            ],
            [
                'component' => 'PD',
                'slsPD' => null,
                'state_id' => 0,
                'name' => 'National Bamboo Mission',
                'status' => 1
            ],
            [
                'component' => 'PD',
                'slsPD' => null,
                'state_id' => 0,
                'name' => 'National Food Security and Nutrition Mission',
                'status' => 1
            ],
            
            // SLS Components (State Linked Schemes) for different states
            [
                'component' => 'SL',
                'slsPD' => 'Agricultural Extension',
                'state_id' => $stateIds[0],
                'name' => 'ATMA - SAM MISSION',
                'status' => 1
            ],
            [
                'component' => 'SL',
                'slsPD' => 'Agricultural Extension',
                'state_id' => $stateIds[0],
                'name' => 'Soil Health and Fertility',
                'status' => 1
            ],
            [
                'component' => 'SL',
                'slsPD' => 'Agricultural Extension',
                'state_id' => $stateIds[0],
                'name' => 'Seeds',
                'status' => 1
            ],
            [
                'component' => 'SL',
                'slsPD' => 'Mission for Integrated Development of Horticulture',
                'state_id' => $stateIds[1],
                'name' => 'Horticulture Development',
                'status' => 1
            ],
            [
                'component' => 'SL',
                'slsPD' => 'National Bamboo Mission',
                'state_id' => $stateIds[2],
                'name' => 'Bamboo Cultivation',
                'status' => 1
            ],
            [
                'component' => 'SL',
                'slsPD' => 'National Food Security and Nutrition Mission',
                'state_id' => $stateIds[3],
                'name' => 'Food Security Program',
                'status' => 1
            ]
        ];

        foreach ($sampleComponents as $component) {
            DB::table('pd_and_sls_comp')->insert([
                'component' => $component['component'],
                'slsPD' => $component['slsPD'],
                'state_id' => $component['state_id'],
                'name' => $component['name'],
                'status' => $component['status'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $this->command->info('Sample SLS components seeded successfully!');
    }
}
