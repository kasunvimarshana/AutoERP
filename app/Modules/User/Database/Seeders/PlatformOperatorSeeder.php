<?php

declare(strict_types=1);

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Contracts\PasswordHasherInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\UserStatus;
use Modules\User\Models\PlatformOperatorPermissionModel;
use Modules\User\Models\PlatformPermissionModel;
use Modules\User\Models\UserModel;
use Modules\User\Services\Platform\PlatformPermissionCatalogSynchronizer;
use RuntimeException;

final class PlatformOperatorSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->enabled()) {
            return;
        }
        if (! Schema::hasTable('users')
            || ! Schema::hasTable('platform_permissions')
            || ! Schema::hasTable('platform_operator_permissions')
        ) {
            return;
        }

        $email = $this->requiredEmail();

        app(TenantExecutionContextInterface::class)->runAsControlPlane(function () use ($email): void {
            $operator = UserModel::query()->firstOrNew([
                'platform_login_email' => $email,
            ]);
            $isNew = ! $operator->exists;
            if ($operator->exists && (
                $operator->getAttribute('tenant_id') !== null
                || ! (bool) $operator->getAttribute('is_platform_operator')
            )) {
                throw new RuntimeException('The configured platform login email belongs to a non-platform identity.');
            }

            $metadata = is_array($operator->getAttribute('metadata'))
                ? $operator->getAttribute('metadata')
                : [];

            $attributes = [
                'tenant_id' => null,
                'platform_login_email' => $email,
                'email' => $email,
                'username' => null,
                'status' => UserStatus::ACTIVE,
                'is_platform_operator' => true,
                'metadata' => [
                    ...$metadata,
                    'seed_source' => 'platform_operator_seeder',
                    'account_scope' => 'platform',
                ],
            ];
            if ($isNew) {
                $attributes = [
                    ...$attributes,
                    'first_name' => 'Platform',
                    'last_name' => 'Administrator',
                    'email_verified_at' => now(),
                    'password' => app(PasswordHasherInterface::class)->hash($this->requiredPassword()),
                    'row_version' => 1,
                ];
            } else {
                $operator->forceFill($attributes);
                if ($operator->isDirty()) {
                    $attributes['row_version'] = max(1, (int) $operator->getAttribute('row_version')) + 1;
                }
            }

            if ($isNew || $operator->isDirty()) {
                $operator->forceFill($attributes)->save();
            }

            app(PlatformPermissionCatalogSynchronizer::class)->synchronize();
            $permissionIds = PlatformPermissionModel::query()
                ->where('is_active', true)
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            PlatformOperatorPermissionModel::query()
                ->where('user_id', $operator->getKey())
                ->whereNotIn('platform_permission_id', $permissionIds)
                ->delete();

            foreach ($permissionIds as $permissionId) {
                PlatformOperatorPermissionModel::query()->firstOrCreate([
                    'user_id' => $operator->getKey(),
                    'platform_permission_id' => $permissionId,
                ]);
            }
        });
    }

    private function enabled(): bool
    {
        return filter_var(env('AUTOERP_SEED_PLATFORM_OPERATOR', false), FILTER_VALIDATE_BOOL);
    }

    private function requiredEmail(): string
    {
        $email = strtolower(trim((string) env('AUTOERP_PLATFORM_ADMIN_EMAIL', '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException(
                'AUTOERP_PLATFORM_ADMIN_EMAIL must be a valid email when platform operator seeding is enabled.',
            );
        }

        return $email;
    }

    private function requiredPassword(): string
    {
        $password = (string) env('AUTOERP_PLATFORM_ADMIN_PASSWORD', '');
        if (mb_strlen($password) < 12) {
            throw new RuntimeException(
                'AUTOERP_PLATFORM_ADMIN_PASSWORD must contain at least 12 characters when platform operator seeding is enabled.',
            );
        }

        return $password;
    }
}
