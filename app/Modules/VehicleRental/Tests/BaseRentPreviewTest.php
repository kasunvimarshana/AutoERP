<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BaseRentPolicy;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\BaseRentPreview;
use Modules\VehicleRental\Services\RentalAuthorization;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

final class BaseRentPreviewTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(RentalAuthorization::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
    }

    public static function periods(): iterable
    {
        yield 'leap month' => ['2024-02-01', '2024-02-01', '2024-02-29', 'monthly', '2900', '2900.000000', [29]];
        yield 'partial leap month' => ['2024-02-01', '2024-02-15', '2024-02-29', 'monthly', '2900', '1500.000000', [29]];
        yield 'partial normal February' => ['2025-02-01', '2025-02-15', '2025-02-28', 'monthly', '2800', '1400.000000', [28]];
        yield '31-day month' => ['2026-01-01', '2026-01-16', '2026-01-31', 'monthly', '3100', '1600.000000', [31]];
        yield 'anchor recovers after February' => ['2026-01-31', '2026-02-28', '2026-03-30', 'monthly', '3100', '3100.000000', [31]];
        yield 'two anchored cycles' => ['2026-01-31', '2026-01-31', '2026-03-30', 'monthly', '3100', '6200.000000', [28, 31]];
        yield 'TACGL E04 explicit daily rate' => ['2026-01-01', '2026-01-01', '2026-01-14', 'daily', '8000', '112000.000000', [1]];
        yield 'one civil day' => ['2026-01-01', '2026-01-01', '2026-01-01', 'daily', '7.125', '7.125000', [1]];
        yield 'explicit zero rate' => ['2026-01-01', '2026-01-01', '2026-01-31', 'monthly', '0', '0.000000', [31]];
    }

    #[DataProvider('periods')]
    public function test_actual_calendar_policy(string $anchor, string $from, string $until, string $basis, string $rate, string $total, array $denominators): void
    {
        [$context, $customer] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer, $anchor, $from, $until, $basis, $rate, $total, $denominators): void {
            $record = app(AgreementService::class)->create(AgreementKind::Customer, $context, array_replace($customer, ['starts_on' => $anchor, 'basis' => $basis, 'terms' => ['base_rate' => $rate]]));
            $result = app(BaseRentPreview::class)->calculate(AgreementKind::Customer, $context, $record->id, ['expected_version' => $record->row_version, 'policy' => BaseRentPolicy::ActualCalendarDays->value, 'from' => $from, 'until' => $until]);
            self::assertSame($total, $result['base_rent']);
            self::assertSame($denominators, array_column($result['segments'], 'denominator_days'));
            self::assertSame(1, $record->history()->count());
        });
    }

    public function test_adjacent_partial_periods_reconcile_without_rounding_leakage_and_sides_remain_independent(): void
    {
        [$context, $customer, $owner] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer, $owner): void {
            $service = app(AgreementService::class);
            $c = $service->create(AgreementKind::Customer, $context, array_replace($customer, ['starts_on' => '2026-01-01', 'terms' => ['base_rate' => '100']]));
            $o = $service->create(AgreementKind::Owner, $context, array_replace($owner, ['starts_on' => '2026-01-01', 'terms' => ['base_rate' => '60']]));
            $preview = app(BaseRentPreview::class);
            $input = ['expected_version' => 1, 'policy' => BaseRentPolicy::ActualCalendarDays->value, 'from' => '2026-01-01', 'until' => '2026-01-15'];
            $first = $preview->calculate(AgreementKind::Customer, $context, $c->id, $input);
            $second = $preview->calculate(AgreementKind::Customer, $context, $c->id, array_replace($input, ['from' => '2026-01-16', 'until' => '2026-01-31']));
            $ownerFull = $preview->calculate(AgreementKind::Owner, $context, $o->id, array_replace($input, ['until' => '2026-01-31']));
            self::assertSame('100.000000', app(DecimalMath::class)->add($first['base_rent'], $second['base_rent']));
            self::assertSame('60.000000', $ownerFull['base_rent']);
            self::assertSame($first, $preview->calculate(AgreementKind::Customer, $context, $c->id, $input));
            $this->assertDatabaseCount('invoices', 0);
        });
    }

    public static function invalidPeriods(): iterable
    {
        yield 'invalid civil date' => [['from' => '2026-09-31']];
        yield 'inverted' => [['until' => '2026-09-06']];
        yield 'outside coverage' => [['from' => '2026-09-06']];
        yield 'unsupported policy' => [['policy' => 'fixed_30']];
        yield 'missing policy' => [['policy' => null]];
        yield 'resource bound' => [['until' => '2036-09-07']];
    }

    #[DataProvider('invalidPeriods')]
    public function test_invalid_preview_is_rejected(array $overrides): void
    {
        [$context, $customer] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer, $overrides): void {
            $record = app(AgreementService::class)->create(AgreementKind::Customer, $context, array_replace($customer, ['terms' => ['base_rate' => '100']]));
            $this->expectException(ValidationException::class);
            app(BaseRentPreview::class)->calculate(AgreementKind::Customer, $context, $record->id, array_replace(['expected_version' => 1, 'policy' => BaseRentPolicy::ActualCalendarDays->value, 'from' => '2026-09-07', 'until' => '2026-09-30'], $overrides));
        });
    }

    public function test_missing_rate_is_not_zero(): void
    {
        [$context, $customer] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer): void {
            $record = app(AgreementService::class)->create(AgreementKind::Customer, $context, $customer);
            $this->expectException(ValidationException::class);
            app(BaseRentPreview::class)->calculate(AgreementKind::Customer, $context, $record->id, ['expected_version' => 1, 'policy' => BaseRentPolicy::ActualCalendarDays->value, 'from' => '2026-09-07', 'until' => '2026-09-30']);
        });
    }

    public function test_finite_agreement_end_is_inclusive_but_cannot_be_exceeded(): void
    {
        [$context, $customer] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer): void {
            $record = app(AgreementService::class)->create(AgreementKind::Customer, $context, array_replace($customer, ['basis' => 'daily', 'ends_on' => '2026-09-08', 'terms' => ['base_rate' => '100']]));
            $input = ['expected_version' => 1, 'policy' => BaseRentPolicy::ActualCalendarDays->value, 'from' => '2026-09-07', 'until' => '2026-09-08', 'base_rate' => '999999'];
            self::assertSame('200.000000', app(BaseRentPreview::class)->calculate(AgreementKind::Customer, $context, $record->id, $input)['base_rent']);
            $this->expectException(ValidationException::class);
            app(BaseRentPreview::class)->calculate(AgreementKind::Customer, $context, $record->id, array_replace($input, ['until' => '2026-09-09']));
        });
    }

    public function test_stale_agreement_cannot_produce_a_preview(): void
    {
        [$context, $customer] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer): void {
            $record = app(AgreementService::class)->create(AgreementKind::Customer, $context, $customer);
            $this->expectException(ConflictHttpException::class);
            app(BaseRentPreview::class)->calculate(AgreementKind::Customer, $context, $record->id, ['expected_version' => 2, 'policy' => BaseRentPolicy::ActualCalendarDays->value, 'from' => '2026-09-07', 'until' => '2026-09-30']);
        });
    }
}
