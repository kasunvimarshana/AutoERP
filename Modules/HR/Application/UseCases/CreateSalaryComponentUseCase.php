<?php

namespace Modules\HR\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\HR\Domain\Contracts\SalaryComponentRepositoryInterface;
use Modules\HR\Domain\Enums\SalaryComponentType;
use Modules\HR\Domain\Events\SalaryComponentCreated;

class CreateSalaryComponentUseCase
{
    public function __construct(
        private SalaryComponentRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id;
            $code     = strtoupper(trim($data['code'] ?? ''));

            if (empty($code)) {
                throw new \DomainException('Salary component code cannot be empty.');
            }

            $existing = $this->repo->findByCode($tenantId, $code);
            if ($existing) {
                throw new \DomainException("A salary component with code [{$code}] already exists.");
            }

            $amount = bcadd($data['default_amount'] ?? '0.00000000', '0.00000000', 8);

            $component = $this->repo->create(array_merge($data, [
                'tenant_id'      => $tenantId,
                'code'           => $code,
                'type'           => $data['type'] ?? SalaryComponentType::Earning->value,
                'default_amount' => $amount,
                'is_active'      => true,
            ]));

            Event::dispatch(new SalaryComponentCreated(
                $component->id,
                $tenantId,
                $code,
                $component->type,
            ));

            return $component;
        });
    }
}
