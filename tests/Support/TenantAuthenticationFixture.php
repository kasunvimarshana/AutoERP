<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Enums\IdentityStatus;
use Modules\Auth\Enums\ProviderStatus;
use RuntimeException;

final class TenantAuthenticationFixture
{
    public static function provision(int $tenantId, int $userId, string $identifier): int
    {
        $identifier = mb_strtolower(trim($identifier));
        if ($tenantId < 1 || $userId < 1 || $identifier === '') {
            throw new RuntimeException('Tenant authentication fixture requires tenant, user and identifier values.');
        }

        $now = now();
        $providerId = (int) DB::table('auth_providers')->insertGetId([
            'tenant_id' => $tenantId,
            'provider_key' => 'internal',
            'name' => 'Internal authentication',
            'driver' => 'internal',
            'status' => ProviderStatus::ACTIVE->value,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('auth_identities')->insert([
            'tenant_id' => $tenantId,
            'provider_id' => $providerId,
            'user_id' => $userId,
            'provider_user_key' => $identifier,
            'status' => IdentityStatus::ACTIVE->value,
            'primary_marker' => 'primary',
            'verified_at' => $now,
            'last_authenticated_at' => null,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $providerId;
    }

    private function __construct() {}
}
