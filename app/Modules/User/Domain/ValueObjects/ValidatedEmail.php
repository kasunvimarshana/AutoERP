<?php

declare(strict_types=1);

namespace Modules\User\Domain\ValueObjects;

use Modules\Core\Domain\ValueObjects\ValueObject;

class ValidatedEmail extends ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address: {$value}");
        }

        if (! str_ends_with($value, '@company.com')) {
            throw new \InvalidArgumentException('Only company emails allowed');
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public static function fromArray(array $data): static
    {
        return new static($data['value']);
    }
}
