<?php

namespace Tests\Unit\Tax;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Tax\Application\UseCases\CreateTaxRateUseCase;
use Modules\Tax\Application\UseCases\DeactivateTaxRateUseCase;
use Modules\Tax\Domain\Contracts\TaxRateRepositoryInterface;
use Modules\Tax\Domain\Events\TaxRateCreated;
use Modules\Tax\Domain\Events\TaxRateDeactivated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Tax module use cases.
 *
 * Covers tax rate creation with BCMath normalisation, negative rate guard,
 * deactivation lifecycle (already-inactive guard), and domain event assertions.
 */
class TaxUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTaxRate(bool $isActive = true): object
    {
        return (object) [
            'id'        => 'taxrate-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'Standard VAT',
            'type'      => 'percentage',
            'rate'      => '20.00000000',
            'is_active' => $isActive,
        ];
    }

    // -------------------------------------------------------------------------
    // CreateTaxRateUseCase
    // -------------------------------------------------------------------------

    public function test_create_tax_rate_normalises_rate_and_dispatches_event(): void
    {
        $taxRate     = $this->makeTaxRate();
        $taxRateRepo = Mockery::mock(TaxRateRepositoryInterface::class);

        $taxRateRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['rate'] === '20.00000000'
                    && $data['is_active'] === true
                    && $data['type'] === 'percentage';
            })
            ->andReturn($taxRate);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof TaxRateCreated
                && $event->name === 'Standard VAT'
                && $event->rate === '20.00000000');

        $useCase = new CreateTaxRateUseCase($taxRateRepo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'Standard VAT',
            'type'      => 'percentage',
            'rate'      => '20',
        ]);

        $this->assertTrue($result->is_active);
    }

    public function test_create_tax_rate_throws_when_rate_is_negative(): void
    {
        $taxRateRepo = Mockery::mock(TaxRateRepositoryInterface::class);
        $taxRateRepo->shouldNotReceive('create');

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CreateTaxRateUseCase($taxRateRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/zero or positive/i');

        $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'Invalid Tax',
            'type'      => 'percentage',
            'rate'      => '-5',
        ]);
    }

    public function test_create_fixed_tax_rate(): void
    {
        $taxRate = (object) [
            'id'        => 'taxrate-uuid-2',
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'Flat Fee',
            'type'      => 'fixed',
            'rate'      => '5.00000000',
            'is_active' => true,
        ];

        $taxRateRepo = Mockery::mock(TaxRateRepositoryInterface::class);

        $taxRateRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['type'] === 'fixed' && $data['rate'] === '5.00000000')
            ->andReturn($taxRate);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CreateTaxRateUseCase($taxRateRepo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'Flat Fee',
            'type'      => 'fixed',
            'rate'      => '5',
        ]);

        $this->assertSame('fixed', $result->type);
    }

    // -------------------------------------------------------------------------
    // DeactivateTaxRateUseCase
    // -------------------------------------------------------------------------

    public function test_deactivate_throws_when_tax_rate_not_found(): void
    {
        $taxRateRepo = Mockery::mock(TaxRateRepositoryInterface::class);
        $taxRateRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new DeactivateTaxRateUseCase($taxRateRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_deactivate_throws_when_already_inactive(): void
    {
        $taxRateRepo = Mockery::mock(TaxRateRepositoryInterface::class);
        $taxRateRepo->shouldReceive('findById')->andReturn($this->makeTaxRate(false));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new DeactivateTaxRateUseCase($taxRateRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already inactive/i');

        $useCase->execute('taxrate-uuid-1');
    }

    public function test_deactivate_sets_is_active_false_and_dispatches_event(): void
    {
        $taxRate     = $this->makeTaxRate(true);
        $deactivated = (object) array_merge((array) $taxRate, ['is_active' => false]);

        $taxRateRepo = Mockery::mock(TaxRateRepositoryInterface::class);
        $taxRateRepo->shouldReceive('findById')->andReturn($taxRate);
        $taxRateRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['is_active'] === false)
            ->once()
            ->andReturn($deactivated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof TaxRateDeactivated
                && $event->taxRateId === 'taxrate-uuid-1');

        $useCase = new DeactivateTaxRateUseCase($taxRateRepo);
        $result  = $useCase->execute('taxrate-uuid-1');

        $this->assertFalse($result->is_active);
    }
}
