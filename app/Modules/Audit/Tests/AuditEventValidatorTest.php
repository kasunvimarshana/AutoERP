<?php

declare(strict_types=1);

namespace Modules\Audit\Tests;

use InvalidArgumentException;
use Modules\Audit\Constants\AuditActorType;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Data\AuditEventData;
use Modules\Audit\Data\PlatformAuditActorData;
use Modules\Audit\Data\SystemAuditEventData;
use Modules\Audit\Services\AuditEventValidator;
use PHPUnit\Framework\TestCase;

final class AuditEventValidatorTest extends TestCase
{
    public function test_it_accepts_a_complete_event_contract(): void
    {
        $event = new AuditEventData(
            eventName: 'purchase.order.posted',
            eventCategory: AuditEventCategory::WORKFLOW,
            sourceModule: 'purchase',
            subjectType: 'purchase_order',
            subjectId: '42',
            producerKey: 'purchase:order:42:posted',
        );

        (new AuditEventValidator())->validate($event);

        self::assertTrue(true);
    }

    public function test_it_accepts_every_canonical_event_category(): void
    {
        $validator = new AuditEventValidator();

        foreach (AuditEventCategory::values() as $category) {
            $validator->validate(new AuditEventData(
                eventName: 'audit.category.validated',
                eventCategory: $category,
                sourceModule: 'audit',
                subjectType: 'event_category',
                subjectId: $category,
            ));
        }

        self::assertContains(AuditEventCategory::ADMINISTRATION, AuditEventCategory::values());
        self::assertContains(AuditEventCategory::SECURITY, AuditEventCategory::values());
    }

    public function test_it_rejects_unknown_categories(): void
    {
        $event = new AuditEventData('example', 'unknown', 'example', 'record', '1');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported audit event category');

        (new AuditEventValidator())->validate($event);
    }

    public function test_platform_actor_snapshots_accept_a_pre_authentication_user(): void
    {
        $actor = new PlatformAuditActorData(
            actorType: AuditActorType::USER,
            actorId: '42',
            actorName: 'Platform Administrator',
            actorGuard: 'platform-invitation',
        );

        (new AuditEventValidator())->validatePlatformActor($actor);

        self::assertTrue(true);
    }

    public function test_system_events_cannot_impersonate_a_user_actor(): void
    {
        $event = new SystemAuditEventData(
            new AuditEventData('system.task.completed', AuditEventCategory::SYSTEM, 'system', 'task', '1'),
            AuditActorType::USER,
            '1',
            'User One',
            tenantId: 1,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-user actor type');

        (new AuditEventValidator())->validateSystem($event);
    }

    public function test_source_type_and_identifier_must_be_supplied_together(): void
    {
        $event = new AuditEventData(
            eventName: 'purchase.order.posted',
            eventCategory: AuditEventCategory::WORKFLOW,
            sourceModule: 'purchase',
            subjectType: 'purchase_order',
            subjectId: '42',
            sourceType: 'purchase_order',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('source type and source identifier');

        (new AuditEventValidator())->validate($event);
    }

    public function test_system_events_require_a_positive_tenant_scope(): void
    {
        $event = new SystemAuditEventData(
            new AuditEventData('system.task.completed', AuditEventCategory::SYSTEM, 'system', 'task', '1'),
            AuditActorType::JOB,
            'nightly-job',
            'Nightly Job',
            tenantId: 0,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tenant identifier must be positive');

        (new AuditEventValidator())->validateSystem($event);
    }

}
