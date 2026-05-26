<?php

declare(strict_types=1);

namespace Modules\Sequence\Domain\Contracts;

interface SequenceDomainServiceInterface
{
    public function normalizeDocumentType(string $value): string;

    public function normalizePeriodType(?string $value): string;

    public function normalizePeriodValue(?string $value): ?string;

    public function normalizeOptionalText(?string $value): ?string;

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    public function normalizeMetadata(mixed $value): array;

    public function normalizePadding(mixed $value): int;

    public function normalizeNextNumber(mixed $value): int;

    public function resolvePeriodValue(string $periodType, ?string $periodValue, ?string $atDate = null): ?string;

    /**
     * @param array<string, scalar|null> $tokens
     */
    public function formatSequenceNumber(
        string $prefix,
        string $suffix,
        int $padding,
        int $number,
        array $tokens = [],
    ): string;
}
