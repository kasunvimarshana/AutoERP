<?php

namespace Modules\Currency\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Currency\Domain\Contracts\CurrencyRepositoryInterface;
use Modules\Currency\Domain\Contracts\ExchangeRateRepositoryInterface;
use Modules\Currency\Domain\Events\ExchangeRateRecorded;

class RecordExchangeRateUseCase
{
    public function __construct(
        private CurrencyRepositoryInterface      $currencyRepo,
        private ExchangeRateRepositoryInterface  $exchangeRateRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $fromCode = strtoupper(trim($data['from_currency_code'] ?? ''));
            $toCode   = strtoupper(trim($data['to_currency_code'] ?? ''));

            if ($fromCode === $toCode) {
                throw new DomainException('From and To currency codes must be different.');
            }

            $rate = bcadd((string) ($data['rate'] ?? '0'), '0', 8);

            if (bccomp($rate, '0', 8) <= 0) {
                throw new DomainException('Exchange rate must be greater than zero.');
            }

            $from = $this->currencyRepo->findByCode($data['tenant_id'], $fromCode);
            if (! $from) {
                throw new DomainException("Currency '{$fromCode}' not found for this tenant.");
            }

            $to = $this->currencyRepo->findByCode($data['tenant_id'], $toCode);
            if (! $to) {
                throw new DomainException("Currency '{$toCode}' not found for this tenant.");
            }

            $exchangeRate = $this->exchangeRateRepo->create([
                'tenant_id'          => $data['tenant_id'],
                'from_currency_code' => $fromCode,
                'to_currency_code'   => $toCode,
                'rate'               => $rate,
                'source'             => $data['source'] ?? 'manual',
                'effective_date'     => $data['effective_date'] ?? now()->toDateString(),
            ]);

            Event::dispatch(new ExchangeRateRecorded(
                $exchangeRate->id,
                $exchangeRate->tenant_id,
                $exchangeRate->from_currency_code,
                $exchangeRate->to_currency_code,
                $exchangeRate->rate,
            ));

            return $exchangeRate;
        });
    }
}
