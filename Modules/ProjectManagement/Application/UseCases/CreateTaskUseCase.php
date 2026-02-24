<?php

namespace Modules\ProjectManagement\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\ProjectManagement\Domain\Contracts\TaskRepositoryInterface;

class CreateTaskUseCase
{
    public function __construct(
        private TaskRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id ?? null;

            return $this->repo->create(array_merge($data, [
                'tenant_id'       => $tenantId,
                'status'          => $data['status'] ?? 'todo',
                'priority'        => $data['priority'] ?? 'medium',
                'estimated_hours' => isset($data['estimated_hours'])
                    ? bcadd((string) $data['estimated_hours'], '0', 2)
                    : '0.00',
                'actual_hours'    => '0.00',
            ]));
        });
    }
}
