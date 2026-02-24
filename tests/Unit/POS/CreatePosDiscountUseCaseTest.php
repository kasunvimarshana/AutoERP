<?php

namespace Tests\Unit\POS;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\POS\Application\UseCases\CreatePosDiscountUseCase;
use Modules\POS\Domain\Contracts\PosDiscountRepositoryInterface;
use Modules\POS\Domain\Events\PosDiscountCreated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreatePosDiscountUseCase.
 *
 * Covers:
 *  - Empty code guard
 *  - Empty name guard
 *  - Non-positive value guard
 *  - Percentage > 100 guard
 *  - Duplicate code guard
 *  - Successful creation (code uppercased, value BCMath-normalised, event dispatched)
 *  - Fixed-amount type allowed with value > 100
 */
class CreatePosDiscountUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeRepo(): PosDiscountRepositoryInterface
    {
        return Mockery::mock(PosDiscountRepositoryInterface::class);
    }

    public function test_throws_when_code_is_empty(): void
    {
        $repo = $this->makeRepo();
        $useCase = new CreatePosDiscountUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Discount code must not be empty.');
        $useCase->execute(['tenant_id' => 't-1', 'code' => '  ', 'name' => 'Summer Sale', 'type' => 'percentage', 'value' => '10']);
    }

    public function test_throws_when_name_is_empty(): void
    {
        $repo = $this->makeRepo();
        $repo->shouldReceive('findByCode')->andReturn(null);
        $useCase = new CreatePosDiscountUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Discount name must not be empty.');
        $useCase->execute(['tenant_id' => 't-1', 'code' => 'SUMMER10', 'name' => '', 'type' => 'percentage', 'value' => '10']);
    }

    public function test_throws_when_value_is_zero(): void
    {
        $repo = $this->makeRepo();
        $useCase = new CreatePosDiscountUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Discount value must be greater than zero.');
        $useCase->execute(['tenant_id' => 't-1', 'code' => 'ZERO', 'name' => 'Zero Off', 'type' => 'percentage', 'value' => '0']);
    }

    public function test_throws_when_percentage_exceeds_100(): void
    {
        $repo = $this->makeRepo();
        $useCase = new CreatePosDiscountUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Percentage discount cannot exceed 100.');
        $useCase->execute(['tenant_id' => 't-1', 'code' => 'OVER100', 'name' => 'Over Limit', 'type' => 'percentage', 'value' => '150']);
    }

    public function test_throws_when_code_already_exists(): void
    {
        $existing = (object) ['id' => 'disc-1', 'code' => 'SUMMER10'];

        $repo = $this->makeRepo();
        $repo->shouldReceive('findByCode')->with('t-1', 'SUMMER10')->andReturn($existing);
        $useCase = new CreatePosDiscountUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A discount with this code already exists.');
        $useCase->execute(['tenant_id' => 't-1', 'code' => 'SUMMER10', 'name' => 'Summer Sale', 'type' => 'percentage', 'value' => '10']);
    }

    public function test_creates_discount_uppercases_code_and_dispatches_event(): void
    {
        $created = (object) [
            'id'        => 'disc-uuid',
            'tenant_id' => 't-1',
            'code'      => 'SUMMER10',
            'name'      => 'Summer Sale',
            'type'      => 'percentage',
            'value'     => '10.00000000',
            'is_active' => true,
        ];

        $repo = $this->makeRepo();
        $repo->shouldReceive('findByCode')->with('t-1', 'SUMMER10')->andReturn(null);
        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($d) =>
                $d['code'] === 'SUMMER10' &&
                $d['name'] === 'Summer Sale' &&
                $d['type'] === 'percentage' &&
                $d['value'] === '10.00000000' &&
                $d['times_used'] === 0 &&
                $d['is_active'] === true
            ))
            ->andReturn($created);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(PosDiscountCreated::class));

        $useCase = new CreatePosDiscountUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id' => 't-1',
            'code'      => 'summer10',  // lowercase input — should be uppercased
            'name'      => 'Summer Sale',
            'type'      => 'percentage',
            'value'     => '10',
        ]);

        $this->assertSame('disc-uuid', $result->id);
        $this->assertSame('SUMMER10', $result->code);
    }

    public function test_fixed_amount_type_allows_value_above_100(): void
    {
        $created = (object) [
            'id'    => 'disc-2',
            'code'  => 'FIXEDOFF',
            'type'  => 'fixed_amount',
            'value' => '200.00000000',
        ];

        $repo = $this->makeRepo();
        $repo->shouldReceive('findByCode')->with('t-1', 'FIXEDOFF')->andReturn(null);
        $repo->shouldReceive('create')->once()->andReturn($created);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CreatePosDiscountUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id' => 't-1',
            'code'      => 'FIXEDOFF',
            'name'      => 'Fixed $200 Off',
            'type'      => 'fixed_amount',
            'value'     => '200',
        ]);

        $this->assertSame('disc-2', $result->id);
    }

    public function test_optional_fields_are_passed_correctly(): void
    {
        $created = (object) ['id' => 'disc-3', 'code' => 'LIMITEDRUN'];

        $repo = $this->makeRepo();
        $repo->shouldReceive('findByCode')->andReturn(null);
        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($d) =>
                $d['usage_limit'] === 50 &&
                $d['expires_at'] === '2026-12-31' &&
                $d['is_active'] === false &&
                $d['description'] === 'Limited run promo'
            ))
            ->andReturn($created);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CreatePosDiscountUseCase($repo);
        $result = $useCase->execute([
            'tenant_id'   => 't-1',
            'code'        => 'LIMITEDRUN',
            'name'        => 'Limited Run',
            'type'        => 'percentage',
            'value'       => '5',
            'usage_limit' => 50,
            'expires_at'  => '2026-12-31',
            'is_active'   => false,
            'description' => 'Limited run promo',
        ]);

        $this->assertSame('disc-3', $result->id);
    }
}
