<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders\Support\VoucherDocumentSeedCatalog;

class VoucherDocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('document_types')->upsert(
            VoucherDocumentSeedCatalog::documentTypes(),
            ['code'],
            ['name', 'default_status', 'is_active', 'requires_source'],
        );
    }
}
