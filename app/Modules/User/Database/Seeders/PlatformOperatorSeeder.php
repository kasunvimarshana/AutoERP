<?php

declare(strict_types=1);

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Models\PlatformOperatorModel;
use Modules\User\Models\PlatformOperatorPermissionModel;
use Modules\User\Models\PlatformPermissionModel;
use Modules\User\Services\Platform\Invitations\PlatformOperatorInvitationService;
use Modules\User\Services\Platform\PlatformPermissionCatalogSynchronizer;
use RuntimeException;

final class PlatformOperatorSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->enabled()) {
            return;
        }
        foreach (['platform_operators', 'platform_permissions', 'platform_operator_permissions'] as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        $email = $this->requiredEmail();
        app(TenantExecutionContextInterface::class)->runAsControlPlane(function () use ($email): void {
            DB::transaction(function () use ($email): void {
                app(PlatformPermissionCatalogSynchronizer::class)->synchronize();
                $operator = PlatformOperatorModel::query()->where('email', $email)->lockForUpdate()->first();
                if (! $operator instanceof PlatformOperatorModel) {
                    $operator = PlatformOperatorModel::query()->create([
                        'row_version' => 1,
                        'first_name' => 'Platform',
                        'last_name' => 'Administrator',
                        'email' => $email,
                        'status' => PlatformOperatorStatus::INVITED,
                        'invited_at' => now(),
                    ]);
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

                if ($operator->getAttribute('status') === PlatformOperatorStatus::INVITED
                    && ! $operator->invitations()->where('status', 'pending')->exists()
                ) {
                    app(PlatformOperatorInvitationService::class)->issueForOperator($operator);
                }
            }, 3);
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
            throw new RuntimeException('AUTOERP_PLATFORM_ADMIN_EMAIL must be valid when platform operator seeding is enabled.');
        }

        return $email;
    }
}
