<?php

namespace Tests\Unit\Shared;

use InvalidArgumentException;
use Modules\Shared\Domain\Contracts\RepositoryInterface;
use Modules\Shared\Domain\Contracts\UseCaseInterface;
use Modules\Shared\Domain\Events\DomainEvent;
use Modules\Shared\Domain\ValueObjects\TenantId;
use Modules\Shared\Domain\ValueObjects\UserId;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Shared module.
 *
 * Covers the foundational value objects, domain event base class, and the
 * repository / use-case contracts that every other module depends on.
 * ResponseFormatter is excluded from pure unit tests because it requires the
 * Laravel HTTP kernel (response()->json()); it is exercised at the feature layer.
 */
class SharedModuleTest extends TestCase
{
    // =========================================================================
    // TenantId value object
    // =========================================================================

    public function test_tenant_id_stores_value(): void
    {
        $id = new TenantId('tenant-abc');

        $this->assertSame('tenant-abc', $id->value());
    }

    public function test_tenant_id_to_string_returns_value(): void
    {
        $id = new TenantId('tenant-xyz');

        $this->assertSame('tenant-xyz', (string) $id);
    }

    public function test_tenant_id_equals_returns_true_for_same_value(): void
    {
        $a = new TenantId('tenant-1');
        $b = new TenantId('tenant-1');

        $this->assertTrue($a->equals($b));
    }

    public function test_tenant_id_equals_returns_false_for_different_value(): void
    {
        $a = new TenantId('tenant-1');
        $b = new TenantId('tenant-2');

        $this->assertFalse($a->equals($b));
    }

    public function test_tenant_id_throws_for_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot be empty/i');

        new TenantId('');
    }

    // =========================================================================
    // UserId value object
    // =========================================================================

    public function test_user_id_stores_value(): void
    {
        $id = new UserId('user-abc');

        $this->assertSame('user-abc', $id->value());
    }

    public function test_user_id_to_string_returns_value(): void
    {
        $id = new UserId('user-xyz');

        $this->assertSame('user-xyz', (string) $id);
    }

    public function test_user_id_equals_returns_true_for_same_value(): void
    {
        $a = new UserId('user-1');
        $b = new UserId('user-1');

        $this->assertTrue($a->equals($b));
    }

    public function test_user_id_equals_returns_false_for_different_value(): void
    {
        $a = new UserId('user-1');
        $b = new UserId('user-2');

        $this->assertFalse($a->equals($b));
    }

    public function test_user_id_throws_for_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot be empty/i');

        new UserId('');
    }

    // =========================================================================
    // DomainEvent base class
    // =========================================================================

    public function test_domain_event_generates_unique_event_ids(): void
    {
        $eventA = $this->makeConcreteDomainEvent();
        $eventB = $this->makeConcreteDomainEvent();

        $this->assertNotEmpty($eventA->eventId);
        $this->assertNotEmpty($eventB->eventId);
        $this->assertNotSame($eventA->eventId, $eventB->eventId);
    }

    public function test_domain_event_id_is_valid_uuid(): void
    {
        $event = $this->makeConcreteDomainEvent();

        // UUID v4 pattern: 8-4-4-4-12 hex chars
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $event->eventId,
        );
    }

    public function test_domain_event_occurred_at_is_date_time_immutable(): void
    {
        $event = $this->makeConcreteDomainEvent();

        $this->assertInstanceOf(\DateTimeImmutable::class, $event->occurredAt);
    }

    public function test_domain_event_occurred_at_is_close_to_now(): void
    {
        $before = new \DateTimeImmutable();
        $event  = $this->makeConcreteDomainEvent();
        $after  = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $event->occurredAt->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $event->occurredAt->getTimestamp());
    }

    // =========================================================================
    // Contract interfaces — structural integrity
    // =========================================================================

    public function test_repository_interface_declares_required_methods(): void
    {
        $methods = get_class_methods(RepositoryInterface::class);

        $this->assertContains('findById', $methods);
        $this->assertContains('findAll', $methods);
        $this->assertContains('create', $methods);
        $this->assertContains('update', $methods);
        $this->assertContains('delete', $methods);
    }

    public function test_use_case_interface_declares_execute_method(): void
    {
        $methods = get_class_methods(UseCaseInterface::class);

        $this->assertContains('execute', $methods);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Return a concrete anonymous subclass of the abstract DomainEvent so its
     * constructor logic can be tested without any Eloquent dependency.
     * The Dispatchable and SerializesModels traits do not modify the constructor,
     * so parent::__construct() safely initialises the readonly properties.
     */
    private function makeConcreteDomainEvent(): DomainEvent
    {
        return new class extends DomainEvent {
            public function __construct()
            {
                parent::__construct();
            }
        };
    }
}
