<?php

namespace Tests\Unit\Currency;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Currency\Application\UseCases\ConvertAmountUseCase;
use Modules\Currency\Application\UseCases\CreateCurrencyUseCase;
use Modules\Currency\Application\UseCases\DeactivateCurrencyUseCase;
use Modules\Currency\Application\UseCases\RecordExchangeRateUseCase;
use Modules\Currency\Domain\Contracts\CurrencyRepositoryInterface;
use Modules\Currency\Domain\Contracts\ExchangeRateRepositoryInterface;
use Modules\Currency\Domain\Events\CurrencyCreated;
use Modules\Currency\Domain\Events\CurrencyDeactivated;
use Modules\Currency\Domain\Events\ExchangeRateRecorded;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Currency module use cases.
 *
 * Covers ISO-code validation, duplicate guard, BCMath rate normalisation,
 * deactivation lifecycle, exchange rate recording guards, and amount conversion.
 */
class CurrencyUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCurrency(bool $isActive = true): object
    {
        return (object) [
            'id'             => 'currency-uuid-1',
            'tenant_id'      => 'tenant-uuid-1',
            'code'           => 'USD',
            'name'           => 'US Dollar',
            'symbol'         => '$',
            'decimal_places' => 2,
            'is_active'      => $isActive,
        ];
    }

    private function makeExchangeRate(string $rate = '1.25000000'): object
    {
        return (object) [
            'id'                 => 'rate-uuid-1',
            'tenant_id'          => 'tenant-uuid-1',
            'from_currency_code' => 'USD',
            'to_currency_code'   => 'EUR',
            'rate'               => $rate,
            'source'             => 'manual',
            'effective_date'     => '2024-01-01',
        ];
    }

    // -------------------------------------------------------------------------
    // CreateCurrencyUseCase
    // -------------------------------------------------------------------------

    public function test_create_currency_succeeds_and_dispatches_event(): void
    {
        $currency     = $this->makeCurrency();
        $currencyRepo = Mockery::mock(CurrencyRepositoryInterface::class);

        $currencyRepo->shouldReceive('findByCode')->once()->andReturn(null);
        $currencyRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['code'] === 'USD'
                    && $data['is_active'] === true
                    && $data['decimal_places'] === 2;
            })
            ->andReturn($currency);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof CurrencyCreated
                && $event->code === 'USD'
                && $event->tenantId === 'tenant-uuid-1');

        $useCase = new CreateCurrencyUseCase($currencyRepo);
        $result  = $useCase->execute([
            'tenant_id'      => 'tenant-uuid-1',
            'code'           => 'usd',
            'name'           => 'US Dollar',
            'symbol'         => '$',
            'decimal_places' => 2,
        ]);

        $this->assertSame('USD', $result->code);
        $this->assertTrue($result->is_active);
    }

    public function test_create_currency_throws_when_code_not_three_chars(): void
    {
        $currencyRepo = Mockery::mock(CurrencyRepositoryInterface::class);
        $currencyRepo->shouldNotReceive('create');

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CreateCurrencyUseCase($currencyRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/exactly 3 characters/i');

        $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'code'      => 'US',
            'name'      => 'US Dollar',
        ]);
    }

    public function test_create_currency_throws_when_decimal_places_out_of_range(): void
    {
        $currencyRepo = Mockery::mock(CurrencyRepositoryInterface::class);
        $currencyRepo->shouldNotReceive('create');

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CreateCurrencyUseCase($currencyRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/between 0 and 8/i');

        $useCase->execute([
            'tenant_id'      => 'tenant-uuid-1',
            'code'           => 'USD',
            'name'           => 'US Dollar',
            'decimal_places' => 9,
        ]);
    }

    public function test_create_currency_throws_when_duplicate_code(): void
    {
        $currencyRepo = Mockery::mock(CurrencyRepositoryInterface::class);
        $currencyRepo->shouldReceive('findByCode')->andReturn($this->makeCurrency());
        $currencyRepo->shouldNotReceive('create');

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CreateCurrencyUseCase($currencyRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already exists/i');

        $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'code'      => 'USD',
            'name'      => 'US Dollar',
        ]);
    }

    public function test_create_currency_uppercases_code(): void
    {
        $currency     = $this->makeCurrency();
        $currencyRepo = Mockery::mock(CurrencyRepositoryInterface::class);

        $currencyRepo->shouldReceive('findByCode')
            ->with('tenant-uuid-1', 'EUR')
            ->once()
            ->andReturn(null);
        $currencyRepo->shouldReceive('create')
            ->withArgs(fn ($data) => $data['code'] === 'EUR')
            ->andReturn((object) array_merge((array) $currency, ['code' => 'EUR']));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CreateCurrencyUseCase($currencyRepo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'code'      => 'eur',
            'name'      => 'Euro',
        ]);

        $this->assertSame('EUR', $result->code);
    }

    // -------------------------------------------------------------------------
    // DeactivateCurrencyUseCase
    // -------------------------------------------------------------------------

    public function test_deactivate_throws_when_not_found(): void
    {
        $currencyRepo = Mockery::mock(CurrencyRepositoryInterface::class);
        $currencyRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new DeactivateCurrencyUseCase($currencyRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_deactivate_throws_when_already_inactive(): void
    {
        $currencyRepo = Mockery::mock(CurrencyRepositoryInterface::class);
        $currencyRepo->shouldReceive('findById')->andReturn($this->makeCurrency(false));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new DeactivateCurrencyUseCase($currencyRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already inactive/i');

        $useCase->execute('currency-uuid-1');
    }

    public function test_deactivate_sets_is_active_false_and_dispatches_event(): void
    {
        $currency    = $this->makeCurrency(true);
        $deactivated = (object) array_merge((array) $currency, ['is_active' => false]);

        $currencyRepo = Mockery::mock(CurrencyRepositoryInterface::class);
        $currencyRepo->shouldReceive('findById')->andReturn($currency);
        $currencyRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['is_active'] === false)
            ->once()
            ->andReturn($deactivated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof CurrencyDeactivated
                && $event->currencyId === 'currency-uuid-1'
                && $event->code === 'USD');

        $useCase = new DeactivateCurrencyUseCase($currencyRepo);
        $result  = $useCase->execute('currency-uuid-1');

        $this->assertFalse($result->is_active);
    }

    // -------------------------------------------------------------------------
    // RecordExchangeRateUseCase
    // -------------------------------------------------------------------------

    public function test_record_exchange_rate_succeeds_and_dispatches_event(): void
    {
        $exchangeRate    = $this->makeExchangeRate('1.25000000');
        $currencyRepo    = Mockery::mock(CurrencyRepositoryInterface::class);
        $exchangeRateRepo = Mockery::mock(ExchangeRateRepositoryInterface::class);

        $currencyRepo->shouldReceive('findByCode')->with('tenant-uuid-1', 'USD')->andReturn($this->makeCurrency());
        $currencyRepo->shouldReceive('findByCode')->with('tenant-uuid-1', 'EUR')->andReturn($this->makeCurrency());

        $exchangeRateRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['rate'] === '1.25000000'
                && $data['from_currency_code'] === 'USD'
                && $data['to_currency_code'] === 'EUR')
            ->andReturn($exchangeRate);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ExchangeRateRecorded
                && $event->rate === '1.25000000');

        $useCase = new RecordExchangeRateUseCase($currencyRepo, $exchangeRateRepo);
        $result  = $useCase->execute([
            'tenant_id'          => 'tenant-uuid-1',
            'from_currency_code' => 'USD',
            'to_currency_code'   => 'EUR',
            'rate'               => '1.25',
        ]);

        $this->assertSame('1.25000000', $result->rate);
    }

    public function test_record_exchange_rate_throws_when_same_currency(): void
    {
        $currencyRepo     = Mockery::mock(CurrencyRepositoryInterface::class);
        $exchangeRateRepo = Mockery::mock(ExchangeRateRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordExchangeRateUseCase($currencyRepo, $exchangeRateRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/must be different/i');

        $useCase->execute([
            'tenant_id'          => 'tenant-uuid-1',
            'from_currency_code' => 'USD',
            'to_currency_code'   => 'USD',
            'rate'               => '1',
        ]);
    }

    public function test_record_exchange_rate_throws_when_rate_zero(): void
    {
        $currencyRepo     = Mockery::mock(CurrencyRepositoryInterface::class);
        $exchangeRateRepo = Mockery::mock(ExchangeRateRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordExchangeRateUseCase($currencyRepo, $exchangeRateRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/greater than zero/i');

        $useCase->execute([
            'tenant_id'          => 'tenant-uuid-1',
            'from_currency_code' => 'USD',
            'to_currency_code'   => 'EUR',
            'rate'               => '0',
        ]);
    }

    public function test_record_exchange_rate_throws_when_from_currency_not_found(): void
    {
        $currencyRepo     = Mockery::mock(CurrencyRepositoryInterface::class);
        $exchangeRateRepo = Mockery::mock(ExchangeRateRepositoryInterface::class);

        $currencyRepo->shouldReceive('findByCode')->with('tenant-uuid-1', 'USD')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordExchangeRateUseCase($currencyRepo, $exchangeRateRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute([
            'tenant_id'          => 'tenant-uuid-1',
            'from_currency_code' => 'USD',
            'to_currency_code'   => 'EUR',
            'rate'               => '1.25',
        ]);
    }

    // -------------------------------------------------------------------------
    // ConvertAmountUseCase
    // -------------------------------------------------------------------------

    public function test_convert_amount_returns_same_for_same_currency(): void
    {
        $exchangeRateRepo = Mockery::mock(ExchangeRateRepositoryInterface::class);
        $exchangeRateRepo->shouldNotReceive('findLatest');

        $useCase = new ConvertAmountUseCase($exchangeRateRepo);
        $result  = $useCase->execute('tenant-uuid-1', 'USD', 'USD', '100.00');

        $this->assertSame('100.00000000', $result);
    }

    public function test_convert_amount_multiplies_by_exchange_rate(): void
    {
        $exchangeRateRepo = Mockery::mock(ExchangeRateRepositoryInterface::class);
        $exchangeRateRepo->shouldReceive('findLatest')
            ->with('tenant-uuid-1', 'USD', 'EUR')
            ->andReturn($this->makeExchangeRate('1.25000000'));

        $useCase = new ConvertAmountUseCase($exchangeRateRepo);
        $result  = $useCase->execute('tenant-uuid-1', 'USD', 'EUR', '100.00');

        $this->assertSame('125.00000000', $result);
    }

    public function test_convert_amount_throws_when_no_exchange_rate_found(): void
    {
        $exchangeRateRepo = Mockery::mock(ExchangeRateRepositoryInterface::class);
        $exchangeRateRepo->shouldReceive('findLatest')->andReturn(null);

        $useCase = new ConvertAmountUseCase($exchangeRateRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/no exchange rate found/i');

        $useCase->execute('tenant-uuid-1', 'USD', 'GBP', '50.00');
    }
}
