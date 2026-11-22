<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Tenant\Constants;

final class TenantDomainProbe
{
    public const HEADER = 'X-AutoERP-Domain-Probe';
    public const PATH = '.well-known/autoerp-tenant-domain';

    private function __construct() {}
}
