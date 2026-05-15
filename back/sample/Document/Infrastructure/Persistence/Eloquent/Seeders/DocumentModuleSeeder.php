<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;

class DocumentModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DocumentTenantSeeder::class,
            DocumentTypesSeeder::class,
            ItemTypesSeeder::class,
            DocumentDefinitionsSeeder::class,
            ItemDefinitionsSeeder::class,
            DocumentWorkflowsSeeder::class,
            WorkflowStepsSeeder::class,
            WorkflowTransitionsSeeder::class,
            DocumentSequencesSeeder::class,
        ]);
    }
}
