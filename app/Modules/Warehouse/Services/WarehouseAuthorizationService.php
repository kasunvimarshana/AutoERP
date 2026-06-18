<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\User\Services\UserAccessResolver;

final class WarehouseAuthorizationService
{
    public const WAREHOUSES_VIEW = 'warehouse.view';

    public const WAREHOUSES_CREATE = 'warehouse.create';

    public const WAREHOUSES_UPDATE = 'warehouse.update';

    public const WAREHOUSES_ACTIVATE = 'warehouse.activate';

    public const WAREHOUSES_DEACTIVATE = 'warehouse.deactivate';

    public const WAREHOUSES_DELETE = 'warehouse.delete';

    public const WAREHOUSES_MANAGE_DEFAULTS = 'warehouse.defaults.manage';

    public const LOCATIONS_VIEW = 'warehouse.locations.view';

    public const LOCATIONS_CREATE = 'warehouse.locations.create';

    public const LOCATIONS_UPDATE = 'warehouse.locations.update';

    public const LOCATIONS_ACTIVATE = 'warehouse.locations.activate';

    public const LOCATIONS_DEACTIVATE = 'warehouse.locations.deactivate';

    public const LOCATIONS_DELETE = 'warehouse.locations.delete';

    public const LOCATIONS_MANAGE_DEFAULTS = 'warehouse.locations.defaults.manage';

    public function __construct(private readonly UserAccessResolver $access) {}

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            self::WAREHOUSES_VIEW => 'View warehouses and warehouse summaries.',
            self::WAREHOUSES_CREATE => 'Create warehouses in the current tenant and organization scope.',
            self::WAREHOUSES_UPDATE => 'Update warehouse setup fields.',
            self::WAREHOUSES_ACTIVATE => 'Activate warehouses for new operational use.',
            self::WAREHOUSES_DEACTIVATE => 'Deactivate warehouses while preserving historical references.',
            self::WAREHOUSES_DELETE => 'Delete unused warehouses when no setup or operational records depend on them.',
            self::WAREHOUSES_MANAGE_DEFAULTS => 'Set or clear the default warehouse in a tenant organization scope.',
            self::LOCATIONS_VIEW => 'View warehouse locations and hierarchy details.',
            self::LOCATIONS_CREATE => 'Create warehouse locations in an accessible warehouse.',
            self::LOCATIONS_UPDATE => 'Update warehouse location setup fields and hierarchy.',
            self::LOCATIONS_ACTIVATE => 'Activate warehouse locations for new operational use.',
            self::LOCATIONS_DEACTIVATE => 'Deactivate warehouse locations while preserving historical references.',
            self::LOCATIONS_DELETE => 'Delete unused warehouse locations when no children or operational records depend on them.',
            self::LOCATIONS_MANAGE_DEFAULTS => 'Set or clear the default location for a warehouse.',
        ];
    }

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This Warehouse action requires permission: '.$permission);
        }
    }

    public function can(int $userId, int $tenantId, string $permission): bool
    {
        return $this->access->can($userId, $tenantId, $permission);
    }
}
