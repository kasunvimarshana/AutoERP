<?php

namespace Database\Seeders\POS;

use Illuminate\Database\Seeder;
use App\Modules\POS\Models\POSTransaction;

class POSTransactionSeeder extends Seeder
{
    public function run(): void
    {
        POSTransaction::factory()->count(20)->completed()->create();
        POSTransaction::factory()->count(5)->pending()->create();
        POSTransaction::factory()->count(3)->cancelled()->create();
        POSTransaction::factory()->count(2)->refunded()->create();
    }
}
