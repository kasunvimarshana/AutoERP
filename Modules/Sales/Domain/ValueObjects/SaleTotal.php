<?php
declare(strict_types=1);
namespace Modules\Sales\Domain\ValueObjects;
final class SaleTotal {
    private const SCALE = 4;
    private function __construct(
        public readonly string $subtotal,
        public readonly string $discountAmount,
        public readonly string $taxAmount,
        public readonly string $total,
    ) {}
    public static function calculate(array $lines, string $discountPercent = '0', string $taxPercent = '0'): static {
        $subtotal = '0.0000';
        foreach ($lines as $line) {
            $subtotal = bcadd($subtotal, (string)($line['line_total'] ?? '0'), self::SCALE);
        }
        $discountAmount = bcdiv(bcmul($subtotal, $discountPercent, self::SCALE), '100', self::SCALE);
        $afterDiscount  = bcsub($subtotal, $discountAmount, self::SCALE);
        $taxAmount      = bcdiv(bcmul($afterDiscount, $taxPercent, self::SCALE), '100', self::SCALE);
        $total          = bcadd($afterDiscount, $taxAmount, self::SCALE);
        return new static(
            subtotal: $subtotal,
            discountAmount: $discountAmount,
            taxAmount: $taxAmount,
            total: $total,
        );
    }
}
