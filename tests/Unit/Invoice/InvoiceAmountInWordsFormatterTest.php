<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice;

use InvalidArgumentException;
use Modules\Invoice\Services\InvoiceAmountInWordsFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InvoiceAmountInWordsFormatterTest extends TestCase
{
    #[DataProvider('amounts')]
    public function test_it_formats_exact_invoice_totals(string $amount, ?string $currency, string $expected): void
    {
        self::assertSame($expected, (new InvoiceAmountInWordsFormatter())->format($amount, $currency));
    }

    /** @return iterable<string, array{string, ?string, string}> */
    public static function amounts(): iterable
    {
        yield 'zero' => ['0.000000', 'LKR', 'LKR Zero and 00/100 Only'];
        yield 'whole number' => ['95.000000', 'LKR', 'LKR Ninety-Five and 00/100 Only'];
        yield 'large decimal' => [
            '125450.750000',
            'LKR',
            'LKR One Hundred Twenty-Five Thousand Four Hundred Fifty and 75/100 Only',
        ];
        yield 'half up rounding' => ['1.235000', 'USD', 'USD One and 24/100 Only'];
        yield 'negative' => ['-12.345000', 'USD', 'USD Minus Twelve and 35/100 Only'];
        yield 'no currency' => ['10.010000', null, 'Ten and 01/100 Only'];
    }

    public function test_it_rejects_non_decimal_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invoice total must be a plain decimal value.');

        (new InvoiceAmountInWordsFormatter())->format('1e3', 'LKR');
    }
}
