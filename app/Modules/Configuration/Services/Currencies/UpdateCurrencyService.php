<?php

declare(strict_types=1);

namespace Modules\Configuration\Services\Currencies;

use InvalidArgumentException;
use Modules\Configuration\Constants\ConfigurationErrorCode;
use Modules\Configuration\Repositories\CurrencyRepositoryInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Throwable;

final class UpdateCurrencyService
{
    public function __construct(private readonly CurrencyRepositoryInterface $currencies) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($payload === []) {
                return Result::failure(new Error(ConfigurationErrorCode::INVALID_VALUE, 'At least one field must be provided for update.'));
            }

            $existing = $this->currencies->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(ConfigurationErrorCode::NOT_FOUND, 'Currency not found.'));
            }

            $attributes = [];
            if (array_key_exists('code', $payload)) {
                $attributes['code'] = $this->requiredString($payload, 'code');
            }
            if (array_key_exists('name', $payload)) {
                $attributes['name'] = $this->requiredString($payload, 'name');
            }
            if (array_key_exists('symbol', $payload)) {
                $attributes['symbol'] = $payload['symbol'];
            }
            if (array_key_exists('decimal_places', $payload)) {
                $attributes['decimal_places'] = (int) $payload['decimal_places'];
            }
            if (array_key_exists('is_active', $payload)) {
                $attributes['is_active'] = (bool) $payload['is_active'];
            }
            if (array_key_exists('metadata', $payload)) {
                $attributes['metadata'] = $payload['metadata'];
            }
            if (array_key_exists('row_version', $payload)) {
                $attributes['row_version'] = (int) $payload['row_version'];
            }

            return Result::success($this->currencies->update($id, $attributes));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function requiredString(array $payload, string $field): string
    {
        $value = trim((string) ($payload[$field] ?? ''));
        if ($value === '') {
            throw new InvalidArgumentException(sprintf('Field "%s" must be a non-empty string.', $field));
        }

        return $value;
    }
}
