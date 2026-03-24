<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

class ValidatedEmail extends Email
{
    public function __construct(string $value)
    {
        parent::__construct($value);
        // add custom validation, e.g., check domain
        if (! str_ends_with($value, '@company.com')) {
            throw new \InvalidArgumentException('Only company emails allowed');
        }
    }
}
