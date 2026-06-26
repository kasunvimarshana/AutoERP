<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\User\Contracts\PlatformOperatorAuthenticationDirectoryInterface;

final readonly class PlatformAuthProfileBuilder
{
    public function __construct(private PlatformOperatorAuthenticationDirectoryInterface $operators) {}

    /** @param array<string,mixed> $token @return array<string,mixed> */
    public function build(array $token): array
    {
        $operatorId = $this->positiveInt($token['platform_operator_id'] ?? null);
        $operator = $operatorId === null ? null : $this->operators->findActivePlatformById($operatorId);
        if ($operatorId === null || $operator === null) {
            throw new AuthFailure(
                AuthErrorCode::UNAUTHORIZED_ACCESS,
                'Platform authentication is required.',
                401,
            );
        }

        $permissions = $this->operators->permissionNames($operatorId);

        return [
            'user' => array_merge($operator, [
                'roles' => ['Platform Operator'],
                'permissions' => $permissions,
                'is_platform_operator' => true,
            ]),
            'tenant' => null,
            'organization_unit' => null,
            'roles' => ['Platform Operator'],
            'permissions' => $permissions,
            'enabled_modules' => null,
            'is_platform_operator' => true,
        ];
    }

    private function positiveInt(mixed $value): ?int
    {
        if (! is_int($value) && ! ctype_digit((string) $value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }
}
