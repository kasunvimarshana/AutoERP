<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Notification;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private int $userId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $tenantResponse = $this->postJson('/api/v1/tenants', [
            'name' => 'Notification Test Tenant',
            'slug' => 'notification-test-tenant',
        ]);
        $this->tenantId = $tenantResponse->json('data.id');
    }

    // ─────────────────────────────────────────────
    // Notification Template tests
    // ─────────────────────────────────────────────

    public function test_can_create_notification_template(): void
    {
        $response = $this->postJson('/api/v1/notifications/templates', [
            'tenant_id' => $this->tenantId,
            'channel' => 'in_app',
            'event_type' => 'order.confirmed',
            'name' => 'Order Confirmed',
            'subject' => 'Your order has been confirmed',
            'body' => 'Dear customer, your order {{order_number}} has been confirmed.',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonPath('data.channel', 'in_app')
            ->assertJsonPath('data.event_type', 'order.confirmed')
            ->assertJsonPath('data.name', 'Order Confirmed')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'channel', 'event_type', 'name',
                'subject', 'body', 'is_active', 'created_at', 'updated_at',
            ]]);
    }

    public function test_can_create_email_notification_template(): void
    {
        $response = $this->postJson('/api/v1/notifications/templates', [
            'tenant_id' => $this->tenantId,
            'channel' => 'email',
            'event_type' => 'invoice.issued',
            'name' => 'Invoice Issued',
            'subject' => 'Invoice #{{invoice_number}} issued',
            'body' => 'Please find your invoice attached.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.channel', 'email');
    }

    public function test_can_create_sms_notification_template(): void
    {
        $response = $this->postJson('/api/v1/notifications/templates', [
            'tenant_id' => $this->tenantId,
            'channel' => 'sms',
            'event_type' => 'shipment.dispatched',
            'name' => 'Shipment Dispatched',
            'subject' => 'Shipment Update',
            'body' => 'Your shipment has been dispatched.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.channel', 'sms');
    }

    public function test_invalid_channel_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/notifications/templates', [
            'tenant_id' => $this->tenantId,
            'channel' => 'push',
            'event_type' => 'some.event',
            'name' => 'Some Template',
            'subject' => 'Subject',
            'body' => 'Body text.',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_list_notification_templates(): void
    {
        $this->postJson('/api/v1/notifications/templates', [
            'tenant_id' => $this->tenantId,
            'channel' => 'in_app',
            'event_type' => 'event.one',
            'name' => 'Template One',
            'subject' => 'Subject One',
            'body' => 'Body One',
        ])->assertStatus(201);

        $this->postJson('/api/v1/notifications/templates', [
            'tenant_id' => $this->tenantId,
            'channel' => 'email',
            'event_type' => 'event.two',
            'name' => 'Template Two',
            'subject' => 'Subject Two',
            'body' => 'Body Two',
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/notifications/templates?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_can_get_template_by_id(): void
    {
        $template = $this->postJson('/api/v1/notifications/templates', [
            'tenant_id' => $this->tenantId,
            'channel' => 'in_app',
            'event_type' => 'lead.assigned',
            'name' => 'Lead Assigned',
            'subject' => 'New Lead',
            'body' => 'A new lead has been assigned to you.',
        ])->json('data');

        $response = $this->getJson(
            "/api/v1/notifications/templates/{$template['id']}?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $template['id'])
            ->assertJsonPath('data.event_type', 'lead.assigned');
    }

    public function test_returns_404_for_nonexistent_template(): void
    {
        $response = $this->getJson(
            "/api/v1/notifications/templates/99999?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_notification_template(): void
    {
        $template = $this->postJson('/api/v1/notifications/templates', [
            'tenant_id' => $this->tenantId,
            'channel' => 'in_app',
            'event_type' => 'task.completed',
            'name' => 'Old Name',
            'subject' => 'Old Subject',
            'body' => 'Old body.',
        ])->json('data');

        $response = $this->putJson(
            "/api/v1/notifications/templates/{$template['id']}?tenant_id={$this->tenantId}",
            [
                'name' => 'Updated Name',
                'subject' => 'Updated Subject',
                'body' => 'Updated body text.',
                'is_active' => false,
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.subject', 'Updated Subject')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_can_delete_notification_template(): void
    {
        $template = $this->postJson('/api/v1/notifications/templates', [
            'tenant_id' => $this->tenantId,
            'channel' => 'in_app',
            'event_type' => 'delete.me',
            'name' => 'Delete Me',
            'subject' => 'Subject',
            'body' => 'Body.',
        ])->json('data');

        $this->deleteJson(
            "/api/v1/notifications/templates/{$template['id']}?tenant_id={$this->tenantId}"
        )->assertStatus(200)->assertJsonPath('success', true);

        $this->getJson(
            "/api/v1/notifications/templates/{$template['id']}?tenant_id={$this->tenantId}"
        )->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // Notification (instance) tests
    // ─────────────────────────────────────────────

    public function test_can_send_in_app_notification(): void
    {
        $response = $this->postJson('/api/v1/notifications/send', [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'channel' => 'in_app',
            'event_type' => 'order.confirmed',
            'subject' => 'Order Confirmed',
            'body' => 'Your order has been confirmed.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonPath('data.user_id', $this->userId)
            ->assertJsonPath('data.channel', 'in_app')
            ->assertJsonPath('data.status', 'sent')
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'user_id', 'channel', 'event_type',
                'subject', 'body', 'status', 'sent_at', 'created_at', 'updated_at',
            ]]);
    }

    public function test_can_send_email_notification(): void
    {
        $response = $this->postJson('/api/v1/notifications/send', [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'channel' => 'email',
            'event_type' => 'invoice.issued',
            'subject' => 'Invoice Ready',
            'body' => 'Your invoice is ready.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.channel', 'email')
            ->assertJsonPath('data.status', 'sent');
    }

    public function test_can_send_notification_with_template_reference(): void
    {
        $template = $this->postJson('/api/v1/notifications/templates', [
            'tenant_id' => $this->tenantId,
            'channel' => 'in_app',
            'event_type' => 'workflow.approved',
            'name' => 'Workflow Approved',
            'subject' => 'Workflow approved',
            'body' => 'Your workflow has been approved.',
        ])->json('data');

        $response = $this->postJson('/api/v1/notifications/send', [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'channel' => 'in_app',
            'event_type' => 'workflow.approved',
            'subject' => 'Workflow approved',
            'body' => 'Your workflow has been approved.',
            'template_id' => $template['id'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.template_id', $template['id']);
    }

    public function test_can_list_notifications_for_user(): void
    {
        $this->postJson('/api/v1/notifications/send', [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'channel' => 'in_app',
            'event_type' => 'event.a',
            'subject' => 'Notification A',
            'body' => 'Body A.',
        ])->assertStatus(201);

        $this->postJson('/api/v1/notifications/send', [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'channel' => 'email',
            'event_type' => 'event.b',
            'subject' => 'Notification B',
            'body' => 'Body B.',
        ])->assertStatus(201);

        $response = $this->getJson(
            "/api/v1/notifications?tenant_id={$this->tenantId}&user_id={$this->userId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_can_get_notification_by_id(): void
    {
        $notification = $this->postJson('/api/v1/notifications/send', [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'channel' => 'in_app',
            'event_type' => 'order.shipped',
            'subject' => 'Order Shipped',
            'body' => 'Your order is on the way.',
        ])->json('data');

        $response = $this->getJson(
            "/api/v1/notifications/{$notification['id']}?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $notification['id'])
            ->assertJsonPath('data.event_type', 'order.shipped');
    }

    public function test_returns_404_for_nonexistent_notification(): void
    {
        $response = $this->getJson(
            "/api/v1/notifications/99999?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_mark_notification_as_read(): void
    {
        $notification = $this->postJson('/api/v1/notifications/send', [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'channel' => 'in_app',
            'event_type' => 'task.due',
            'subject' => 'Task Due',
            'body' => 'Your task is due.',
        ])->json('data');

        $response = $this->putJson(
            "/api/v1/notifications/{$notification['id']}/read?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'read')
            ->assertJsonPath('success', true);

        $this->assertNotNull($response->json('data.read_at'));
    }

    public function test_can_get_unread_notifications(): void
    {
        // Send two notifications
        $this->postJson('/api/v1/notifications/send', [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'channel' => 'in_app',
            'event_type' => 'unread.one',
            'subject' => 'Unread One',
            'body' => 'Body.',
        ])->assertStatus(201);

        $n2 = $this->postJson('/api/v1/notifications/send', [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'channel' => 'in_app',
            'event_type' => 'unread.two',
            'subject' => 'Unread Two',
            'body' => 'Body.',
        ])->json('data');

        // Mark one as read
        $this->putJson("/api/v1/notifications/{$n2['id']}/read?tenant_id={$this->tenantId}");

        $response = $this->getJson(
            "/api/v1/notifications/unread?tenant_id={$this->tenantId}&user_id={$this->userId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_delete_notification(): void
    {
        $notification = $this->postJson('/api/v1/notifications/send', [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'channel' => 'in_app',
            'event_type' => 'delete.this',
            'subject' => 'Delete Me',
            'body' => 'Body.',
        ])->json('data');

        $this->deleteJson(
            "/api/v1/notifications/{$notification['id']}?tenant_id={$this->tenantId}"
        )->assertStatus(200)->assertJsonPath('success', true);

        $this->getJson(
            "/api/v1/notifications/{$notification['id']}?tenant_id={$this->tenantId}"
        )->assertStatus(404);
    }

    public function test_notifications_are_tenant_isolated(): void
    {
        // Create a second tenant
        $tenant2Id = $this->postJson('/api/v1/tenants', [
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
        ])->json('data.id');

        // Send notification for tenant 1
        $this->postJson('/api/v1/notifications/send', [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'channel' => 'in_app',
            'event_type' => 'isolated.event',
            'subject' => 'Tenant 1 notification',
            'body' => 'Body.',
        ])->assertStatus(201);

        // Tenant 2 sees no notifications
        $response = $this->getJson(
            "/api/v1/notifications?tenant_id={$tenant2Id}&user_id={$this->userId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 0);
    }
}
