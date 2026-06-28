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
