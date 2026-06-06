<?php

declare(strict_types=1);

namespace Modules\Configuration\Services\Currencies;

use InvalidArgumentException;
use Modules\Configuration\Constants\ConfigurationErrorCode;
use Modules\Configuration\Repositories\CurrencyRepositoryInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Throwable;

final class CreateCurrencyService
{
    public function __construct(private readonly CurrencyRepositoryInterface $currencies) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): Result
    {
        try {
            $attributes = [
                'code' => $this->requiredString($payload, 'code'),
                'name' => $this->requiredString($payload, 'name'),
                'symbol' => $payload['symbol'] ?? null,
                'decimal_places' => isset($payload['decimal_places']) ? (int) $payload['decimal_places'] : 2,
                'is_active' => isset($payload['is_active']) ? (bool) $payload['is_active'] : true,
                'metadata' => $payload['metadata'] ?? null,
                'row_version' => isset($payload['row_version']) ? (int) $payload['row_version'] : 1,
            ];

            return Result::success($this->currencies->create($attributes));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function requiredString(array $payload, string $field): string
    {
        if (! array_key_exists($field, $payload)) {
            throw new InvalidArgumentException(sprintf('Missing required field "%s".', $field));
        }

        $value = trim((string) $payload[$field]);
        if ($value === '') {
            throw new InvalidArgumentException(sprintf('Field "%s" must be a non-empty string.', $field));
        }

        return $value;
    }
}
