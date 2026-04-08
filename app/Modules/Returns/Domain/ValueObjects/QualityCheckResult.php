<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class QualityCheckResult extends ValueObject
{
    public const PASSED     = 'passed';
    public const FAILED     = 'failed';
    public const PENDING    = 'pending';
    public const QUARANTINE = 'quarantine';

    private const VALID = [
        self::PASSED,
        self::FAILED,
        self::PENDING,
        self::QUARANTINE,
    ];

    public function __construct(public readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException(
                "Invalid quality check result '{$value}'. Must be one of: " . implode(', ', self::VALID)
            );
        }
    }

    public static function passed(): self     { return new self(self::PASSED); }
    public static function failed(): self     { return new self(self::FAILED); }
    public static function pending(): self    { return new self(self::PENDING); }
    public static function quarantine(): self { return new self(self::QUARANTINE); }

    public function getValue(): string   { return $this->value; }
    public function isPassed(): bool     { return $this->value === self::PASSED; }
    public function isFailed(): bool     { return $this->value === self::FAILED; }
    public function isPending(): bool    { return $this->value === self::PENDING; }

    public function toArray(): array { return ['value' => $this->value]; }

    public static function fromArray(array $data): static { return new static($data['value']); }

    public function __toString(): string { return $this->value; }

    /** @return string[] */
    public static function values(): array { return self::VALID; }
}
