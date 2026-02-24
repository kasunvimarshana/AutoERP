<?php

namespace Modules\HR\Application\Listeners;

use Modules\HR\Application\UseCases\CreateEmployeeUseCase;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use Modules\Recruitment\Domain\Events\ApplicantHired;


class HandleApplicantHiredListener
{
    public function __construct(
        private CreateEmployeeUseCase $createEmployee,
        private EmployeeRepositoryInterface $employeeRepo,
    ) {}

    public function handle(ApplicantHired $event): void
    {
        if ($event->tenantId === '') {
            return;
        }

        if ($event->candidateName === '') {
            return;
        }

        if ($event->email === '') {
            return;
        }

        // Idempotency guard: skip if an employee with this email already exists.
        $existing = $this->employeeRepo->findByEmail($event->tenantId, $event->email);
        if ($existing !== null) {
            return;
        }

        // Parse candidateName into first_name / last_name.
        $parts     = explode(' ', trim($event->candidateName), 2);
        $firstName = $parts[0];
        $lastName  = $parts[1] ?? $firstName;

        try {
            $this->createEmployee->execute([
                'tenant_id'  => $event->tenantId,
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $event->email,
                'phone'      => $event->phone,
                'status'     => 'active',
                'hire_date'  => now()->toDateString(),
            ]);
        } catch (\Throwable) {
            // Graceful degradation: an employee creation failure must never
            // prevent the hiring action from completing.
        }
    }
}
