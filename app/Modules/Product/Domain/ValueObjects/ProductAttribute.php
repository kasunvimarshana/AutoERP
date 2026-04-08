<?php

declare(strict_types=1);

namespace Modules\Product\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Defines an attribute type for variable products (e.g., Color with values Red/Blue/Green).
 */
class ProductAttribute
{
    private string $code;

    private string $name;

    /** @var string[] */
    private array $allowedValues;

    /**
     * @param  string[]  $allowedValues  Empty means open-ended (any value accepted).
     */
    public function __construct(string $code, string $name, array $allowedValues = [])
    {
        $code = strtolower(trim($code));
        $name = trim($name);

        if ($code === '') {
            throw new InvalidArgumentException('Attribute code cannot be empty.');
        }
        if ($name === '') {
            throw new InvalidArgumentException('Attribute name cannot be empty.');
        }

        $this->code          = $code;
        $this->name          = $name;
        $this->allowedValues = array_values(array_unique($allowedValues));
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return string[] */
    public function getAllowedValues(): array
    {
        return $this->allowedValues;
    }

    /**
     * Returns true when value is allowed, or always true when no allowed values are defined (open-ended).
     */
    public function isValueAllowed(string $value): bool
    {
        if (empty($this->allowedValues)) {
            return true;
        }

        return in_array($value, $this->allowedValues, true);
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function toArray(): array
    {
        return [
            'code'           => $this->code,
            'name'           => $this->name,
            'allowed_values' => $this->allowedValues,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['code'] ?? ''),
            (string) ($data['name'] ?? ''),
            (array)  ($data['allowed_values'] ?? []),
        );
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
