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
            self::CREATE => 'Create vehicle master records.',
            self::UPDATE => 'Update vehicle master records.',
            self::DELETE => 'Archive unreferenced vehicle master records.',
            self::MANAGE_DOCUMENTS => 'Manage vehicle documents.',
            self::DOWNLOAD_DOCUMENTS => 'Preview and download vehicle documents.',
            self::MANAGE_ATTRIBUTES => 'Manage vehicle attributes.',
            self::CHANGE_STATUS => 'Change vehicle status through the governed workflow.',
            self::VIEW_OWNERSHIPS => 'View current and historical vehicle ownership relationships.',
            self::MANAGE_OWNERSHIPS => 'Create, supersede, end, and change current vehicle ownership relationships.',
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
