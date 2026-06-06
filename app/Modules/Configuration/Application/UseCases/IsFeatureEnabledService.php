<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\UseCases;

use Modules\Configuration\Application\DTOs\ConfigurationValueData;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class IsFeatureEnabledService
{
    public function __construct(
        private readonly ResolveConfigurationService $resolveConfiguration,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function execute(string $key, ?int $tenantId = null, bool $default = false): Result
    {
        try {
            $resolved = $this->resolveConfiguration->execute($key, $tenantId, null, $default);

            if ($resolved->isFailure()) {
                return Result::failure($resolved->errorOrFail());
            }

            $payload = $resolved->valueOrFail();
            if (! $payload instanceof ConfigurationValueData) {
                return Result::failure(new Error(
                    ConfigurationErrorCode::INVALID_RECORD,
                    'Feature flag resolver returned an invalid payload.',
                    ['key' => $key],
                ));
            }

            return Result::success([
                'key' => $payload->key,
                'enabled' => $this->toBool($payload->value),
                'tenant_id' => $payload->tenantId,
                'resolved_from' => $payload->resolvedFrom,
            ]);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                ConfigurationErrorCode::INVALID_VALUE,
                ['key' => $key, 'tenant_id' => $tenantId],
            ));
        }
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value !== 0.0;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
