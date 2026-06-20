<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\User\Services\UserAccessResolver;

class SupplierAuthorizationService
{
    public const VIEW = 'suppliers.view';

    public const CREATE = 'suppliers.create';

    public const UPDATE = 'suppliers.update';

    public const DELETE = 'suppliers.delete';

    public const VEHICLES_VIEW = 'supplier-vehicles.view';

    public const VEHICLES_CREATE = 'supplier-vehicles.create';

    public const VEHICLES_UPDATE = 'supplier-vehicles.update';

    public const VEHICLES_SET_CURRENT = 'supplier-vehicles.set-current';

    public const VEHICLES_CLEAR_CURRENT = 'supplier-vehicles.clear-current';

    public const VEHICLES_DELETE = 'supplier-vehicles.delete';

    public function __construct(private readonly UserAccessResolver $access) {}

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->access->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This Supplier action requires permission: '.$permission);
        }
    }

    /** @return array<string,string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View suppliers and supplier lookups.', self::CREATE => 'Create suppliers.', self::UPDATE => 'Update and deactivate suppliers.', self::DELETE => 'Archive unreferenced suppliers.',
            self::VEHICLES_VIEW => 'View supplier vehicle relationships.', self::VEHICLES_CREATE => 'Create supplier vehicle relationships.', self::VEHICLES_UPDATE => 'Update supplier vehicle relationships.', self::VEHICLES_SET_CURRENT => 'Set the current supplier for a vehicle.', self::VEHICLES_CLEAR_CURRENT => 'Clear the current supplier for a vehicle.', self::VEHICLES_DELETE => 'End supplier vehicle relationships.',
        ];
    }
}
