<?php

declare(strict_types=1);

namespace Tests\Feature\Sequence;

use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Sequence\Constants\SequencePermission;
use Tests\TestCase;

final class SequencePermissionBoundaryTest extends TestCase
{
    public function test_sequence_permissions_are_registered_in_the_tenant_catalogue(): void
    {
        $definitions = app(PermissionDefinitionRegistryInterface::class)->all();

        foreach (SequencePermission::descriptions() as $permission => $description) {
            self::assertArrayHasKey($permission, $definitions);
            self::assertSame('sequence', $definitions[$permission]['module']);
            self::assertSame($description, $definitions[$permission]['description']);
        }
    }

    public function test_sequence_routes_require_granular_tenant_permissions(): void
    {
        $expected = [
            'api.v1.sequence.sequences.preview-number' => SequencePermission::VIEW,
            'api.v1.sequence.sequences.generate-number' => SequencePermission::MANAGE,
            'api.v1.sequence.sequences.index' => SequencePermission::VIEW,
            'api.v1.sequence.sequences.show' => SequencePermission::VIEW,
            'api.v1.sequence.sequences.store' => SequencePermission::MANAGE,
            'api.v1.sequence.sequences.update' => SequencePermission::MANAGE,
            'api.v1.sequence.sequences.destroy' => SequencePermission::MANAGE,
        ];

        $permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');

        foreach ($expected as $routeName => $permission) {
            $route = Route::getRoutes()->getByName($routeName);

            self::assertNotNull($route, "Route [{$routeName}] must exist.");
            self::assertContains(
                $permissionMiddleware.':'.$permission,
                $route->gatherMiddleware(),
                "Route [{$routeName}] must require [{$permission}].",
            );
        }
    }
}
