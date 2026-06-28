<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\User\Services\UserAccessResolver;

class CustomerAuthorizationService
{
    public const VIEW = 'customers.view';

    public const CREATE = 'customers.create';

    public const UPDATE = 'customers.update';

    public const DELETE = 'customers.delete';

    public const VEHICLES_VIEW = 'customer-vehicles.view';

    public const VEHICLES_CREATE = 'customer-vehicles.create';

    public const VEHICLES_UPDATE = 'customer-vehicles.update';

    public const VEHICLES_SET_CURRENT = 'customer-vehicles.set-current';

    public const VEHICLES_CLEAR_CURRENT = 'customer-vehicles.clear-current';

    public const VEHICLES_DELETE = 'customer-vehicles.delete';

    public function __construct(private readonly UserAccessResolver $access) {}

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->access->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This Customer action requires permission: '.$permission);
        }
    }

    /** @return array<string,string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View customers and customer lookups.', self::CREATE => 'Create customers.', self::UPDATE => 'Update and deactivate customers.', self::DELETE => 'Archive unreferenced customers.',
            self::VEHICLES_VIEW => 'View customer vehicle relationships.', self::VEHICLES_CREATE => 'Create customer vehicle relationships.', self::VEHICLES_UPDATE => 'Update customer vehicle relationships.', self::VEHICLES_SET_CURRENT => 'Set the current customer for a vehicle.', self::VEHICLES_CLEAR_CURRENT => 'Clear the current customer for a vehicle.', self::VEHICLES_DELETE => 'End customer vehicle relationships.',
        ];
    }
}
