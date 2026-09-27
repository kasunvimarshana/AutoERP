<?php

declare(strict_types=1);

namespace Modules\Selling\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\User\Services\UserAccessResolver;

final class SellingAuthorizationService
{
    public const SALES_VIEW = 'selling.sales.view';

    public const SALES_CREATE = 'selling.sales.create';

    public const RETURNS_VIEW = 'selling.returns.view';

    public const RETURNS_CREATE = 'selling.returns.create';

    public function __construct(private readonly UserAccessResolver $access) {}

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::SALES_VIEW => 'View customer sales and invoices.',
            self::SALES_CREATE => 'Create and post customer sales.',
            self::RETURNS_VIEW => 'View sales returns and credit notes.',
            self::RETURNS_CREATE => 'Create and post sales returns and credit notes.',
        ];
    }

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->access->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This Selling action requires permission: '.$permission);
        }
    }
}
