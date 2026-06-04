<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;

final class InvoiceModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            InvoiceTypeSeeder::class,
        ]);
    }
}
