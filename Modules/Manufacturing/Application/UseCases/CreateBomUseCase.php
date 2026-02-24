<?php

namespace Modules\Manufacturing\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Manufacturing\Domain\Contracts\BomLineRepositoryInterface;
use Modules\Manufacturing\Domain\Contracts\BomRepositoryInterface;

class CreateBomUseCase
{
    public function __construct(
        private BomRepositoryInterface     $bomRepo,
        private BomLineRepositoryInterface $bomLineRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id ?? null;

            $bom = $this->bomRepo->create([
                'tenant_id'    => $tenantId,
                'product_id'   => $data['product_id'],
                'product_name' => $data['product_name'],
                'version'      => $data['version'] ?? '1.0',
                'quantity'     => $data['quantity'],
                'unit'         => $data['unit'],
                'status'       => $data['status'] ?? 'draft',
                'notes'        => $data['notes'] ?? null,
            ]);

            foreach ($data['lines'] as $line) {
                $this->bomLineRepo->create([
                    'tenant_id'            => $tenantId,
                    'bom_id'               => $bom->id,
                    'component_product_id' => $line['component_product_id'],
                    'component_name'       => $line['component_name'],
                    'quantity'             => $line['quantity'],
                    'unit'                 => $line['unit'] ?? 'pcs',
                    'scrap_rate'           => $line['scrap_rate'] ?? '0.00',
                ]);
            }

            return $bom->load('lines');
        });
    }
}
