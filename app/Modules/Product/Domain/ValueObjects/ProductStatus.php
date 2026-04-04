<?php
namespace Modules\Product\Domain\ValueObjects;

class ProductStatus
{
    public const ACTIVE       = 'active';
    public const INACTIVE     = 'inactive';
    public const DISCONTINUED = 'discontinued';
    public const DRAFT        = 'draft';

    private static array $valid = [self::ACTIVE, self::INACTIVE, self::DISCONTINUED, self::DRAFT];

    private function __construct(public readonly string $value) {}

    public static function from(string $v): self
    {
        if (!in_array($v, self::$valid, true)) {
            throw new \InvalidArgumentException("Invalid product status: {$v}");
        }
        return new self($v);
    }

    public static function valid(): array { return self::$valid; }
    public function __toString(): string { return $this->value; }
}
