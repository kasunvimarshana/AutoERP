<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Broadcasting\PrivateChannel;
use Modules\Auth\Domain\Events\UserLoggedIn;
use Modules\Auth\Domain\Events\UserLoggedOut;
use Modules\Auth\Domain\Events\UserRegistered;
use Modules\Core\Domain\Events\BaseEvent;
use Modules\Core\Domain\Events\UserScopedEvent;
use Modules\Core\Infrastructure\Broadcasting\Channels\OrgUnitChannel;
use Modules\Core\Infrastructure\Broadcasting\Channels\PresenceOrgUnitChannel;
use Modules\Core\Infrastructure\Broadcasting\Channels\PresenceTenantChannel;
use Modules\Core\Infrastructure\Broadcasting\Channels\TenantChannel;
use Modules\Core\Infrastructure\Broadcasting\Channels\UserChannel;
use Modules\Core\Infrastructure\Broadcasting\Contracts\BroadcastServiceInterface;
use Modules\Core\Infrastructure\Broadcasting\Contracts\ChannelManagerInterface;
use Modules\Core\Infrastructure\Broadcasting\Contracts\EventBroadcasterInterface;
use Modules\Core\Infrastructure\Broadcasting\Services\BroadcastService;
use Modules\Core\Infrastructure\Broadcasting\Services\ChannelManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Core Broadcasting layer.
 *
 * These tests are pure-PHP (no Laravel container) and verify:
 * - Contracts exist in the expected namespaces.
 * - Implementations exist and satisfy their interfaces.
 * - Channel authorization classes exist and have the expected join() method.
 * - BaseEvent uses PrivateChannel and exposes broadcastAs().
 * - ChannelManager stores and resolves callbacks correctly.
 */
class BroadcastingTest extends TestCase
{
    // -----------------------------------------------------------------------
    // 1. Contract existence
    // -----------------------------------------------------------------------

