<?php

declare(strict_types=1);

namespace Modules\Selling\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Selling\Enums\SaleDiscountType;

final class SaleDiscountService
{
    private const ZERO = '0.000000';

    private const PERCENT_BASE = '100';

    private const PRODUCT_SCALE = DecimalMath::SCALE * 2;

    public function __construct(private readonly DecimalMath $math) {}

    public function amount(SaleDiscountType $type, string $value, string $base): string
    {
        if ($this->math->isNegative($value)) {
            throw new InvalidArgumentException('Discount cannot be negative.');
        }
        if ($type === SaleDiscountType::Percentage && $this->math->compare($value, self::PERCENT_BASE) > 0) {
            throw new InvalidArgumentException('Discount percentage cannot exceed 100.');
        }
        $amount = $type === SaleDiscountType::Percentage
            ? $this->math->div($this->math->mul($base, $value, self::PRODUCT_SCALE), self::PERCENT_BASE)
            : $this->math->normalize($value);
        if ($this->math->compare($amount, $base) > 0) {
            throw new InvalidArgumentException('Discount cannot exceed the eligible amount.');
        }

        return $amount;
    }

    /** @param list<string> $lineBases
     * @return list<string>
     */
    public function allocate(string $amount, array $lineBases): array
    {
        $total = $this->math->sum($lineBases);
        if ($this->math->isZero($total)) {
            return array_fill(0, count($lineBases), self::ZERO);
        }

        $allocated = self::ZERO;
        $result = [];
        $last = count($lineBases) - 1;
        foreach ($lineBases as $index => $base) {
            $share = $index === $last
                ? $this->math->sub($amount, $allocated)
                : $this->math->div($this->math->mul($amount, $base, self::PRODUCT_SCALE), $total);
            $result[] = $share;
            $allocated = $this->math->add($allocated, $share);
        }

        return $result;
    }
}
