<?php

namespace Database\Seeders\JobCards;

use Illuminate\Database\Seeder;
use App\Modules\JobCards\Models\JobCardTask;

class JobCardTaskSeeder extends Seeder
{
    public function run(): void
    {
        JobCardTask::factory()->count(30)->pending()->create();
        JobCardTask::factory()->count(40)->inProgress()->create();
        JobCardTask::factory()->count(50)->completed()->create();
    }
}
