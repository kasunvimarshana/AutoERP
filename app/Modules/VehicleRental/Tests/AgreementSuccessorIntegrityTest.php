<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

final class AgreementSuccessorIntegrityTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(RentalAuthorization::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
    }

    public function test_predecessor_can_have_only_one_direct_successor(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $service = app(AgreementService::class);
            $predecessor = $service->create(AgreementKind::Customer, $context, $input);
            $predecessor = $service->change(
                AgreementKind::Customer,
                $context,
                $predecessor->id,
                $predecessor->row_version,
                AgreementAction::Activate,
            );

            $service->successor(AgreementKind::Customer, $context, $predecessor->id, $predecessor->row_version, [
                'reference' => $predecessor->reference.'-R2',
                'agreed_on' => '2026-09-20',
                'starts_on' => '2026-10-01',
                'reason' => 'First commercial revision',
            ]);

            $this->expectException(ConflictHttpException::class);
            $service->successor(AgreementKind::Customer, $context, $predecessor->id, $predecessor->row_version, [
                'reference' => $predecessor->reference.'-ALT',
                'agreed_on' => '2026-09-21',
                'starts_on' => '2026-10-02',
                'reason' => 'Competing commercial revision',
            ]);
        });
    }
}
