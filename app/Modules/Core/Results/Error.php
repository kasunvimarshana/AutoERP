<?php

declare(strict_types=1);

namespace Modules\Core\Results;

use InvalidArgumentException;

final readonly class Error
{
    /**
     * @param  array<string, scalar|array|null>  $context
     */
    public function __construct(
        public string $code,
        public string $message,
        public array $context = [],
    ) {
        if (trim($this->code) === '') {
            throw new InvalidArgumentException('Error code cannot be empty.');
        }

        if (trim($this->message) === '') {
            throw new InvalidArgumentException('Error message cannot be empty.');
        }

        foreach (array_keys($this->context) as $key) {
            if (! is_string($key) || trim($key) === '') {
                throw new InvalidArgumentException('Error context keys must be non-empty strings.');
            }
        }
    }
}
