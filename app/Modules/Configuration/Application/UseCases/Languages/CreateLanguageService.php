<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases\Languages;

use Modules\Configuration\Application\Contracts\UseCases\Languages\CreateLanguageServiceInterface;
use Modules\Configuration\Application\Repositories\LanguageRepositoryInterface;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use InvalidArgumentException;
use Throwable;

final class CreateLanguageService implements CreateLanguageServiceInterface
{
    public function __construct(private readonly LanguageRepositoryInterface $languages)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result
    {
        try {
            $attributes = [
                'code' => $this->requiredString($payload, 'code'),
                'name' => $this->requiredString($payload, 'name'),
                'metadata' => $payload['metadata'] ?? null,
                'row_version' => isset($payload['row_version']) ? (int) $payload['row_version'] : 1,
            ];

            return Result::success($this->languages->create($attributes));
        } catch (Throwable $exception) {
            return Result::failure(new Error(ConfigurationErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredString(array $payload, string $field): string
    {
        if (!array_key_exists($field, $payload)) {
            throw new InvalidArgumentException(sprintf('Missing required field "%s".', $field));
        }

        $value = trim((string) $payload[$field]);
        if ($value === '') {
            throw new InvalidArgumentException(sprintf('Field "%s" must be a non-empty string.', $field));
        }

        return $value;
    }
}
