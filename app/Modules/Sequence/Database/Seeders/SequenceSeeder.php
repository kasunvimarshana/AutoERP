<?php

declare(strict_types=1);

namespace Modules\Sequence\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\Sequence\Models\SequenceModel;
use Modules\Sequence\Services\Contracts\SequenceDomainServiceInterface;

final class SequenceSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('sequences')) {
            return;
        }

        $tenant = $this->defaultTenant();
        if ($tenant === null) {
            return;
        }

        DB::transaction(function () use ($tenant): void {
            $domain = app(SequenceDomainServiceInterface::class);

            foreach ($this->sequences() as $sequence) {
                $scopeKey = $domain->scopeKey(null, null);

                SequenceModel::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->getKey(),
                        'document_type' => $sequence['document_type'],
                        'scope_key' => $scopeKey,
                    ],
                    [
                        'organization_unit_id' => null,
                        'prefix' => $sequence['prefix'].'-',
                        'suffix' => '',
                        'padding' => 6,
                        'next_number' => 1,
                        'period_type' => 'infinite',
                        'period_value' => null,
                        'row_version' => 1,
                        'metadata' => ['seed_source' => 'sequence_module'],
                    ],
                );
            }
        }, 3);
    }

    /**
     * @return list<array{document_type:string,prefix:string}>
     */
    private function sequences(): array
    {
        return [
            ['document_type' => 'supplier', 'prefix' => 'SUP'],
            ['document_type' => 'customer', 'prefix' => 'CUS'],
            ['document_type' => 'item', 'prefix' => 'ITEM'],
            ['document_type' => 'vehicle', 'prefix' => 'VEH'],
            ['document_type' => 'employee', 'prefix' => 'EMP'],
            ['document_type' => 'purchase_order', 'prefix' => 'PO'],
            ['document_type' => 'goods_receipt_note', 'prefix' => 'GRN'],
            ['document_type' => 'purchase_return', 'prefix' => 'PRN'],
            ['document_type' => 'invoice', 'prefix' => 'INV'],
            ['document_type' => 'payment', 'prefix' => 'PAY'],
            ['document_type' => 'finance_journal', 'prefix' => 'JE'],
            ['document_type' => 'vehicle_service_job', 'prefix' => 'VSJ'],
        ];
    }
}
