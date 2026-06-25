<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantDomainOperationalVerificationOutcome
{
    public const READY = 'ready';
    public const RETRY = 'retry';
    public const STOP = 'stop';

    private function __construct() {}
}
