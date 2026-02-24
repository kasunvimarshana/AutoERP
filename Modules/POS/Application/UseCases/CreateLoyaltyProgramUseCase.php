<?php

namespace Modules\POS\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\POS\Domain\Contracts\LoyaltyProgramRepositoryInterface;
use Modules\POS\Domain\Events\LoyaltyProgramCreated;
use Modules\Shared\Domain\Contracts\UseCaseInterface;

class CreateLoyaltyProgramUseCase implements UseCaseInterface
{
    public function __construct(
        private LoyaltyProgramRepositoryInterface $loyaltyRepo,
    ) {}

    public function execute(array $data): mixed
    {
        $name     = trim($data['name'] ?? '');
        $tenantId = $data['tenant_id'];
        $ppcUnit  = (string) ($data['points_per_currency_unit'] ?? '1');
        $redemRate = (string) ($data['redemption_rate'] ?? '100');

        if ($name === '') {
            throw new DomainException('Loyalty program name must not be empty.');
        }

        if (bccomp($ppcUnit, '0', 8) <= 0) {
            throw new DomainException('Points per currency unit must be greater than zero.');
        }

        if (bccomp($redemRate, '0', 8) <= 0) {
            throw new DomainException('Redemption rate (points per discount unit) must be greater than zero.');
        }

        return DB::transaction(function () use ($data, $tenantId, $name, $ppcUnit, $redemRate) {
            $program = $this->loyaltyRepo->create([
                'id'                       => (string) Str::uuid(),
                'tenant_id'                => $tenantId,
                'name'                     => $name,
                'points_per_currency_unit' => bcadd($ppcUnit, '0.00000000', 8),
                'redemption_rate'          => bcadd($redemRate, '0.00000000', 8),
                'is_active'                => (bool) ($data['is_active'] ?? true),
                'description'              => $data['description'] ?? null,
            ]);

            Event::dispatch(new LoyaltyProgramCreated(
                programId: $program->id,
                tenantId:  $tenantId,
                name:      $name,
            ));

            return $program;
        });
    }
}
