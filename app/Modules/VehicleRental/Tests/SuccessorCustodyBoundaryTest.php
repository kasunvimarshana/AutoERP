<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\VehicleRental\Constants\RentalConfiguration;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\VehicleUseService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SuccessorCustodyBoundaryTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    public static function boundaries(): array
    {
        return [
            'UTC end crosses Colombo cutover' => ['2026-09-07T23:00:00+00:00', null, false, false],
            'positive offset before Colombo cutover' => ['2026-09-08T01:00:00+10:00', null, false, true],
            'exact Colombo boundary' => ['2026-09-07T18:30:00+00:00', null, false, true],
            'custody remains open after planned end' => ['2026-09-07T18:00:00+00:00', null, true, false],
            'late actual return crosses boundary' => ['2026-09-07T18:00:00+00:00', '2026-09-07T19:00:00+00:00', true, false],
            'early actual return before boundary' => ['2026-09-09T18:00:00+00:00', '2026-09-07T18:00:00+00:00', true, true],
            'open-ended plan returned at boundary' => [null, '2026-09-07T18:30:00+00:00', true, true],
        ];
    }

    #[DataProvider('boundaries')]
    public function test_both_agreement_sides_respect_business_calendar_and_actual_custody(?string $plannedEnd, ?string $actualReturn, bool $handover, bool $allowed): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-10T12:00:00+00:00'));
        $this->mock(RentalAuthorization::class, fn ($mock) => $mock->shouldReceive('assert', 'assertUse')->zeroOrMoreTimes());
        $this->mock(ConfigurationResolverInterface::class, fn ($mock) => $mock->shouldReceive('value')
            ->with(RentalConfiguration::WORKSPACE_TIMEZONE, \Mockery::type('int'), \Mockery::type('int'))->andReturn('Asia/Colombo'));
        $this->activeFixture(function ($context, $customer, $owner, $input) use ($plannedEnd, $actualReturn, $handover, $allowed): void {
            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customer->id, $customer->row_version, array_replace($input, ['ends_at' => $plannedEnd]));
            if ($handover) {
                $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover,
                    ['occurred_at' => $input['starts_at'], 'reason' => 'Collected']);
            }
            if ($actualReturn !== null) {
                $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::ReturnVehicle,
                    ['occurred_at' => $actualReturn, 'reason' => 'Returned']);
            }
            $service = app(AgreementService::class);
            foreach ([[AgreementKind::Customer, $customer], [AgreementKind::Owner, $owner]] as [$kind, $agreement]) {
                $successor = $service->successor($kind, $context, $agreement->id, $agreement->row_version,
                    ['reference' => $agreement->reference.'-NEXT', 'agreed_on' => '2026-09-07', 'starts_on' => '2026-09-08', 'reason' => 'Prospective revision']);
                try {
                    $service->change($kind, $context, $successor->id, $successor->row_version, AgreementAction::Activate);
                    self::assertTrue($allowed, 'Cutover admitted uncovered planned or actual custody.');
                    self::assertSame(AgreementStatus::Closed, $agreement->refresh()->status);
                    self::assertSame('2026-09-07', $agreement->ends_on->toDateString());
                } catch (ValidationException $error) {
                    if ($allowed) {
                        throw $error;
                    }
                    self::assertSame(AgreementStatus::Active, $agreement->refresh()->status);
                    self::assertSame(AgreementStatus::Draft, $successor->refresh()->status);
                    self::assertCount(2, $agreement->history);
                }
            }
        });
    }
}
