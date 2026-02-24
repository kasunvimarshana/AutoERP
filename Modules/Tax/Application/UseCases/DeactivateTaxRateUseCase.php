<?php

namespace Modules\Tax\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Tax\Domain\Contracts\TaxRateRepositoryInterface;
use Modules\Tax\Domain\Events\TaxRateDeactivated;

class DeactivateTaxRateUseCase
{
    public function __construct(
        private TaxRateRepositoryInterface $taxRateRepo,
    ) {}

    public function execute(string $taxRateId): object
    {
        return DB::transaction(function () use ($taxRateId) {
            $taxRate = $this->taxRateRepo->findById($taxRateId);

            if (! $taxRate) {
                throw new DomainException('Tax rate not found.');
            }

            if (! $taxRate->is_active) {
                throw new DomainException('Tax rate is already inactive.');
            }

            $updated = $this->taxRateRepo->update($taxRateId, [
                'is_active' => false,
            ]);

            Event::dispatch(new TaxRateDeactivated($taxRateId, $taxRate->tenant_id));

            return $updated;
        });
    }
}
