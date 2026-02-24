<?php

namespace Modules\HR\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\HR\Domain\Contracts\SalaryStructureRepositoryInterface;
use Modules\HR\Domain\Events\SalaryStructureCreated;

class CreateSalaryStructureUseCase
{
    public function __construct(
        private SalaryStructureRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id;
            $code     = strtoupper(trim($data['code'] ?? ''));

            if (empty($code)) {
                throw new \DomainException('Salary structure code cannot be empty.');
            }

            $existing = $this->repo->findByCode($tenantId, $code);
            if ($existing) {
                throw new \DomainException("A salary structure with code [{$code}] already exists.");
            }

            $lines = $data['lines'] ?? [];

            if (empty($lines)) {
                throw new \DomainException('A salary structure must have at least one component line.');
            }

            $structure = $this->repo->create(array_merge($data, [
                'tenant_id' => $tenantId,
                'code'      => $code,
                'is_active' => true,
                'lines'     => $lines,
            ]));

            Event::dispatch(new SalaryStructureCreated($structure->id, $tenantId, $code));

            return $structure;
        });
    }
}
