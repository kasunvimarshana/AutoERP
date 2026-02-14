<?php

namespace App\Modules\Appointments\Database\Seeders;

use App\Modules\Appointments\Models\ServiceBay;
use Illuminate\Database\Seeder;

class ServiceBaySeeder extends Seeder
{
    public function run(): void
    {
        ServiceBay::factory()->count(5)->available()->create();
        ServiceBay::factory()->count(2)->occupied()->create();
        ServiceBay::factory()->count(1)->maintenance()->create();
    }
}
