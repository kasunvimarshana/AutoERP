<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases\Timezones;

use InvalidArgumentException;
use Modules\Configuration\Application\Repositories\TimezoneRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class CreateTimezoneService
{
    public function __construct(private readonly TimezoneRepositoryInterface $timezones) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): Result
    {
        try {
            $attributes = [
                'name' => $this->requiredString($payload, 'name'),
                'offset' => $this->requiredString($payload, 'offset'),
                'metadata' => $payload['metadata'] ?? null,
                'row_version' => isset($payload['row_version']) ? (int) $payload['row_version'] : 1,
            ];

            return Result::success($this->timezones->create($attributes));
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