    public function test_broadcast_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(BroadcastServiceInterface::class));
    }

    public function test_channel_manager_interface_exists(): void
    {
        $this->assertTrue(interface_exists(ChannelManagerInterface::class));
    }

    public function test_event_broadcaster_interface_exists(): void
    {
        $this->assertTrue(interface_exists(EventBroadcasterInterface::class));
    }

    // -----------------------------------------------------------------------
    // 2. Implementation / interface compliance
    // -----------------------------------------------------------------------

    public function test_broadcast_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(BroadcastService::class, BroadcastServiceInterface::class),
            'BroadcastService must implement BroadcastServiceInterface.'
        );
    }

    public function test_channel_manager_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(ChannelManager::class, ChannelManagerInterface::class),
            'ChannelManager must implement ChannelManagerInterface.'
        );
    }

    // -----------------------------------------------------------------------
    // 3. Channel classes exist with correct join() method
    // -----------------------------------------------------------------------

    public function test_tenant_channel_class_exists(): void
    {
        $this->assertTrue(class_exists(TenantChannel::class));
    }

    public function test_org_unit_channel_class_exists(): void
    {
        $this->assertTrue(class_exists(OrgUnitChannel::class));
    }

    public function test_user_channel_class_exists(): void
    {
        $this->assertTrue(class_exists(UserChannel::class));
    }

    public function test_tenant_channel_has_join_method(): void
    {
        $this->assertTrue(method_exists(TenantChannel::class, 'join'));
    }

    public function test_org_unit_channel_has_join_method(): void
    {
        $this->assertTrue(method_exists(OrgUnitChannel::class, 'join'));
    }

    public function test_user_channel_has_join_method(): void
    {
        $this->assertTrue(method_exists(UserChannel::class, 'join'));
    }

    // -----------------------------------------------------------------------
    // 4. UserChannel authorization logic
    // -----------------------------------------------------------------------

    public function test_user_channel_authorizes_matching_user(): void
    {
        $channel = new UserChannel();
        $user = $this->makeAuthUser(42);

        $this->assertTrue($channel->join($user, 42));
        $this->assertTrue($channel->join($user, '42'));
    }

    public function test_user_channel_rejects_different_user(): void
    {
        $channel = new UserChannel();
        $user = $this->makeAuthUser(1);

        $this->assertFalse($channel->join($user, 99));
    }

    // -----------------------------------------------------------------------
    // 5. TenantChannel authorization logic
    // -----------------------------------------------------------------------

    public function test_tenant_channel_authorizes_matching_tenant(): void
    {
        $channel = new TenantChannel();
        $user = $this->makeAuthUser(1, tenantId: 5);

        $this->assertTrue($channel->join($user, 5));
        $this->assertTrue($channel->join($user, '5'));
    }

    public function test_tenant_channel_rejects_different_tenant(): void
    {
        $channel = new TenantChannel();
        $user = $this->makeAuthUser(1, tenantId: 5);

        $this->assertFalse($channel->join($user, 99));
    }

    public function test_tenant_channel_rejects_user_without_tenant_id(): void
    {
        $channel = new TenantChannel();
        $user = $this->makeAuthUser(1); // no tenant_id set

        $this->assertFalse($channel->join($user, 1));
    }

    // -----------------------------------------------------------------------
    // 6. OrgUnitChannel authorization logic
    // -----------------------------------------------------------------------

    public function test_org_unit_channel_authorizes_matching_org_unit(): void
    {
        $channel = new OrgUnitChannel();
        $user = $this->makeAuthUser(1, orgUnitId: 10);

        $this->assertTrue($channel->join($user, 10));
        $this->assertTrue($channel->join($user, '10'));
    }

    public function test_org_unit_channel_rejects_different_org_unit(): void
    {
        $channel = new OrgUnitChannel();
        $user = $this->makeAuthUser(1, orgUnitId: 10);

        $this->assertFalse($channel->join($user, 99));
    }

    // -----------------------------------------------------------------------
    // 7. ChannelManager stores and resolves callbacks
    // -----------------------------------------------------------------------

    public function test_channel_manager_register_and_resolve(): void
    {
        $manager = new ChannelManager();
        $callback = fn ($user, $id) => true;

        $manager->register('private-tenant.{tenantId}', $callback);

        $resolved = $manager->resolve('private-tenant.{tenantId}');
        $this->assertSame($callback, $resolved);
    }

    public function test_channel_manager_all_returns_registered_channels(): void
    {
        $manager = new ChannelManager();
        $cb1 = fn () => true;
        $cb2 = fn () => false;

        $manager->register('private-tenant.{tenantId}', $cb1);
        $manager->register('private-user.{userId}', $cb2);

        $all = $manager->all();
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('private-tenant.{tenantId}', $all);
        $this->assertArrayHasKey('private-user.{userId}', $all);
    }

    public function test_channel_manager_resolve_returns_null_for_unknown_channel(): void
    {
        $manager = new ChannelManager();
        $this->assertNull($manager->resolve('nonexistent.{id}'));
    }

    // -----------------------------------------------------------------------
    // 8. BaseEvent uses PrivateChannel and broadcastAs()
    // -----------------------------------------------------------------------

    public function test_base_event_broadcasts_on_private_channels(): void
    {
        $event = $this->makeConcreteBaseEvent(tenantId: 3);
        $channels = $event->broadcastOn();

        $this->assertNotEmpty($channels);
        foreach ($channels as $channel) {
            $this->assertInstanceOf(PrivateChannel::class, $channel);
        }
    }

    public function test_base_event_includes_tenant_channel(): void
    {
        $event = $this->makeConcreteBaseEvent(tenantId: 7);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-tenant.7', $names);
    }

    public function test_base_event_includes_org_unit_channel_when_set(): void
    {
        $event = $this->makeConcreteBaseEvent(tenantId: 1, orgUnitId: 4);
        $names = array_map(fn ($c) => $c->name, $event->broadcastOn());

        $this->assertContains('private-org.4', $names);
    }

    public function test_base_event_omits_org_unit_channel_when_null(): void
    {
        $event = $this->makeConcreteBaseEvent(tenantId: 1, orgUnitId: null);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
    }

    public function test_base_event_broadcast_as_returns_class_short_name(): void
    {
        $event = $this->makeConcreteBaseEvent(tenantId: 1);
        // The anonymous concrete subclass is named via get_class(); the method
        // derives the short name from the last segment after '\\'.
        $broadcastAs = $event->broadcastAs();
        $this->assertIsString($broadcastAs);
        $this->assertNotEmpty($broadcastAs);
    }

    // -----------------------------------------------------------------------
    // 9. BroadcastService channelName()
    // -----------------------------------------------------------------------

    public function test_broadcast_service_channel_name_for_tenant(): void
    {
        $service = $this->makeBroadcastService();
        $this->assertSame('private-tenant.5', $service->channelName('tenant', 5));
    }

    public function test_broadcast_service_channel_name_for_org_unit(): void
    {
        $service = $this->makeBroadcastService();
        $this->assertSame('private-org.10', $service->channelName('org-unit', 10));
    }

    public function test_broadcast_service_channel_name_for_user(): void
    {
        $service = $this->makeBroadcastService();
        $this->assertSame('private-user.42', $service->channelName('user', 42));
    }

    public function test_broadcast_service_channel_name_fallback_for_unknown_type(): void
    {
        $service = $this->makeBroadcastService();
        // Unknown type should generate a reasonable fallback channel name.
        $name = $service->channelName('custom', 99);
        $this->assertStringContainsString('99', $name);
    }

    // -----------------------------------------------------------------------
    // 10. UserScopedEvent — auth events broadcast on the user channel
    // -----------------------------------------------------------------------

    public function test_user_scoped_event_class_exists(): void
    {
        $this->assertTrue(class_exists(UserScopedEvent::class));
    }

    public function test_user_scoped_event_implements_should_broadcast(): void
    {
        $this->assertTrue(
            is_subclass_of(UserScopedEvent::class, \Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, true),
        );
    }

    public function test_user_logged_in_extends_user_scoped_event(): void
    {
        $this->assertTrue(is_subclass_of(UserLoggedIn::class, UserScopedEvent::class));
    }

    public function test_user_logged_out_extends_user_scoped_event(): void
    {
        $this->assertTrue(is_subclass_of(UserLoggedOut::class, UserScopedEvent::class));
    }

    public function test_user_registered_extends_user_scoped_event(): void
    {
        $this->assertTrue(is_subclass_of(UserRegistered::class, UserScopedEvent::class));
    }

    public function test_user_logged_in_broadcasts_on_private_user_channel(): void
    {
        $event = new UserLoggedIn(99, 'alice@example.com', '127.0.0.1');
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-user.99', $channels[0]->name);
    }

    public function test_user_logged_in_broadcast_with_returns_expected_keys(): void
    {
        $event = new UserLoggedIn(7, 'bob@example.com', '10.0.0.1', 'Mozilla/5.0');
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('userId', $payload);
        $this->assertArrayHasKey('email', $payload);
        $this->assertArrayHasKey('ipAddress', $payload);
        $this->assertSame(7, $payload['userId']);
        $this->assertSame('bob@example.com', $payload['email']);
    }

    public function test_user_logged_out_broadcast_with_returns_expected_keys(): void
    {
        $event = new UserLoggedOut(3, 'carol@example.com');
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('userId', $payload);
        $this->assertArrayHasKey('email', $payload);
        $this->assertSame(3, $payload['userId']);
    }

    public function test_user_registered_broadcast_with_returns_expected_keys(): void
    {
        $event = new UserRegistered(12, 'dave@example.com', 'Dave', 'Smith');
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('userId', $payload);
        $this->assertArrayHasKey('email', $payload);
        $this->assertArrayHasKey('firstName', $payload);
        $this->assertArrayHasKey('lastName', $payload);
    }

    public function test_user_scoped_event_broadcast_as_returns_short_class_name(): void
    {
        $event = new UserLoggedIn(1, 'x@x.com');
        $this->assertSame('UserLoggedIn', $event->broadcastAs());
    }

    // -----------------------------------------------------------------------
    // 11. BaseEvent::broadcastWith() base payload
    // -----------------------------------------------------------------------

    public function test_base_event_broadcast_with_contains_tenant_id(): void
    {
        $event = $this->makeConcreteBaseEvent(tenantId: 5);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('tenantId', $payload);
        $this->assertSame(5, $payload['tenantId']);
    }

    public function test_base_event_broadcast_with_contains_org_unit_id(): void
    {
        $event = $this->makeConcreteBaseEvent(tenantId: 2, orgUnitId: 8);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('orgUnitId', $payload);
        $this->assertSame(8, $payload['orgUnitId']);
    }

    public function test_base_event_broadcast_with_org_unit_id_null_when_not_set(): void
    {
        $event = $this->makeConcreteBaseEvent(tenantId: 1);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('orgUnitId', $payload);
        $this->assertNull($payload['orgUnitId']);
    }

    // -----------------------------------------------------------------------
    // 12. PresenceTenantChannel authorization logic
    // -----------------------------------------------------------------------

    public function test_presence_tenant_channel_class_exists(): void
    {
        $this->assertTrue(class_exists(PresenceTenantChannel::class));
    }

    public function test_presence_tenant_channel_has_join_method(): void
    {
        $this->assertTrue(method_exists(PresenceTenantChannel::class, 'join'));
    }

    public function test_presence_tenant_channel_returns_user_data_for_matching_tenant(): void
    {
        $channel = new PresenceTenantChannel();
        $user = $this->makePresenceUser(id: 10, tenantId: 3);

        $result = $channel->join($user, 3);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertSame(10, $result['id']);
    }

    public function test_presence_tenant_channel_returns_false_for_wrong_tenant(): void
    {
        $channel = new PresenceTenantChannel();
        $user = $this->makePresenceUser(id: 1, tenantId: 2);

        $this->assertFalse($channel->join($user, 99));
    }

    public function test_presence_tenant_channel_returns_false_without_tenant_id(): void
    {
        $channel = new PresenceTenantChannel();
        $user = $this->makeAuthUser(1); // no tenant_id

        $this->assertFalse($channel->join($user, 1));
    }

    // -----------------------------------------------------------------------
    // 13. PresenceOrgUnitChannel authorization logic
    // -----------------------------------------------------------------------

    public function test_presence_org_unit_channel_class_exists(): void
    {
        $this->assertTrue(class_exists(PresenceOrgUnitChannel::class));
    }

    public function test_presence_org_unit_channel_has_join_method(): void
    {
        $this->assertTrue(method_exists(PresenceOrgUnitChannel::class, 'join'));
    }

    public function test_presence_org_unit_channel_returns_user_data_for_matching_org_unit(): void
    {
        $channel = new PresenceOrgUnitChannel();
        $user = $this->makePresenceUser(id: 5, tenantId: 1, orgUnitId: 20);

        $result = $channel->join($user, 20);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertSame(5, $result['id']);
    }

    public function test_presence_org_unit_channel_returns_false_for_wrong_org_unit(): void
    {
        $channel = new PresenceOrgUnitChannel();
        $user = $this->makePresenceUser(id: 1, tenantId: 1, orgUnitId: 5);

        $this->assertFalse($channel->join($user, 99));
    }

    public function test_presence_org_unit_channel_returns_false_without_org_unit_id(): void
    {
        $channel = new PresenceOrgUnitChannel();
        $user = $this->makeAuthUser(1, tenantId: 2); // no organization_unit_id

        $this->assertFalse($channel->join($user, 1));
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /** Create a minimal Authenticatable stub with optional name for presence tests. */
    private function makePresenceUser(
        int $id,
        ?int $tenantId = null,
        ?int $orgUnitId = null,
        string $name = 'Test User',
    ): \Illuminate\Contracts\Auth\Authenticatable {
        $user = new class ($id, $tenantId, $orgUnitId, $name) implements \Illuminate\Contracts\Auth\Authenticatable {
            public function __construct(
                private int $id,
                public ?int $tenant_id,
                public ?int $organization_unit_id,
                public string $name,
            ) {}

            public function getAuthIdentifierName(): string { return 'id'; }
            public function getAuthIdentifier(): mixed { return $this->id; }
            public function getAuthPasswordName(): string { return 'password'; }
            public function getAuthPassword(): string { return ''; }
            public function getRememberToken(): string { return ''; }
            public function setRememberToken($value): void {}
            public function getRememberTokenName(): string { return 'remember_token'; }
        };

        return $user;
    }

    // -----------------------------------------------------------------------
    // Helpers (original)
    // -----------------------------------------------------------------------

    /** Create a minimal Authenticatable stub. */
    private function makeAuthUser(
        int $id,
        ?int $tenantId = null,
        ?int $orgUnitId = null,
    ): \Illuminate\Contracts\Auth\Authenticatable {
        $user = new class ($id, $tenantId, $orgUnitId) implements \Illuminate\Contracts\Auth\Authenticatable {
            public function __construct(
                private int $id,
                public ?int $tenant_id,
                public ?int $organization_unit_id,
            ) {}

            public function getAuthIdentifierName(): string { return 'id'; }
            public function getAuthIdentifier(): mixed { return $this->id; }
            public function getAuthPasswordName(): string { return 'password'; }
            public function getAuthPassword(): string { return ''; }
            public function getRememberToken(): string { return ''; }
            public function setRememberToken($value): void {}
            public function getRememberTokenName(): string { return 'remember_token'; }
        };

        return $user;
    }

    /** Create a concrete anonymous subclass of BaseEvent for testing. */
    private function makeConcreteBaseEvent(int $tenantId, ?int $orgUnitId = null): BaseEvent
    {
        return new class ($tenantId, $orgUnitId) extends BaseEvent {};
    }

    /** Create a BroadcastService with a null broadcaster for channelName() tests. */
    private function makeBroadcastService(): BroadcastService
    {
        // BroadcastingFactory is only needed for broadcast(); we test channelName() only.
        $factory = $this->createMock(\Illuminate\Contracts\Broadcasting\Factory::class);

        return new BroadcastService($factory);
    }
}
