<?php

declare(strict_types=1);

namespace Modules\Sequence\Domain\Services;

use InvalidArgumentException;
use Modules\Sequence\Domain\Constants\SequencePeriodType;
use Modules\Sequence\Domain\Contracts\SequenceDomainServiceInterface;

final class SequenceDomainService implements SequenceDomainServiceInterface
{
    public function normalizeDocumentType(string $value): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            throw new InvalidArgumentException('Document type is required.');
        }

        return $normalized;
    }

    public function normalizePeriodType(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        if ($normalized === '') {
            return SequencePeriodType::YEARLY;
        }

        if (! SequencePeriodType::isValid($normalized)) {
            throw new InvalidArgumentException('Period type must be yearly, monthly, or infinite.');
        }

        return $normalized;
    }

    public function normalizePeriodValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    public function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    public function normalizeMetadata(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    public function normalizePadding(mixed $value): int
    {
        $padding = (int) $value;

        if ($padding < 1) {
            throw new InvalidArgumentException('Padding must be at least 1.');
        }

        return $padding;
    }

    public function normalizeNextNumber(mixed $value): int
    {
        $nextNumber = (int) $value;

        if ($nextNumber < 1) {
            throw new InvalidArgumentException('Next number must be at least 1.');
        }

        return $nextNumber;
    }
}
