<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases\Countries;

use InvalidArgumentException;
use Modules\Configuration\Application\Repositories\CountryRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class CreateCountryService
{
    public function __construct(private readonly CountryRepositoryInterface $countries) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): Result
    {
        try {
            $attributes = [
                'code' => $this->requiredString($payload, 'code'),
                'name' => $this->requiredString($payload, 'name'),
                'phone_code' => array_key_exists('phone_code', $payload) ? $payload['phone_code'] : null,
                'metadata' => $payload['metadata'] ?? null,
                'row_version' => isset($payload['row_version']) ? (int) $payload['row_version'] : 1,
            ];

            return Result::success($this->countries->create($attributes));
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
