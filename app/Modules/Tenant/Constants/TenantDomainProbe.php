<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantDomainProbe
{
    public const HEADER = 'X-AutoERP-Domain-Probe';
    public const PATH = '.well-known/autoerp-tenant-domain';

    private function __construct() {}
}
