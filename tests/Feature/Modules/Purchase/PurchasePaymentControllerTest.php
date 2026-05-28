<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Purchase;

use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\PaymentAllocations\CreatePaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentAllocations\ListPaymentAllocationsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\CreatePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\DeletePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\GetPaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\ListPaymentsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\UpdatePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\WriteOffs\CreateWriteOffServiceInterface;
use Modules\Purchase\Application\Contracts\Services\PurchaseIntegrationServiceInterface;
use Tests\TestCase;

final class PurchasePaymentControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function testSupplierOutstandingForwardsFiltersToIntegrationService(): void
    {
        $integration = $this->createMock(PurchaseIntegrationServiceInterface::class);
        $integration->expects(self::once())
            ->method('supplierPayables')
            ->with(5, 8)
            ->willReturn(Result::success([
                ['supplier_id' => 8, 'outstanding_amount' => 225.0],
            ]));

        $this->bindPaymentControllerDependencies($integration);

        $response = $this->getJson('/api/purchase/supplier-outstanding?tenant_id=5&supplier_id=8');

        $response->assertOk()->assertJson([
            'data' => [
                ['supplier_id' => 8, 'outstanding_amount' => 225.0],
            ],
        ]);
    }

    public function testPreviewPaymentAllocationReturnsComputedSummary(): void
    {
        $integration = $this->createMock(PurchaseIntegrationServiceInterface::class);
        $integration->expects(self::once())
            ->method('sourcePaymentSummary')
            ->with(
                'purchase_order',
                10,
                self::callback(static function (array $payload): bool {
                    return (float) ($payload['allocated_amount'] ?? 0) === 75.0;
                }),
            )
            ->willReturn(Result::success([
                'outstanding_amount' => 150.0,
            ]));

        $this->bindPaymentControllerDependencies($integration);

        $response = $this->postJson('/api/purchase/preview-payment-allocation', [
            'source_type' => 'purchase_order',
            'source_id' => 10,
            'allocated_amount' => 75,
        ]);

        $response->assertOk()->assertJson([
            'data' => [
                'can_allocate' => true,
                'requested_amount' => 75.0,
                'outstanding_amount' => 150.0,
                'remaining_after_allocation' => 75.0,
            ],
        ]);
    }

    public function testStorePaymentUsesIntegrationFlowWhenSourceContextIsProvided(): void
    {
        $integration = $this->createMock(PurchaseIntegrationServiceInterface::class);
        $integration->expects(self::once())
            ->method('createSourcePayment')
            ->with(
                'purchase_order',
                10,
                self::callback(static function (array $payload): bool {
                    return (float) ($payload['amount'] ?? 0) === 125.0;
                }),
            )
            ->willReturn(Result::success([
                'payment' => ['id' => 3001],
                'allocated' => true,
            ]));

        $this->bindPaymentControllerDependencies($integration);

        $response = $this->postJson('/api/purchase/purchase-payments', [
            'source_type' => 'purchase_order',
            'source_id' => 10,
            'amount' => 125,
        ]);

        $response->assertOk()->assertJson([
            'data' => [
                'payment' => ['id' => 3001],
                'allocated' => true,
            ],
        ]);
    }

    private function bindPaymentControllerDependencies(PurchaseIntegrationServiceInterface $integration): void
    {
        $this->app->instance(PurchaseIntegrationServiceInterface::class, $integration);
        $this->app->instance(
            ListPaymentsServiceInterface::class,
            $this->createMock(ListPaymentsServiceInterface::class),
        );
        $this->app->instance(
            GetPaymentServiceInterface::class,
            $this->createMock(GetPaymentServiceInterface::class),
        );
        $this->app->instance(
            CreatePaymentServiceInterface::class,
            $this->createMock(CreatePaymentServiceInterface::class),
        );
        $this->app->instance(
            UpdatePaymentServiceInterface::class,
            $this->createMock(UpdatePaymentServiceInterface::class),
        );
        $this->app->instance(
            DeletePaymentServiceInterface::class,
            $this->createMock(DeletePaymentServiceInterface::class),
        );
        $this->app->instance(
            ListPaymentAllocationsServiceInterface::class,
            $this->createMock(ListPaymentAllocationsServiceInterface::class),
        );
        $this->app->instance(
            CreatePaymentAllocationServiceInterface::class,
            $this->createMock(CreatePaymentAllocationServiceInterface::class),
        );
        $this->app->instance(
            CreateWriteOffServiceInterface::class,
            $this->createMock(CreateWriteOffServiceInterface::class),
        );
    }
}
