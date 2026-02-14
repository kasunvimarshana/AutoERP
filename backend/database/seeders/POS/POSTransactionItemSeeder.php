<?php

namespace Database\Seeders\POS;

use Illuminate\Database\Seeder;
use App\Modules\POS\Models\POSTransactionItem;

class POSTransactionItemSeeder extends Seeder
{
    public function run(): void
    {
        POSTransactionItem::factory()->count(50)->create();
    }
}
