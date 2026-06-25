<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantDomainOwnershipVerificationOutcome
{
    public const VERIFIED = 'verified';
    public const RETRY = 'retry';
    public const STOP = 'stop';

    private function __construct() {}
}
