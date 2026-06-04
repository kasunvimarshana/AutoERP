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
        foreach (VoucherDocumentSeedCatalog::documentTypes() as $documentType) {
            DB::table('document_types')->updateOrInsert(
                [
                    'tenant_id' => $documentType['tenant_id'] ?? null,
                    'code' => $documentType['code'],
                ],
                $documentType,
            );
        }
    }
}
