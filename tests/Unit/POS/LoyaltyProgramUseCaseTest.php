<?php

namespace Tests\Unit\POS;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\POS\Application\UseCases\AccrueLoyaltyPointsUseCase;
use Modules\POS\Application\UseCases\CreateLoyaltyProgramUseCase;
use Modules\POS\Application\UseCases\RedeemLoyaltyPointsUseCase;
use Modules\POS\Domain\Contracts\LoyaltyProgramRepositoryInterface;
use Modules\POS\Domain\Events\LoyaltyPointsAccrued;
use Modules\POS\Domain\Events\LoyaltyPointsRedeemed;
use Modules\POS\Domain\Events\LoyaltyProgramCreated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for POS Loyalty Program use cases.
 *
 * Covers:
 *  - CreateLoyaltyProgramUseCase: empty name guard, non-positive rate guards, successful creation
 *  - AccrueLoyaltyPointsUseCase: zero amount guard, inactive program guard,
 *    too-small amount guard, successful accrual (new card), successful accrual (existing card)
 *  - RedeemLoyaltyPointsUseCase: zero points guard, not-found card guard,
 *    inactive card guard, insufficient balance guard, successful redemption + discount
 */
class LoyaltyProgramUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // CreateLoyaltyProgramUseCase
    // -------------------------------------------------------------------------

    public function test_create_loyalty_program_throws_when_name_empty(): void
    {
        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);

        $useCase = new CreateLoyaltyProgramUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Loyalty program name must not be empty.');
        $useCase->execute(['tenant_id' => 't-1', 'name' => '']);
    }

    public function test_create_loyalty_program_throws_when_ppc_not_positive(): void
    {
        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);

        $useCase = new CreateLoyaltyProgramUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Points per currency unit must be greater than zero.');
        $useCase->execute([
            'tenant_id'                => 't-1',
            'name'                     => 'Test Program',
            'points_per_currency_unit' => '0',
            'redemption_rate'          => '100',
        ]);
    }

    public function test_create_loyalty_program_throws_when_redemption_rate_not_positive(): void
    {
        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);

        $useCase = new CreateLoyaltyProgramUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Redemption rate (points per discount unit) must be greater than zero.');
        $useCase->execute([
            'tenant_id'                => 't-1',
            'name'                     => 'Test Program',
            'points_per_currency_unit' => '1',
            'redemption_rate'          => '-1',
        ]);
    }

    public function test_create_loyalty_program_succeeds_and_dispatches_event(): void
    {
        $created = (object) [
            'id'        => 'prog-1',
            'tenant_id' => 't-1',
            'name'      => 'Gold Rewards',
            'is_active' => true,
        ];

        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($d) =>
                $d['name'] === 'Gold Rewards' &&
                $d['is_active'] === true &&
                $d['points_per_currency_unit'] === '1.00000000'
            ))
            ->andReturn($created);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(LoyaltyProgramCreated::class));

        $useCase = new CreateLoyaltyProgramUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id'                => 't-1',
            'name'                     => 'Gold Rewards',
            'points_per_currency_unit' => '1',
            'redemption_rate'          => '100',
        ]);

        $this->assertSame('prog-1', $result->id);
    }

    // -------------------------------------------------------------------------
    // AccrueLoyaltyPointsUseCase
    // -------------------------------------------------------------------------

    public function test_accrue_throws_when_order_amount_is_zero(): void
    {
        $repo    = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $useCase = new AccrueLoyaltyPointsUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Order amount must be greater than zero to accrue points.');
        $useCase->execute([
            'tenant_id'    => 't-1',
            'program_id'   => 'prog-1',
            'customer_id'  => 'cust-1',
            'order_amount' => '0',
        ]);
    }

    public function test_accrue_throws_when_program_not_found(): void
    {
        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('prog-x')->andReturn(null);

        $useCase = new AccrueLoyaltyPointsUseCase($repo);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Loyalty program not found.');
        $useCase->execute([
            'tenant_id'    => 't-1',
            'program_id'   => 'prog-x',
            'customer_id'  => 'cust-1',
            'order_amount' => '50',
        ]);
    }

    public function test_accrue_throws_when_program_inactive(): void
    {
        $program = (object) ['id' => 'prog-1', 'is_active' => false, 'points_per_currency_unit' => '1'];

        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('prog-1')->andReturn($program);

        $useCase = new AccrueLoyaltyPointsUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot accrue points for an inactive loyalty program.');
        $useCase->execute([
            'tenant_id'    => 't-1',
            'program_id'   => 'prog-1',
            'customer_id'  => 'cust-1',
            'order_amount' => '50',
        ]);
    }

    public function test_accrue_creates_new_card_and_dispatches_event(): void
    {
        $program = (object) [
            'id'                       => 'prog-1',
            'tenant_id'                => 't-1',
            'is_active'                => true,
            'points_per_currency_unit' => '2',
        ];

        $newCard     = (object) ['id' => 'card-1', 'points_balance' => '0', 'tenant_id' => 't-1', 'program_id' => 'prog-1'];
        $updatedCard = (object) ['id' => 'card-1', 'points_balance' => '200', 'tenant_id' => 't-1'];

        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('prog-1')->andReturn($program);
        $repo->shouldReceive('findCardByCustomer')->with('t-1', 'cust-1', 'prog-1')->andReturn(null);
        $repo->shouldReceive('createCard')
            ->once()
            ->with(Mockery::on(fn ($d) =>
                $d['customer_id'] === 'cust-1' &&
                $d['points_balance'] === '0'
            ))
            ->andReturn($newCard);
        $repo->shouldReceive('updateCard')
            ->once()
            ->with('card-1', ['points_balance' => '200'])
            ->andReturn($updatedCard);
        $repo->shouldReceive('createTransaction')->once();

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(LoyaltyPointsAccrued::class));

        $useCase = new AccrueLoyaltyPointsUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id'    => 't-1',
            'program_id'   => 'prog-1',
            'customer_id'  => 'cust-1',
            'order_amount' => '100',
        ]);

        $this->assertSame('200', $result->points_balance);
    }

    // -------------------------------------------------------------------------
    // RedeemLoyaltyPointsUseCase
    // -------------------------------------------------------------------------

    public function test_redeem_throws_when_points_zero(): void
    {
        $repo    = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $useCase = new RedeemLoyaltyPointsUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Points to redeem must be greater than zero.');
        $useCase->execute(['card_id' => 'card-1', 'points_to_redeem' => '0']);
    }

    public function test_redeem_throws_when_card_not_found(): void
    {
        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $repo->shouldReceive('findCardById')->with('missing')->andReturn(null);

        $useCase = new RedeemLoyaltyPointsUseCase($repo);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Loyalty card not found.');
        $useCase->execute(['card_id' => 'missing', 'points_to_redeem' => '50']);
    }

    public function test_redeem_throws_when_insufficient_balance(): void
    {
        $card = (object) [
            'id'             => 'card-1',
            'is_active'      => true,
            'points_balance' => '30',
            'program_id'     => 'prog-1',
            'tenant_id'      => 't-1',
            'customer_id'    => 'cust-1',
        ];

        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $repo->shouldReceive('findCardById')->with('card-1')->andReturn($card);

        $useCase = new RedeemLoyaltyPointsUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Insufficient points balance.');
        $useCase->execute(['card_id' => 'card-1', 'points_to_redeem' => '100']);
    }

    public function test_redeem_succeeds_and_returns_discount_amount(): void
    {
        $card = (object) [
            'id'             => 'card-1',
            'is_active'      => true,
            'points_balance' => '500',
            'program_id'     => 'prog-1',
            'tenant_id'      => 't-1',
            'customer_id'    => 'cust-1',
        ];

        $program = (object) [
            'id'              => 'prog-1',
            'is_active'       => true,
            'redemption_rate' => '100',
        ];

        $updatedCard = (object) array_merge((array) $card, ['points_balance' => '300']);

        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $repo->shouldReceive('findCardById')->with('card-1')->andReturn($card);
        $repo->shouldReceive('findById')->with('prog-1')->andReturn($program);
        $repo->shouldReceive('updateCard')
            ->once()
            ->with('card-1', ['points_balance' => '300'])
            ->andReturn($updatedCard);
        $repo->shouldReceive('createTransaction')->once();

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(LoyaltyPointsRedeemed::class));

        $useCase = new RedeemLoyaltyPointsUseCase($repo);
        $result  = $useCase->execute(['card_id' => 'card-1', 'points_to_redeem' => '200']);

        // 200 points ÷ 100 redemption_rate = 2 discount units
        $this->assertSame('2', $result['discount_amount']);
        $this->assertSame('300', $result['card']->points_balance);
    }
}
