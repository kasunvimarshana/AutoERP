<?php

namespace App\Services\Finance;

use App\Models\Finance\Currency;
use App\Models\Finance\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for currency operations and conversions
 */
class CurrencyService
{
    /**
     * Get all active currencies
     */
    public function getActiveCurrencies(): Collection
    {
        return Currency::active()->get();
    }

    /**
     * Get default currency
     */
    public function getDefaultCurrency(): ?Currency
    {
        return Currency::default()->first();
    }

    /**
     * Get currency by code
     */
    public function getCurrencyByCode(string $code): ?Currency
    {
        return Currency::where('code', $code)->first();
    }

    /**
     * Convert amount from one currency to another
     * 
     * @param float|int $amount Amount to convert
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @param Carbon|null $date Date for exchange rate (default: today)
     * @return float Converted amount
     * @throws \Exception
     */
    public function convert(
        float|int $amount,
        string $fromCurrency,
        string $toCurrency,
        ?Carbon $date = null
    ): float {
        // If same currency, no conversion needed
        if ($fromCurrency === $toCurrency) {
            return (float) $amount;
        }

        $date = $date ?? Carbon::today();

        // Get exchange rate
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency, $date);

        if (!$rate) {
            throw new \Exception("Exchange rate not found for {$fromCurrency} to {$toCurrency}");
        }

        return $amount * $rate;
    }

    /**
     * Get exchange rate between two currencies
     * 
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @param Carbon|null $date Date for exchange rate
     * @return float|null Exchange rate or null if not found
     */
    public function getExchangeRate(
        string $fromCurrency,
        string $toCurrency,
        ?Carbon $date = null
    ): ?float {
        $date = $date ?? Carbon::today();

        $from = Currency::where('code', $fromCurrency)->first();
        $to = Currency::where('code', $toCurrency)->first();

        if (!$from || !$to) {
            return null;
        }

        // Try to find direct exchange rate
        $exchangeRate = ExchangeRate::where('from_currency_id', $from->id)
            ->where('to_currency_id', $to->id)
            ->forDate($date)
            ->first();

        if ($exchangeRate) {
            return (float) $exchangeRate->rate;
        }

        // Try inverse rate
        $exchangeRate = ExchangeRate::where('from_currency_id', $to->id)
            ->where('to_currency_id', $from->id)
            ->forDate($date)
            ->first();

        if ($exchangeRate) {
            return $exchangeRate->inverseRate();
        }

        // Use default exchange rates stored in currency table
        if ($from->exchange_rate && $to->exchange_rate) {
            return (float) $to->exchange_rate / (float) $from->exchange_rate;
        }

        return null;
    }

    /**
     * Update exchange rate between two currencies
     * 
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @param float $rate Exchange rate
     * @param string $source Source of the rate
     * @param Carbon|null $effectiveDate Effective date
     * @return ExchangeRate
     */
    public function updateExchangeRate(
        string $fromCurrency,
        string $toCurrency,
        float $rate,
        string $source = 'manual',
        ?Carbon $effectiveDate = null
    ): ExchangeRate {
        $from = Currency::where('code', $fromCurrency)->firstOrFail();
        $to = Currency::where('code', $toCurrency)->firstOrFail();
        $effectiveDate = $effectiveDate ?? Carbon::today();

        return ExchangeRate::create([
            'from_currency_id' => $from->id,
            'to_currency_id' => $to->id,
            'rate' => $rate,
            'effective_date' => $effectiveDate,
            'source' => $source,
        ]);
    }

    /**
     * Create a new currency
     * 
     * @param array $data Currency data
     * @return Currency
     */
    public function createCurrency(array $data): Currency
    {
        return Currency::create($data);
    }

    /**
     * Update currency
     * 
     * @param Currency $currency
     * @param array $data
     * @return Currency
     */
    public function updateCurrency(Currency $currency, array $data): Currency
    {
        $currency->update($data);
        return $currency->fresh();
    }

    /**
     * Set currency as default
     * 
     * @param Currency $currency
     * @return void
     */
    public function setDefaultCurrency(Currency $currency): void
    {
        $currency->setAsDefault();
    }

    /**
     * Format amount in specified currency
     * 
     * @param float|int $amount
     * @param string $currencyCode
     * @return string
     */
    public function formatAmount(float|int $amount, string $currencyCode): string
    {
        $currency = $this->getCurrencyByCode($currencyCode);
        
        if (!$currency) {
            return number_format($amount, 2);
        }

        return $currency->format($amount);
    }
}
