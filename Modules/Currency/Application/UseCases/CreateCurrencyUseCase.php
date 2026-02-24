<?php

namespace Modules\Currency\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Currency\Domain\Contracts\CurrencyRepositoryInterface;
use Modules\Currency\Domain\Events\CurrencyCreated;

class CreateCurrencyUseCase
{
    public function __construct(
        private CurrencyRepositoryInterface $currencyRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $code = strtoupper(trim($data['code'] ?? ''));

            if (strlen($code) !== 3) {
                throw new DomainException('Currency code must be exactly 3 characters (ISO 4217).');
            }

            $decimalPlaces = (int) ($data['decimal_places'] ?? 2);

            if ($decimalPlaces < 0 || $decimalPlaces > 8) {
                throw new DomainException('Decimal places must be between 0 and 8.');
            }

            $existing = $this->currencyRepo->findByCode($data['tenant_id'], $code);

            if ($existing) {
                throw new DomainException("Currency code '{$code}' already exists for this tenant.");
            }

            $currency = $this->currencyRepo->create([
                'tenant_id'      => $data['tenant_id'],
                'code'           => $code,
                'name'           => trim($data['name']),
                'symbol'         => trim($data['symbol'] ?? $code),
                'decimal_places' => $decimalPlaces,
                'is_active'      => true,
            ]);

            Event::dispatch(new CurrencyCreated(
                $currency->id,
                $currency->tenant_id,
                $currency->code,
                $currency->name,
            ));

            return $currency;
        });
    }
}
