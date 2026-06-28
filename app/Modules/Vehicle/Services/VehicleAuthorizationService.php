<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\User\Services\UserAccessResolver;

class VehicleAuthorizationService
{
    public const VIEW = 'vehicle.view';

    public const CREATE = 'vehicle.create';

    public const UPDATE = 'vehicle.update';

    public const DELETE = 'vehicle.delete';

    public const MANAGE_DOCUMENTS = 'vehicle.documents.manage';

    public const DOWNLOAD_DOCUMENTS = 'vehicle.documents.download';

    public const MANAGE_ATTRIBUTES = 'vehicle.attributes.manage';

    public const CHANGE_STATUS = 'vehicle.status.change';

    public const VIEW_OWNERSHIPS = 'vehicle.ownerships.view';

    public const MANAGE_OWNERSHIPS = 'vehicle.ownerships.manage';

    /** @return array<string,string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View vehicles and vehicle lookups.',
            self::CREATE => 'Create vehicles.',
            self::UPDATE => 'Update vehicles.',
            self::DELETE => 'Archive vehicles when business rules allow.',
            self::MANAGE_DOCUMENTS => 'Manage vehicle documents.',
            self::DOWNLOAD_DOCUMENTS => 'Download vehicle documents.',
            self::MANAGE_ATTRIBUTES => 'Manage vehicle attributes.',
            self::CHANGE_STATUS => 'Change vehicle lifecycle status.',
            self::VIEW_OWNERSHIPS => 'View vehicle ownership and usage history.',
            self::MANAGE_OWNERSHIPS => 'Create, change current state, and end vehicle ownership records.',
        ];
    }

    public function __construct(private readonly UserAccessResolver $access) {}

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This Vehicle action requires permission: '.$permission);
        }
    }

    public function can(int $userId, int $tenantId, string $permission): bool
    {
        return $this->access->can($userId, $tenantId, $permission);
    }
}
