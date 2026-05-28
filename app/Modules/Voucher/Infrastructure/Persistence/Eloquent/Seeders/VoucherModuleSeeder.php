<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;

class VoucherModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders\VoucherDocumentTypesSeeder::class,
            \Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders\VoucherDocumentDefinitionsSeeder::class,
            \Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders\VoucherDocumentWorkflowSeeder::class,
            \Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders\VoucherDocumentSequenceSeeder::class,
            \Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders\VoucherSettingsSeeder::class,
        ]);
    }
}
