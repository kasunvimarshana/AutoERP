<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\VehicleService;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\Services\VehicleServiceWorkflowServiceInterface;
use Modules\VehicleService\Domain\Constants\VehicleServiceErrorCode;
use Tests\TestCase;

final class VehicleServiceWorkflowControllerIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function testTransitionEndpointForwardsPayloadToWorkflowService(): void
    {
        $service = $this->createMock(VehicleServiceWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('transition')
            ->with(
                10,
                self::callback(static function (array $payload): bool {
                    return (string) ($payload['status'] ?? '') === 'in_progress'
                        && (int) ($payload['actor_id'] ?? 0) === 42;
                }),
            )
            ->willReturn(Result::success(['ok' => true]));

        $this->app->instance(VehicleServiceWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/vehicle-service/workflow/job-cards/10/transition', [
            'status' => 'in_progress',
            'actor_id' => 42,
        ]);

        $response->assertOk()->assertJson(['data' => ['ok' => true]]);
    }

    public function testCreateInvoiceEndpointForwardsPayloadToWorkflowService(): void
    {
        $service = $this->createMock(VehicleServiceWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('createInvoice')
            ->with(
                10,
                self::callback(static function (array $payload): bool {
                    return (string) ($payload['document_type_code'] ?? '') === 'VEHICLE_SERVICE_INVOICE';
                }),
            )
            ->willReturn(Result::success(['document_id' => 9001]));

        $this->app->instance(VehicleServiceWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/vehicle-service/workflow/job-cards/10/invoice', [
            'document_type_code' => 'VEHICLE_SERVICE_INVOICE',
        ]);

        $response->assertOk()->assertJson(['data' => ['document_id' => 9001]]);
    }

    public function testTransitionEndpointReturns404WhenServiceReportsNotFound(): void
    {
        $service = $this->createMock(VehicleServiceWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('transition')
            ->willReturn(Result::failure(new Error(
                VehicleServiceErrorCode::NOT_FOUND,
                'Job card not found.',
            )));

        $this->app->instance(VehicleServiceWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/vehicle-service/workflow/job-cards/999/transition', [
            'status' => 'completed',
        ]);

        $response->assertStatus(404)->assertJson([
            'message' => 'Job card not found.',
        ]);
    }
}
