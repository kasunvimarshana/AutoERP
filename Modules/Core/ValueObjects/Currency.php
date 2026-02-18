<?php

declare(strict_types=1);

namespace Modules\Core\ValueObjects;

use Modules\Core\Exceptions\ValidationException;

/**
 * Currency Value Object
 *
 * Represents a currency with code and symbol
 * Immutable - once created, cannot be changed
 */
final class Currency
{
    private readonly string $code;

    private readonly string $symbol;

    private readonly int $decimals;

    /**
     * Supported currencies with their symbols and decimal places
     */
    private const CURRENCIES = [
        'USD' => ['symbol' => '$', 'decimals' => 2, 'name' => 'US Dollar'],
        'EUR' => ['symbol' => '€', 'decimals' => 2, 'name' => 'Euro'],
        'GBP' => ['symbol' => '£', 'decimals' => 2, 'name' => 'British Pound'],
        'JPY' => ['symbol' => '¥', 'decimals' => 0, 'name' => 'Japanese Yen'],
        'CNY' => ['symbol' => '¥', 'decimals' => 2, 'name' => 'Chinese Yuan'],
        'INR' => ['symbol' => '₹', 'decimals' => 2, 'name' => 'Indian Rupee'],
        'AUD' => ['symbol' => 'A$', 'decimals' => 2, 'name' => 'Australian Dollar'],
        'CAD' => ['symbol' => 'C$', 'decimals' => 2, 'name' => 'Canadian Dollar'],
        'CHF' => ['symbol' => 'Fr', 'decimals' => 2, 'name' => 'Swiss Franc'],
        'SEK' => ['symbol' => 'kr', 'decimals' => 2, 'name' => 'Swedish Krona'],
        'NZD' => ['symbol' => 'NZ$', 'decimals' => 2, 'name' => 'New Zealand Dollar'],
        'SGD' => ['symbol' => 'S$', 'decimals' => 2, 'name' => 'Singapore Dollar'],
        'HKD' => ['symbol' => 'HK$', 'decimals' => 2, 'name' => 'Hong Kong Dollar'],
        'NOK' => ['symbol' => 'kr', 'decimals' => 2, 'name' => 'Norwegian Krone'],
        'KRW' => ['symbol' => '₩', 'decimals' => 0, 'name' => 'South Korean Won'],
        'TRY' => ['symbol' => '₺', 'decimals' => 2, 'name' => 'Turkish Lira'],
        'RUB' => ['symbol' => '₽', 'decimals' => 2, 'name' => 'Russian Ruble'],
        'BRL' => ['symbol' => 'R$', 'decimals' => 2, 'name' => 'Brazilian Real'],
        'ZAR' => ['symbol' => 'R', 'decimals' => 2, 'name' => 'South African Rand'],
    ];

    /**
     * Create a new currency value object
     *
     * @param  string  $code  ISO 4217 currency code (e.g., USD, EUR)
     *
     * @throws ValidationException
     */
    private function __construct(string $code)
    {
        $code = strtoupper($code);

        if (! isset(self::CURRENCIES[$code])) {
            throw ValidationException::forField(
                'currency',
                "Unsupported currency code: {$code}"
            );
        }

        $this->code = $code;
        $this->symbol = self::CURRENCIES[$code]['symbol'];
        $this->decimals = self::CURRENCIES[$code]['decimals'];
    }

    /**
     * Get currency code
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get currency symbol
     */
    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /**
     * Get number of decimal places
     */
    public function getDecimals(): int
    {
        return $this->decimals;
    }

    /**
     * Get currency name
     */
    public function getName(): string
    {
        return self::CURRENCIES[$this->code]['name'];
    }

    /**
     * Check if currency equals another
     */
    public function equals(Currency $other): bool
    {
        return $this->code === $other->code;
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->code;
    }

    /**
     * Create from currency code
     *
     * @throws ValidationException
     */
    public static function fromCode(string $code): static
    {
        return new self($code);
    }

    /**
     * Get USD currency
     */
    public static function USD(): static
    {
        return new static('USD');
    }

    /**
     * Get EUR currency
     */
    public static function EUR(): static
    {
        return new static('EUR');
    }

    /**
     * Get GBP currency
     */
    public static function GBP(): static
    {
        return new static('GBP');
    }

    /**
     * Get all supported currencies
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getAllCurrencies(): array
    {
        return self::CURRENCIES;
    }

    /**
     * Check if currency code is supported
     */
    public static function isSupported(string $code): bool
    {
        return isset(self::CURRENCIES[strtoupper($code)]);
    }

    /**
     * Serialize to array
     *
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'symbol' => $this->symbol,
            'decimals' => $this->decimals,
            'name' => $this->getName(),
        ];
    }
}
