<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Inventory\Application\Contracts\ApproveTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\CreateTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\FindTransferOrderServiceInterface;
use Modules\Inventory\Application\Contracts\ReceiveTransferOrderServiceInterface;
use Modules\Inventory\Domain\Entities\TransferOrder;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

class InventoryTransferOrderEndpointsAuthenticatedTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $authUser;
    private TransferOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = new UserModel([
            'name' => 'Inventory Admin',
            'email' => 'inventory-admin@example.com',
            'password' => 'hashed',
        ]);
        $this->authUser->setAttribute('id', 99);
        $this->authUser->setAttribute('tenant_id', 1);

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturn(1);
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $this->order = new TransferOrder(
            tenantId: 1,
            fromWarehouseId: 2,
            toWarehouseId: 3,
            transferNumber: 'TO-0001',
            status: 'draft',
            requestDate: '2026-05-04',
            expectedDate: null,
            shippedDate: null,
            receivedDate: null,
            notes: null,
            metadata: null,
            lines: [],
            orgUnitId: null,
            id: 1,
        );

        $createService = $this->createMock(CreateTransferOrderServiceInterface::class);
        $createService->method('execute')->willReturn($this->order);
        $this->app->instance(CreateTransferOrderServiceInterface::class, $createService);

        $findService = $this->createMock(FindTransferOrderServiceInterface::class);
        $findService->method('find')->willReturn($this->order);
        $findService->method('list')->willReturn([
            'data' => [$this->order],
            'total' => 1,
            'per_page' => 15,
            'current_page' => 1,
        ]);
        $this->app->instance(FindTransferOrderServiceInterface::class, $findService);

        $approveService = $this->createMock(ApproveTransferOrderServiceInterface::class);
        $approveService->method('execute')->willReturn(new TransferOrder(
            tenantId: 1,
            fromWarehouseId: 2,
            toWarehouseId: 3,
            transferNumber: 'TO-0001',
            status: 'approved',
            requestDate: '2026-05-04',
            expectedDate: null,
            shippedDate: null,
            receivedDate: null,
            notes: null,
            metadata: null,
            lines: [],
            orgUnitId: null,
            id: 1,
        ));
        $this->app->instance(ApproveTransferOrderServiceInterface::class, $approveService);

        $receiveService = $this->createMock(ReceiveTransferOrderServiceInterface::class);
        $receiveService->method('execute')->willReturn(new TransferOrder(
            tenantId: 1,
            fromWarehouseId: 2,
            toWarehouseId: 3,
            transferNumber: 'TO-0001',
            status: 'received',
            requestDate: '2026-05-04',
            expectedDate: null,
            shippedDate: null,
            receivedDate: '2026-05-04',
            notes: null,
            metadata: null,
            lines: [],
            orgUnitId: null,
            id: 1,
        ));
        $this->app->instance(ReceiveTransferOrderServiceInterface::class, $receiveService);
    }

    private function actingAsUser(): static
    {
        return $this->withHeader('X-Tenant-ID', '1')
            ->actingAs($this->authUser, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    public function test_index_returns_list(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/inventory/transfer-orders?tenant_id=1');

        $response->assertStatus(200);
    }

    public function test_store_creates_transfer_order(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/inventory/transfer-orders', [
                'tenant_id' => 1,
                'from_warehouse_id' => 2,
                'to_warehouse_id' => 3,
                'transfer_number' => 'TO-0001',
                'request_date' => '2026-05-04',
                'lines' => [
                    [
                        'product_id' => 10,
                        'uom_id' => 1,
                        'requested_qty' => 5,
                    ],
                ],
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.transfer_number', 'TO-0001');
    }

    public function test_show_returns_transfer_order(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/inventory/transfer-orders/1?tenant_id=1');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 1);
    }

    public function test_approve_returns_approved_order(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/inventory/transfer-orders/1/approve', [
                'tenant_id' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'approved');
    }

    public function test_receive_returns_received_order(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/inventory/transfer-orders/1/receive', [
                'tenant_id' => 1,
                'lines' => [
                    ['line_id' => 1, 'received_qty' => 5],
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'received');
    }
}
