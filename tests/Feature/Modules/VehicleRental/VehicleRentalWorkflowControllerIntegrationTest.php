<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\VehicleRental;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\Services\VehicleRentalWorkflowServiceInterface;
use Modules\VehicleRental\Domain\Constants\VehicleRentalErrorCode;
use Tests\TestCase;

final class VehicleRentalWorkflowControllerIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function testAgreementTransitionEndpointForwardsPayloadToWorkflowService(): void
    {
        $service = $this->createMock(VehicleRentalWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('transitionAgreement')
            ->with(
                10,
                self::callback(static function (array $payload): bool {
                    return (string) ($payload['status'] ?? '') === 'confirmed'
                        && (int) ($payload['actor_id'] ?? 0) === 42;
                }),
            )
            ->willReturn(Result::success(['ok' => true]));

        $this->app->instance(VehicleRentalWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/vehicle-rental/workflow/agreements/10/transition', [
            'status' => 'confirmed',
            'actor_id' => 42,
        ]);

        $response->assertOk()->assertJson(['data' => ['ok' => true]]);
    }

    public function testCreateInvoiceEndpointForwardsPayloadToWorkflowService(): void
    {
        $service = $this->createMock(VehicleRentalWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('createInvoice')
            ->with(
                10,
                self::callback(static function (array $payload): bool {
                    return (int) ($payload['document_type_id'] ?? 0) === 11;
                }),
            )
            ->willReturn(Result::success(['document_id' => 9001]));

        $this->app->instance(VehicleRentalWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/vehicle-rental/workflow/agreements/10/invoice', [
            'document_type_id' => 11,
        ]);

        $response->assertOk()->assertJson(['data' => ['document_id' => 9001]]);
    }

    public function testAgreementTransitionEndpointReturns404WhenServiceReportsNotFound(): void
    {
        $service = $this->createMock(VehicleRentalWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('transitionAgreement')
            ->willReturn(Result::failure(new Error(
                VehicleRentalErrorCode::NOT_FOUND,
                'Agreement not found.',
            )));

        $this->app->instance(VehicleRentalWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/vehicle-rental/workflow/agreements/999/transition', [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(404)->assertJson([
            'message' => 'Agreement not found.',
        ]);
    }
}
