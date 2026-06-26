<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\User\Constants\UserStatus;

final class TenantUserFixture
{
    /**
     * Create a credential-ready tenant user for integration tests.
     *
     * @param array<string,mixed> $attributes
     */
    public static function create(array $attributes): int
    {
        $password = (string) ($attributes['password'] ?? 'secret-password');
        unset($attributes['password']);

        $now = now();
        $status = (string) ($attributes['status'] ?? UserStatus::ACTIVE);
        $userId = (int) DB::table('users')->insertGetId(array_merge([
            'row_version' => 1,
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => null,
            'email_verified_at' => $now,
            'status' => $status,
            'phone' => null,
            'credentials_ready_at' => $now,
            'invited_at' => null,
            'activated_at' => $status === UserStatus::ACTIVE ? $now : null,
            'deactivated_at' => $status === UserStatus::INACTIVE ? $now : null,
            'suspended_at' => $status === UserStatus::SUSPENDED ? $now : null,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
            'deleted_by_user_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ], $attributes));

        DB::table('auth_user_password_credentials')->insert([
            'tenant_id' => (int) $attributes['tenant_id'],
            'user_id' => $userId,
            'password_hash' => app(PasswordHasherInterface::class)->hash($password),
            'status' => 'active',
            'changed_at' => $now,
            'revoked_at' => null,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $userId;
    }

    private function __construct() {}
}
