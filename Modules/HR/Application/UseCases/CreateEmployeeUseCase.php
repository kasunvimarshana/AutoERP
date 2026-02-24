<?php

namespace Modules\HR\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Domain\Events\EmployeeCreated;

class CreateEmployeeUseCase
{
    public function __construct(
        private EmployeeRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id ?? null;

            $employee = $this->repo->create(array_merge($data, [
                'tenant_id' => $tenantId,
                'salary'    => $data['salary'] ?? '0.00000000',
                'status'    => $data['status'] ?? 'active',
            ]));

            Event::dispatch(new EmployeeCreated($employee->id));

            return $employee;
        });
    }
}
