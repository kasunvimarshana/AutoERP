<?php

declare(strict_types=1);

namespace Modules\Core\Results;

final readonly class Error
{
    /**
     * @param  array<string, scalar|array|null>  $context
     */
    public function __construct(
        public string $code,
        public string $message,
        public array $context = [],
    ) {}
}
