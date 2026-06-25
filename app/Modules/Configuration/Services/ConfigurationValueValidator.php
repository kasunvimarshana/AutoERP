<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Illuminate\Validation\ValidationException;
use JsonException;
use Modules\Configuration\Constants\ConfigurationValueType;
use Modules\Configuration\Data\ConfigurationDefinition;
use Modules\ReferenceData\Contracts\ReferenceValueLookupInterface;

final class ConfigurationValueValidator
{
    private const MAX_TEXT_LENGTH = 10000;
    private const MAX_ENCODED_BYTES = 65535;

    public function __construct(
        private readonly ReferenceValueLookupInterface $referenceValues,
    ) {}

    public function validate(ConfigurationDefinition $definition, mixed $value): mixed
    {
        if ($value === null) {
            if (! $definition->nullable) {
                throw ValidationException::withMessages([
                    'value' => ['This setting cannot be empty.'],
                ]);
            }

            return null;
        }

        $normalized = match ($definition->valueType) {
            ConfigurationValueType::STRING => $this->string($value),
            ConfigurationValueType::INTEGER => $this->integer($value),
            ConfigurationValueType::DECIMAL => $this->decimal($value),
            ConfigurationValueType::BOOLEAN => $this->boolean($value),
            ConfigurationValueType::JSON => $this->json($value),
            default => throw ValidationException::withMessages([
                'value' => ['Unsupported setting value type.'],
            ]),
        };

        if (
            $definition->options !== []
            && ! in_array($normalized, $definition->options, true)
        ) {
            throw ValidationException::withMessages([
                'value' => [
                    'Select one of the allowed values: '
                    .implode(', ', array_map('strval', $definition->options))
                    .'.',
                ],
            ]);
        }

        if (is_int($normalized) || is_float($normalized)) {
            if (
                $definition->minimum !== null
                && $normalized < $definition->minimum
            ) {
                throw ValidationException::withMessages([
                    'value' => ["The value must be at least {$definition->minimum}."],
                ]);
            }

            if (
                $definition->maximum !== null
                && $normalized > $definition->maximum
            ) {
                throw ValidationException::withMessages([
                    'value' => ["The value may not exceed {$definition->maximum}."],
                ]);
            }
        }

        if ($definition->lookup !== null) {
            if (! is_string($normalized) && ! is_int($normalized)) {
                throw ValidationException::withMessages([
                    'value' => ['Select a value from the available list.'],
                ]);
            }

            if (! $this->referenceValues->activeValueExists(
                $definition->lookup,
                $normalized,
            )) {
                throw ValidationException::withMessages([
                    'value' => ['Select an active value from the available list.'],
                ]);
            }
        }

        try {
            $encoded = json_encode($normalized, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw ValidationException::withMessages([
                'value' => ['The setting value cannot be encoded safely.'],
            ]);
        }

        if (strlen($encoded) > self::MAX_ENCODED_BYTES) {
            throw ValidationException::withMessages([
                'value' => ['The setting value is too large.'],
            ]);
        }

        return $normalized;
    }

    private function string(mixed $value): string
    {
        if (! is_string($value)) {
            throw ValidationException::withMessages([
                'value' => ['Enter a text value.'],
            ]);
        }

        $value = trim($value);
        if ($value === '') {
            throw ValidationException::withMessages([
                'value' => ['Enter a value.'],
            ]);
        }
        if (mb_strlen($value) > self::MAX_TEXT_LENGTH) {
            throw ValidationException::withMessages([
                'value' => ['The text value is too long.'],
            ]);
        }

        return $value;
    }

    private function integer(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (! is_string($value) || preg_match('/^-?\d+$/', trim($value)) !== 1) {
            throw ValidationException::withMessages([
                'value' => ['Enter a whole number.'],
            ]);
        }

        $normalized = filter_var(trim($value), FILTER_VALIDATE_INT);
        if ($normalized === false) {
            throw ValidationException::withMessages([
                'value' => ['The whole number is outside the supported range.'],
            ]);
        }

        return $normalized;
    }

    private function decimal(mixed $value): float
    {
        $isNumericString = is_string($value) && is_numeric(trim($value));
        if (! is_int($value) && ! is_float($value) && ! $isNumericString) {
            throw ValidationException::withMessages([
                'value' => ['Enter a valid number.'],
            ]);
        }

        $normalized = (float) $value;
        if (! is_finite($normalized)) {
            throw ValidationException::withMessages([
                'value' => ['Enter a finite number.'],
            ]);
        }

        return $normalized;
    }

    private function boolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (
            $value === 1
            || $value === '1'
            || (is_string($value) && strtolower(trim($value)) === 'true')
        ) {
            return true;
        }
        if (
            $value === 0
            || $value === '0'
            || (is_string($value) && strtolower(trim($value)) === 'false')
        ) {
            return false;
        }

        throw ValidationException::withMessages([
            'value' => ['Select enabled or disabled.'],
        ]);
    }

    /** @return array<mixed> */
    private function json(mixed $value): array
    {
        if (! is_array($value)) {
            throw ValidationException::withMessages([
                'value' => ['Enter a JSON object or array.'],
            ]);
        }

        return $value;
    }
}
