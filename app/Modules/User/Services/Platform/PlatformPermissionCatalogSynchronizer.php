<?php

declare(strict_types=1);

namespace Modules\User\Services\Platform;

use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Authorization\PlatformPermission;
use Modules\User\Models\PlatformPermissionModel;

final class PlatformPermissionCatalogSynchronizer
{
    public function __construct(
        private readonly PlatformPermissionModel $permissions,
        private readonly ClockInterface $clock,
    ) {}

    /** @return array{created:int,updated:int,deactivated:int,total:int} */
    public function synchronize(): array
    {
        return DB::transaction(function (): array {
            $defined = PlatformPermission::descriptions();
            $created = 0;
            $updated = 0;

            foreach ($defined as $name => $description) {
                $permission = $this->permissions->newQuery()->where('name', $name)->lockForUpdate()->first();
                if (! $permission instanceof PlatformPermissionModel) {
                    $this->permissions->newQuery()->create([
                        'name' => $name,
                        'description' => $description,
                        'is_active' => true,
                    ]);
                    $created++;
                    continue;
                }

                if (
                    (string) $permission->getAttribute('description') !== $description
                    || ! (bool) $permission->getAttribute('is_active')
                ) {
                    $permission->forceFill([
                        'description' => $description,
                        'is_active' => true,
                        'updated_at' => $this->clock->now(),
                    ])->save();
                    $updated++;
                }
            }

            $deactivated = $this->permissions->newQuery()
                ->whereNotIn('name', array_keys($defined))
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'updated_at' => $this->clock->now(),
                ]);

            return [
                'created' => $created,
                'updated' => $updated,
                'deactivated' => $deactivated,
                'total' => count($defined),
            ];
        }, 3);
    }
}
