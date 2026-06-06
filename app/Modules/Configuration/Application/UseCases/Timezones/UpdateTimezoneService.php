<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases\Timezones;

use InvalidArgumentException;
use Modules\Configuration\Application\Repositories\TimezoneRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class UpdateTimezoneService
{
    public function __construct(private readonly TimezoneRepositoryInterface $timezones) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($payload === []) {
                return Result::failure(new Error(ConfigurationErrorCode::INVALID_VALUE, 'At least one field must be provided for update.'));
            }

            $existing = $this->timezones->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(ConfigurationErrorCode::NOT_FOUND, 'Timezone not found.'));
            }

            $attributes = [];
            if (array_key_exists('name', $payload)) {
                $attributes['name'] = $this->requiredString($payload, 'name');
            }
            if (array_key_exists('offset', $payload)) {
                $attributes['offset'] = $this->requiredString($payload, 'offset');
            }
            if (array_key_exists('metadata', $payload)) {
                $attributes['metadata'] = $payload['metadata'];
            }
            if (array_key_exists('row_version', $payload)) {
                $attributes['row_version'] = (int) $payload['row_version'];
            }

            return Result::success($this->timezones->update($id, $attributes));
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
