<?php
namespace Modules\Tenant\Domain\Enums;
enum TenantStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';
}
