<?php

namespace Modules\HR\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\HR\Domain\Contracts\PayrollRunRepositoryInterface;

class CreatePayrollRunUseCase
{
    public function __construct(
        private PayrollRunRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id ?? null;

            return $this->repo->create(array_merge($data, [
                'tenant_id'   => $tenantId,
                'status'      => 'draft',
                'total_gross' => '0.00000000',
                'total_net'   => '0.00000000',
            ]));
        });
    }
}
