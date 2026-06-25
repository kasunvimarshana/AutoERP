<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantInfrastructureStrategy
{
    public const SHARED_PRIVATE_STORAGE = 'shared_private_storage';
    public const PLATFORM_MAILER = 'platform_mailer';
    public const TENANT_OBJECT_KEY_PREFIX = 'tenant_object_key_prefix';

    private function __construct() {}
}
