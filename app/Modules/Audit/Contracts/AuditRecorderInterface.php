<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Audit\Contracts;

use Modules\Audit\Data\AuditEventData;
use Modules\Audit\Data\PlatformAuditActorData;
use Modules\Audit\Data\SystemAuditEventData;

interface AuditRecorderInterface
{
    /** Records a user action using trusted current request contexts. */
    public function record(AuditEventData $event): void;

    /** Records an authenticated platform-operator action. */
    public function recordPlatform(AuditEventData $event, ?int $targetTenantId = null): void;

    /** Records a platform-scoped action with an explicit trusted actor snapshot. */
    public function recordPlatformActor(
        AuditEventData $event,
        PlatformAuditActorData $actor,
        ?int $targetTenantId = null,
    ): void;

    /** Records an explicit background/system actor after validating its scope. */
    public function recordSystem(SystemAuditEventData $event): void;
}
