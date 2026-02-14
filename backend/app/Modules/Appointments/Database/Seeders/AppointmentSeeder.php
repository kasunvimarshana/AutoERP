<?php

namespace App\Modules\Appointments\Database\Seeders;

use App\Modules\Appointments\Models\Appointment;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        Appointment::factory()->count(10)->scheduled()->create();
        Appointment::factory()->count(5)->confirmed()->create();
        Appointment::factory()->count(3)->inProgress()->create();
        Appointment::factory()->count(7)->completed()->create();
    }
}
