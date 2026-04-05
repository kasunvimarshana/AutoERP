<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Modules\Currency\Application\Services\ConvertAmountService;
use Modules\Currency\Application\Services\ManageCurrencyService;
use Modules\Currency\Domain\Entities\Currency;
use Modules\Currency\Domain\Entities\ExchangeRate;
use Modules\Currency\Domain\Exceptions\CurrencyNotFoundException;
use Modules\Currency\Domain\Exceptions\ExchangeRateNotFoundException;
use Modules\Currency\Domain\RepositoryInterfaces\CurrencyRepositoryInterface;
use Modules\Currency\Domain\RepositoryInterfaces\ExchangeRateRepositoryInterface;

class CurrencyModuleTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────
    // Factory helpers
    // ──────────────────────────────────────────────────────────────────────

    private function makeCurrency(
        string $code = 'USD',
        bool $isBase = false,
        bool $isActive = true
    ): Currency {
        return new Currency(null, $code, 'US Dollar', '$', 2, $isBase, $isActive, null, null);
    }

    private function makeExchangeRate(
        string $from = 'USD',
        string $to = 'EUR',
        float $rate = 0.9
    ): ExchangeRate {
        return new ExchangeRate(1, 1, $from, $to, $rate, 'manual', null, null, null, null);
    }

    private function mockCurrencyRepo(): MockObject&CurrencyRepositoryInterface
    {
        return $this->createMock(CurrencyRepositoryInterface::class);
    }

    private function mockRateRepo(): MockObject&ExchangeRateRepositoryInterface
    {
        return $this->createMock(ExchangeRateRepositoryInterface::class);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Currency entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_currency_creation(): void
    {
        $c = $this->makeCurrency('USD', true);
        $this->assertEquals('USD', $c->getCode());
        $this->assertEquals('US Dollar', $c->getName());
        $this->assertEquals('$', $c->getSymbol());
        $this->assertEquals(2, $c->getDecimalPlaces());
        $this->assertTrue($c->isBase());
        $this->assertTrue($c->isActive());
    }

    public function test_currency_activate_deactivate(): void
    {
        $c = $this->makeCurrency('USD', false, false);
        $this->assertFalse($c->isActive());
        $c->activate();
        $this->assertTrue($c->isActive());
        $c->deactivate();
        $this->assertFalse($c->isActive());
    }

    public function test_currency_format(): void
    {
        $c = $this->makeCurrency('USD');
        $formatted = $c->format(1234.5);
        $this->assertStringContainsString('$', $formatted);
        $this->assertStringContainsString('1,234.50', $formatted);
    }

    public function test_currency_zero_decimal_places(): void
    {
        $jpy = new Currency(null, 'JPY', 'Japanese Yen', '¥', 0, false, true, null, null);
        $this->assertEquals(0, $jpy->getDecimalPlaces());
        $this->assertStringContainsString('¥', $jpy->format(1000.0));
    }

    // ──────────────────────────────────────────────────────────────────────
    // ExchangeRate entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_exchange_rate_creation(): void
    {
        $rate = $this->makeExchangeRate('USD', 'EUR', 0.9);
        $this->assertEquals('USD', $rate->getFromCurrency());
        $this->assertEquals('EUR', $rate->getToCurrency());
        $this->assertEquals(0.9, $rate->getRate());
        $this->assertEquals('manual', $rate->getSource());
    }

    public function test_exchange_rate_convert(): void
    {
        $rate = $this->makeExchangeRate('USD', 'EUR', 0.9);
        $this->assertEqualsWithDelta(90.0, $rate->convert(100.0), 0.0001);
    }

    public function test_exchange_rate_inverted(): void
    {
        $rate = $this->makeExchangeRate('USD', 'EUR', 2.0);
        $this->assertEqualsWithDelta(0.5, $rate->invertedRate(), 0.0001);
    }

    public function test_exchange_rate_zero_rate_invert_throws(): void
    {
        $rate = new ExchangeRate(1, 1, 'USD', 'EUR', 0.0, 'manual', null, null, null, null);
        $this->expectException(\DomainException::class);
        $rate->invertedRate();
    }

    public function test_exchange_rate_valid_at_no_bounds(): void
    {
        $rate = $this->makeExchangeRate();
        $this->assertTrue($rate->isValidAt(new \DateTimeImmutable()));
    }

    public function test_exchange_rate_valid_at_within_bounds(): void
    {
        $rate = new ExchangeRate(
            1, 1, 'USD', 'EUR', 0.9, 'manual',
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable('2026-12-31'),
            null, null,
        );
        $this->assertTrue($rate->isValidAt(new \DateTimeImmutable('2026-06-01')));
        $this->assertFalse($rate->isValidAt(new \DateTimeImmutable('2025-12-31')));
        $this->assertFalse($rate->isValidAt(new \DateTimeImmutable('2027-01-01')));
    }

    // ──────────────────────────────────────────────────────────────────────
    // ManageCurrencyService tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_manage_currency_find_by_code(): void
    {
        $repo = $this->mockCurrencyRepo();
        $repo->method('findByCode')->with('USD')->willReturn($this->makeCurrency('USD'));
        $result = (new ManageCurrencyService($repo))->findByCode('USD');
        $this->assertEquals('USD', $result->getCode());
    }

    public function test_manage_currency_not_found_throws(): void
    {
        $repo = $this->mockCurrencyRepo();
        $repo->method('findByCode')->willReturn(null);
        $this->expectException(CurrencyNotFoundException::class);
        (new ManageCurrencyService($repo))->findByCode('XYZ');
    }

    public function test_manage_currency_find_all(): void
    {
        $repo = $this->mockCurrencyRepo();
        $repo->method('findAll')->willReturn([
            $this->makeCurrency('USD'),
            $this->makeCurrency('EUR'),
        ]);
        $results = (new ManageCurrencyService($repo))->findAll();
        $this->assertCount(2, $results);
    }

    public function test_manage_currency_base_currency(): void
    {
        $repo = $this->mockCurrencyRepo();
        $repo->method('findBaseCurrency')->willReturn($this->makeCurrency('USD', true));
        $base = (new ManageCurrencyService($repo))->findBaseCurrency();
        $this->assertTrue($base->isBase());
    }

    public function test_manage_currency_base_not_found_throws(): void
    {
        $repo = $this->mockCurrencyRepo();
        $repo->method('findBaseCurrency')->willReturn(null);
        $this->expectException(CurrencyNotFoundException::class);
        (new ManageCurrencyService($repo))->findBaseCurrency();
    }

    public function test_manage_currency_create(): void
    {
        $repo = $this->mockCurrencyRepo();
        $repo->method('create')->willReturn($this->makeCurrency('GBP'));
        $result = (new ManageCurrencyService($repo))->create(['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2]);
        $this->assertEquals('GBP', $result->getCode());
    }

    // ──────────────────────────────────────────────────────────────────────
    // ConvertAmountService tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_convert_same_currency_returns_original(): void
    {
        $repo = $this->mockRateRepo();
        $service = new ConvertAmountService($repo);
        $result = $service->convert(1, 100.0, 'USD', 'USD');
        $this->assertEqualsWithDelta(100.0, $result, 0.0001);
    }

    public function test_convert_direct_rate(): void
    {
        $repo = $this->mockRateRepo();
        $rate = $this->makeExchangeRate('USD', 'EUR', 0.9);
        $repo->method('findActive')
            ->willReturnCallback(function ($tid, $from, $to) use ($rate) {
                return ($from === 'USD' && $to === 'EUR') ? $rate : null;
            });

        $service = new ConvertAmountService($repo);
        $result  = $service->convert(1, 100.0, 'USD', 'EUR');
        $this->assertEqualsWithDelta(90.0, $result, 0.0001);
    }

    public function test_convert_inverse_rate(): void
    {
        $repo = $this->mockRateRepo();
        // No direct USD->EUR but EUR->USD at rate 1.1
        $inverseRate = $this->makeExchangeRate('EUR', 'USD', 1.1);
        $repo->method('findActive')
            ->willReturnCallback(function ($tid, $from, $to) use ($inverseRate) {
                return ($from === 'EUR' && $to === 'USD') ? $inverseRate : null;
            });

        $service = new ConvertAmountService($repo);
        // 100 USD ÷ 1.1 ≈ 90.909
        $result = $service->convert(1, 100.0, 'USD', 'EUR');
        $this->assertEqualsWithDelta(100.0 / 1.1, $result, 0.001);
    }

    public function test_convert_no_rate_throws(): void
    {
        $repo = $this->mockRateRepo();
        $repo->method('findActive')->willReturn(null);

        $this->expectException(ExchangeRateNotFoundException::class);
        (new ConvertAmountService($repo))->convert(1, 100.0, 'USD', 'JPY');
    }

    public function test_currency_not_found_exception_message(): void
    {
        $ex = new CurrencyNotFoundException('XYZ');
        $this->assertStringContainsString('XYZ', $ex->getMessage());
    }

    public function test_exchange_rate_not_found_exception_message(): void
    {
        $ex = new ExchangeRateNotFoundException('USD', 'JPY');
        $this->assertStringContainsString('USD', $ex->getMessage());
        $this->assertStringContainsString('JPY', $ex->getMessage());
    }
}
