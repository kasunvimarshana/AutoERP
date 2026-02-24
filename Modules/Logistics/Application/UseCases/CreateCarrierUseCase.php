<?php

namespace Modules\Logistics\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Logistics\Domain\Contracts\CarrierRepositoryInterface;

class CreateCarrierUseCase
{
    public function __construct(
        private CarrierRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id ?? null;

            return $this->repo->create(array_merge($data, [
                'tenant_id' => $tenantId,
                'is_active' => $data['is_active'] ?? true,
            ]));
        });
    }
}
