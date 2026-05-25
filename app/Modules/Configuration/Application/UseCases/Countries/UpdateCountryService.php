<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases\Countries;

use Modules\Configuration\Application\Contracts\UseCases\Countries\UpdateCountryServiceInterface;
use Modules\Configuration\Application\Repositories\CountryRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use InvalidArgumentException;
use Throwable;

final class UpdateCountryService implements UpdateCountryServiceInterface
{
    public function __construct(private readonly CountryRepositoryInterface $countries)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($payload === []) {
                return Result::failure(new Error(ConfigurationErrorCode::INVALID_VALUE, 'At least one field must be provided for update.'));
            }

            $existing = $this->countries->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(ConfigurationErrorCode::NOT_FOUND, 'Country not found.'));
            }

            $attributes = [];
            if (array_key_exists('code', $payload)) {
                $attributes['code'] = $this->requiredString($payload, 'code');
            }
            if (array_key_exists('name', $payload)) {
                $attributes['name'] = $this->requiredString($payload, 'name');
            }
            if (array_key_exists('phone_code', $payload)) {
                $attributes['phone_code'] = $payload['phone_code'];
            }
            if (array_key_exists('metadata', $payload)) {
                $attributes['metadata'] = $payload['metadata'];
            }
            if (array_key_exists('row_version', $payload)) {
                $attributes['row_version'] = (int) $payload['row_version'];
            }

            return Result::success($this->countries->update($id, $attributes));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $payload
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
