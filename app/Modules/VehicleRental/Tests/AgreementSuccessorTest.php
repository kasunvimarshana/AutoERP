<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\TestCase;

final class AgreementSuccessorTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
        });
    }

    public function test_active_agreement_is_closed_at_successor_boundary_without_rewriting_history(): void
    {
        [$context, $customer, $owner] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer, $owner): void {
            $service = app(AgreementService::class);
            foreach ([AgreementKind::Customer => $customer, AgreementKind::Owner => $owner] as $kindValue => $input) {
                $kind = AgreementKind::from($kindValue);
                $record = $service->create($kind, $context, $input);
                $record = $service->change($kind, $context, $record->id, $record->row_version, AgreementAction::Activate);
                $successor = $service->successor($kind, $context, $record->id, $record->row_version, [
                    'reference' => $record->reference.'-R2',
                    'agreed_on' => '2026-09-20',
                    'starts_on' => '2026-10-01',
                    'ends_on' => null,
                    'reason' => 'Future commercial rate revision',
                ]);

                $record->refresh();
                self::assertSame(AgreementStatus::Closed, $record->status);
                self::assertSame('2026-09-30', $record->ends_on->toDateString());
                self::assertSame(AgreementStatus::Draft, $successor->status);
                self::assertSame($record->id, $successor->supersedes_agreement_id);
                self::assertSame($record->terms, $successor->terms);
                self::assertSame($record->basis, $successor->basis);
                self::assertSame($record->driver_mode, $successor->driver_mode);
                self::assertSame(3, $record->history()->count());
                self::assertSame('2026-09-30', $record->history()->get()->last()->snapshot['ends_on']);
                self::assertSame(1, $successor->history()->count());
            }
        });
    }

    public function test_successor_cannot_cut_through_existing_vehicle_use(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            app(VehicleUseService::class)->plan($context, $customer->id, $customer->row_version, $input);

            $this->expectException(ValidationException::class);
            app(AgreementService::class)->successor(AgreementKind::Customer, $context, $customer->id, $customer->row_version, [
                'reference' => $customer->reference.'-R2',
                'agreed_on' => '2026-09-07',
                'starts_on' => '2026-09-08',
                'reason' => 'Rate revision',
            ]);
        });
    }
}
