<?php

declare(strict_types=1);

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Contracts\PlatformOperatorCredentialProvisionerInterface;
use Modules\User\Models\PlatformOperatorModel;
use Modules\User\Models\PlatformOperatorPermissionModel;
use Modules\User\Models\PlatformPermissionModel;
use Modules\User\Services\Platform\PlatformPermissionCatalogSynchronizer;
use RuntimeException;

final class PlatformOperatorSeeder extends Seeder
{
    private const CONFIG_PREFIX = 'user.seeding.platform_operator.';

    public function run(): void
    {
        if (! $this->enabled()) {
            return;
        }
        if (! Schema::hasTable('platform_operators')) {
            throw new RuntimeException('The platform_operators table must exist before platform operator seeding.');
        }
        if (! Schema::hasTable('platform_permissions')) {
            throw new RuntimeException('The platform_permissions table must exist before platform operator seeding.');
        }
        if (! Schema::hasTable('platform_operator_permissions')) {
            throw new RuntimeException('The platform_operator_permissions table must exist before platform operator seeding.');
        }

        $email = $this->requiredEmail();
        $password = $this->requiredPassword();
        app(TenantExecutionContextInterface::class)->runAsControlPlane(function () use ($email, $password): void {
            DB::transaction(function () use ($email, $password): void {
                app(PlatformPermissionCatalogSynchronizer::class)->synchronize();
                $now = now();
                $operator = PlatformOperatorModel::query()->where('email', $email)->lockForUpdate()->first();
                if (! $operator instanceof PlatformOperatorModel) {
                    $operator = PlatformOperatorModel::query()->create([
                        'row_version' => 1,
                        'first_name' => 'Platform',
                        'last_name' => 'Administrator',
                        'email' => $email,
                        'status' => PlatformOperatorStatus::ACTIVE,
                        'credentials_ready_at' => $now,
                        'activated_at' => $now,
                    ]);
                } else {
                    $operator->forceFill([
                        'status' => PlatformOperatorStatus::ACTIVE,
                        'credentials_ready_at' => $operator->getAttribute('credentials_ready_at') ?? $now,
                        'activated_at' => $operator->getAttribute('activated_at') ?? $now,
                        'deactivated_at' => null,
                        'row_version' => (int) $operator->getAttribute('row_version') + 1,
                        'updated_at' => $now,
                    ])->save();
                }

                $permissionIds = PlatformPermissionModel::query()->where('is_active', true)->pluck('id')->all();
                PlatformOperatorPermissionModel::query()->where('platform_operator_id', $operator->getKey())
                    ->whereNotIn('platform_permission_id', $permissionIds)->delete();
                foreach ($permissionIds as $permissionId) {
                    PlatformOperatorPermissionModel::query()->firstOrCreate([
                        'platform_operator_id' => $operator->getKey(),
                        'platform_permission_id' => (int) $permissionId,
                    ]);
                }

                app(PlatformOperatorCredentialProvisionerInterface::class)->provision((int) $operator->getKey(), $password);
            }, 3);
        });
    }

    private function enabled(): bool
    {
        return (bool) config(self::CONFIG_PREFIX.'enabled', false);
    }

    private function requiredEmail(): string
    {
        $email = strtolower(trim((string) config(self::CONFIG_PREFIX.'email')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('AUTOERP_PLATFORM_ADMIN_EMAIL must be valid when platform operator seeding is enabled.');
        }

        return $email;
    }

    private function requiredPassword(): string
    {
        $password = (string) config(self::CONFIG_PREFIX.'password');
        if (trim($password) === '') {
            throw new RuntimeException('AUTOERP_PLATFORM_ADMIN_PASSWORD is required when platform operator seeding is enabled.');
        }

        return $password;
    }
}