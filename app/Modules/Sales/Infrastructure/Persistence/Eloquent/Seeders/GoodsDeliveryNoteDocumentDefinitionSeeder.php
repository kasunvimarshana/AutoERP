<?php

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;

class GoodsDeliveryNoteDocumentDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([SalesDocumentDefinitionsSeeder::class]);
    }
}
