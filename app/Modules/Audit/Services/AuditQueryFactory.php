<?php

declare(strict_types=1);

namespace Modules\Audit\Services;

use DateTimeImmutable;
use DateTimeZone;
use Modules\Audit\Data\AuditQueryData;

final class AuditQueryFactory
{
    /** @param array<string, mixed> $validated */
    public function fromValidated(array $validated): AuditQueryData
    {
        $timezone = new DateTimeZone((string) config('audit.display_timezone', 'UTC'));
        $from = $this->dateBoundary($validated['from_date'] ?? null, $timezone, false);
        $to = $this->dateBoundary($validated['to_date'] ?? null, $timezone, true);
        $max = max(1, (int) config('audit.pagination.max_per_page', 100));
        $default = min(max(1, (int) config('audit.pagination.default_per_page', 25)), $max);
        $requested = is_numeric($validated['per_page'] ?? null) ? (int) $validated['per_page'] : $default;

        return new AuditQueryData(
            $this->nullableString($validated['event_category'] ?? null),
            $this->nullableString($validated['event_name'] ?? null),
            $this->nullableString($validated['source_module'] ?? null),
            $this->nullableString($validated['actor_type'] ?? null),
            $this->nullableString($validated['actor_id'] ?? null),
            $this->nullableString($validated['subject_type'] ?? null),
            $this->nullableString($validated['subject_id'] ?? null),
            $from,
            $to,
            min(max($requested, 1), $max),
            $this->nullableString($validated['cursor'] ?? null),
        );
    }

    private function dateBoundary(mixed $value, DateTimeZone $timezone, bool $exclusiveEnd): ?DateTimeImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value), $timezone);
        if (! $date instanceof DateTimeImmutable) {
            return null;
        }

        if ($exclusiveEnd) {
            $date = $date->modify('+1 day');
        }

        return $date->setTimezone(new DateTimeZone('UTC'));
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
