<?php
namespace Modules\Product\Domain\ValueObjects;

class ProductType
{
    public const PHYSICAL = 'physical';
    public const SERVICE  = 'service';
    public const DIGITAL  = 'digital';
    public const COMBO    = 'combo';
    public const VARIABLE = 'variable';

    private static array $valid = [self::PHYSICAL, self::SERVICE, self::DIGITAL, self::COMBO, self::VARIABLE];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid product type: {$v}");
        }
        return new self($v);
    }

    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
