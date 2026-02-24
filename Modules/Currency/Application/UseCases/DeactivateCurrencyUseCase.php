<?php

namespace Modules\Currency\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Currency\Domain\Contracts\CurrencyRepositoryInterface;
use Modules\Currency\Domain\Events\CurrencyDeactivated;

class DeactivateCurrencyUseCase
{
    public function __construct(
        private CurrencyRepositoryInterface $currencyRepo,
    ) {}

    public function execute(string $id): object
    {
        return DB::transaction(function () use ($id) {
            $currency = $this->currencyRepo->findById($id);

            if (! $currency) {
                throw new DomainException('Currency not found.');
            }

            if (! $currency->is_active) {
                throw new DomainException('Currency is already inactive.');
            }

            $updated = $this->currencyRepo->update($id, ['is_active' => false]);

            Event::dispatch(new CurrencyDeactivated(
                $updated->id,
                $updated->tenant_id,
                $updated->code,
            ));

            return $updated;
        });
    }
}
