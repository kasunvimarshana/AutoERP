<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\User\Services\UserAccessResolver;

final class PaymentAuthorizationService
{
    public function __construct(private readonly UserAccessResolver $access) {}

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This Payment action requires permission: '.$permission);
        }
    }

    public function can(?int $userId, int $tenantId, string $permission): bool
    {
        return $userId !== null && $this->access->can($userId, $tenantId, $permission);
    }
}
