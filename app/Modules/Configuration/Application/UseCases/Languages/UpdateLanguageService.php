<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases\Languages;

use Modules\Configuration\Application\Contracts\UseCases\Languages\UpdateLanguageServiceInterface;
use Modules\Configuration\Application\Repositories\LanguageRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use InvalidArgumentException;
use Throwable;

final class UpdateLanguageService implements UpdateLanguageServiceInterface
{
    public function __construct(private readonly LanguageRepositoryInterface $languages)
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

            $existing = $this->languages->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(ConfigurationErrorCode::NOT_FOUND, 'Language not found.'));
            }

            $attributes = [];
            if (array_key_exists('code', $payload)) {
                $attributes['code'] = $this->requiredString($payload, 'code');
            }
            if (array_key_exists('name', $payload)) {
                $attributes['name'] = $this->requiredString($payload, 'name');
            }
            if (array_key_exists('metadata', $payload)) {
                $attributes['metadata'] = $payload['metadata'];
            }
            if (array_key_exists('row_version', $payload)) {
                $attributes['row_version'] = (int) $payload['row_version'];
            }

            return Result::success($this->languages->update($id, $attributes));
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
