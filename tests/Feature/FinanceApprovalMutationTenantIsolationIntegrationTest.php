<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Application\Contracts\ApproveApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\CancelApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\CreateApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\RejectApprovalRequestServiceInterface;
use Modules\Finance\Domain\Exceptions\ApprovalRequestNotFoundException;
use Tests\TestCase;

class FinanceApprovalMutationTenantIsolationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant(1, 1, 1);
        $this->seedTenant(2, 2, 2);
    }

    public function testApproveApprovalRequestRejectsCrossTenantMutation(): void
    {
        $createService = app(CreateApprovalRequestServiceInterface::class);
        $approveService = app(ApproveApprovalRequestServiceInterface::class);

        app()->instance('current_tenant_id', 1);

        $created = $createService->execute($this->makePayload(
            tenantId: 1,
            workflowConfigId: 1,
            requestedByUserId: 1,
        ));

        app()->instance('current_tenant_id', 2);

        try {
            $approveService->execute([
                'id' => $created->getId(),
                'resolved_by_user_id' => 2,
                'comments' => 'Cross-tenant approval attempt',
            ]);
            $this->fail('Expected cross-tenant approval to be rejected.');
        } catch (ApprovalRequestNotFoundException) {
            $this->assertDatabaseHas('approval_requests', [
                'id' => $created->getId(),
                'tenant_id' => 1,
                'status' => 'pending',
            ]);
        }
    }

    public function testRejectApprovalRequestRejectsCrossTenantMutation(): void
    {
        $createService = app(CreateApprovalRequestServiceInterface::class);
        $rejectService = app(RejectApprovalRequestServiceInterface::class);

        app()->instance('current_tenant_id', 1);

        $created = $createService->execute($this->makePayload(
            tenantId: 1,
            workflowConfigId: 1,
            requestedByUserId: 1,
        ));

        app()->instance('current_tenant_id', 2);

        try {
            $rejectService->execute([
                'id' => $created->getId(),
                'resolved_by_user_id' => 2,
                'comments' => 'Cross-tenant rejection attempt',
            ]);
            $this->fail('Expected cross-tenant rejection to be rejected.');
        } catch (ApprovalRequestNotFoundException) {
            $this->assertDatabaseHas('approval_requests', [
                'id' => $created->getId(),
                'tenant_id' => 1,
                'status' => 'pending',
            ]);
        }
    }

    public function testCancelApprovalRequestRejectsCrossTenantMutation(): void
    {
        $createService = app(CreateApprovalRequestServiceInterface::class);
        $cancelService = app(CancelApprovalRequestServiceInterface::class);

        app()->instance('current_tenant_id', 1);

        $created = $createService->execute($this->makePayload(
            tenantId: 1,
            workflowConfigId: 1,
            requestedByUserId: 1,
        ));

        app()->instance('current_tenant_id', 2);

        try {
            $cancelService->execute([
                'id' => $created->getId(),
                'comments' => 'Cross-tenant cancel attempt',
            ]);
            $this->fail('Expected cross-tenant cancellation to be rejected.');
        } catch (ApprovalRequestNotFoundException) {
            $this->assertDatabaseHas('approval_requests', [
                'id' => $created->getId(),
                'tenant_id' => 1,
                'status' => 'pending',
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function makePayload(
        int $tenantId,
        int $workflowConfigId,
        int $requestedByUserId,
    ): array {
        return [
            'tenant_id' => $tenantId,
            'workflow_config_id' => $workflowConfigId,
            'entity_type' => 'purchase_order',
            'entity_id' => 9001,
            'requested_by_user_id' => $requestedByUserId,
            'status' => 'pending',
            'current_step_order' => 1,
        ];
    }

    private function seedTenant(int $tenantId, int $userId, int $workflowConfigId): void
    {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Tenant ' . $tenantId,
            'slug' => 'tenant-' . $tenantId,
            'domain' => null,
            'logo_path' => null,
            'database_config' => null,
            'mail_config' => null,
            'cache_config' => null,
            'queue_config' => null,
            'feature_flags' => null,
            'api_keys' => null,
            'settings' => null,
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('users')->insert([
            'id' => $userId,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'first_name' => 'Test',
            'last_name' => 'User ' . $userId,
            'email' => 'user' . $userId . '@tenant' . $tenantId . '.test',
            'email_verified_at' => null,
            'password' => bcrypt('secret'),
            'phone' => null,
            'avatar' => null,
            'status' => 'active',
            'preferences' => null,
            'address' => null,
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('approval_workflow_configs')->insert([
            'id' => $workflowConfigId,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'module' => 'purchase',
            'entity_type' => 'purchase_order',
            'name' => 'PO Approval T' . $tenantId,
            'min_amount' => null,
            'max_amount' => null,
            'steps' => json_encode([['order' => 1, 'approver_user_id' => $userId]]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
}
