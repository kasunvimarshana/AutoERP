<?php

namespace Modules\HR\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\HR\Domain\Contracts\DepartmentRepositoryInterface;

class CreateDepartmentUseCase
{
    public function __construct(
        private DepartmentRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id ?? null;

            return $this->repo->create(array_merge($data, [
                'tenant_id' => $tenantId,
            ]));
        });
    }
}
