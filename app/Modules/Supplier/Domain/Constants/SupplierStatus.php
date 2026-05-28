<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Constants;

final class SupplierStatus
{
    public const DRAFT = 'draft';
    public const PENDING_APPROVAL = 'pending_approval';
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const BLOCKED = 'blocked';
    public const SUSPENDED = 'suspended';
    public const ARCHIVED = 'archived';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::DRAFT,
            self::PENDING_APPROVAL,
            self::ACTIVE,
            self::INACTIVE,
            self::BLOCKED,
            self::SUSPENDED,
            self::ARCHIVED,
        ];
    }
}
