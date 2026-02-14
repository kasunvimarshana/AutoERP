<?php

namespace Database\Seeders\JobCards;

use Illuminate\Database\Seeder;
use App\Modules\JobCards\Models\JobCard;

class JobCardSeeder extends Seeder
{
    public function run(): void
    {
        JobCard::factory()->count(10)->open()->create();
        JobCard::factory()->count(15)->inProgress()->create();
        JobCard::factory()->count(20)->completed()->create();
        JobCard::factory()->count(5)->cancelled()->create();
    }
}
