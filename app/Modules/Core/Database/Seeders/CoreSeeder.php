<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;

final class CoreSeeder extends Seeder
{
    public function run(): void
    {
        // Core owns shared infrastructure but no bootstrap database records.
    }
}
