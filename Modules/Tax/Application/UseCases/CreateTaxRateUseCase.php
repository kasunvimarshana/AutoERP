<?php

namespace Modules\Tax\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Tax\Domain\Contracts\TaxRateRepositoryInterface;
use Modules\Tax\Domain\Events\TaxRateCreated;

class CreateTaxRateUseCase
{
    public function __construct(
        private TaxRateRepositoryInterface $taxRateRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $rate = bcadd((string) ($data['rate'] ?? '0'), '0', 8);

            if (bccomp($rate, '0', 8) < 0) {
                throw new DomainException('Tax rate must be zero or positive.');
            }

            $taxRate = $this->taxRateRepo->create([
                'tenant_id'   => $data['tenant_id'],
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'type'        => $data['type'] ?? 'percentage',
                'rate'        => $rate,
                'region'      => $data['region'] ?? null,
                'is_active'   => true,
                'start_date'  => $data['start_date'] ?? null,
                'end_date'    => $data['end_date'] ?? null,
            ]);

            Event::dispatch(new TaxRateCreated(
                $taxRate->id,
                $taxRate->tenant_id,
                $taxRate->name,
                $taxRate->rate,
            ));

            return $taxRate;
        });
    }
}
