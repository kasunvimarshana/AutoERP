<?php

namespace Modules\Accounting\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Domain\Contracts\AccountRepositoryInterface;

class CreateAccountUseCase
{
    public function __construct(
        private AccountRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = auth()->user()?->tenant_id ?? $data['tenant_id'] ?? null;

            return $this->repo->create(array_merge($data, [
                'tenant_id' => $tenantId,
                'balance'   => $data['balance'] ?? '0.00000000',
                'is_active' => $data['is_active'] ?? true,
            ]));
        });
    }
}
