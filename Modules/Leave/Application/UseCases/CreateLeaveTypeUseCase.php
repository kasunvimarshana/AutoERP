<?php

namespace Modules\Leave\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Leave\Domain\Contracts\LeaveTypeRepositoryInterface;

class CreateLeaveTypeUseCase
{
    public function __construct(
        private LeaveTypeRepositoryInterface $leaveTypeRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            return $this->leaveTypeRepo->create([
                'tenant_id'    => $data['tenant_id'],
                'name'         => $data['name'],
                'description'  => $data['description'] ?? null,
                'max_days'     => $data['max_days'] ?? null,
                'is_paid'      => $data['is_paid'] ?? true,
                'is_active'    => true,
            ]);
        });
    }
}
