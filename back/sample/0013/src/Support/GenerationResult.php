<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Support;

/**
 * GenerationResult — Immutable result of a file generation operation.
 */
final class GenerationResult
{
    public const STATUS_CREATED  = 'created';
    public const STATUS_SKIPPED  = 'skipped';
    public const STATUS_DRY_RUN  = 'dry_run';

    private function __construct(
        public readonly string $status,
        public readonly string $path,
    ) {}

    public static function created(string $path): self
    {
        return new self(self::STATUS_CREATED, $path);
    }

    public static function skipped(string $path): self
    {
        return new self(self::STATUS_SKIPPED, $path);
    }

    public static function dryRun(string $path): self
    {
        return new self(self::STATUS_DRY_RUN, $path);
    }

    public function wasCreated(): bool
    {
        return $this->status === self::STATUS_CREATED;
    }

    public function wasSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    public function isDryRun(): bool
    {
        return $this->status === self::STATUS_DRY_RUN;
    }
}
