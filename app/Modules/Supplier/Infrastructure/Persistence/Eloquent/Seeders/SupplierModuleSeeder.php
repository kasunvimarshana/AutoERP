<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;

final class SupplierModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SupplierSampleSeeder::class,
        ]);
    }
}
