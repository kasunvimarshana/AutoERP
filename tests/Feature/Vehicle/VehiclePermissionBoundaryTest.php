<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicle;

use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;
use Modules\Vehicle\Services\VehicleAuthorizationService;
use Tests\TestCase;

final class VehiclePermissionBoundaryTest extends TestCase
{
    public function test_vehicle_permissions_are_registered_in_the_tenant_catalogue(): void
    {
        $definitions = app(PermissionDefinitionRegistryInterface::class)->all();

        foreach (VehicleAuthorizationService::descriptions() as $permission => $description) {
            self::assertArrayHasKey($permission, $definitions);
            self::assertSame('vehicle', $definitions[$permission]['module']);
            self::assertSame($description, $definitions[$permission]['description']);
        }
    }

    public function test_vehicle_routes_require_granular_tenant_permissions(): void
    {
        $expected = [
            'api.v1.vehicles.lookup' => VehicleAuthorizationService::VIEW,
            'api.v1.vehicles.index' => VehicleAuthorizationService::VIEW,
            'api.v1.vehicles.show' => VehicleAuthorizationService::VIEW,
            'api.v1.vehicles.store' => VehicleAuthorizationService::CREATE,
            'api.v1.vehicles.with-relations.store' => VehicleAuthorizationService::CREATE,
            'api.v1.vehicles.update' => VehicleAuthorizationService::UPDATE,
            'api.v1.vehicles.activate' => VehicleAuthorizationService::CHANGE_STATUS,
            'api.v1.vehicles.deactivate' => VehicleAuthorizationService::CHANGE_STATUS,
            'api.v1.vehicles.status' => VehicleAuthorizationService::CHANGE_STATUS,
            'api.v1.vehicles.destroy' => VehicleAuthorizationService::DELETE,
            'api.v1.vehicles.documents.index' => VehicleAuthorizationService::VIEW,
            'api.v1.vehicles.documents.store' => VehicleAuthorizationService::MANAGE_DOCUMENTS,
            'api.v1.vehicles.documents.update' => VehicleAuthorizationService::MANAGE_DOCUMENTS,
            'api.v1.vehicles.documents.destroy' => VehicleAuthorizationService::MANAGE_DOCUMENTS,
            'api.v1.vehicles.documents.preview' => VehicleAuthorizationService::DOWNLOAD_DOCUMENTS,
            'api.v1.vehicles.documents.download' => VehicleAuthorizationService::DOWNLOAD_DOCUMENTS,
            'api.v1.vehicles.attributes.index' => VehicleAuthorizationService::VIEW,
            'api.v1.vehicles.attributes.store' => VehicleAuthorizationService::MANAGE_ATTRIBUTES,
            'api.v1.vehicles.attributes.update' => VehicleAuthorizationService::MANAGE_ATTRIBUTES,
            'api.v1.vehicles.attributes.destroy' => VehicleAuthorizationService::MANAGE_ATTRIBUTES,
            'api.v1.vehicles.status-history.index' => VehicleAuthorizationService::VIEW,
            'api.v1.vehicle-ownerships.index' => VehicleAuthorizationService::VIEW_OWNERSHIPS,
            'api.v1.vehicle-ownerships.show' => VehicleAuthorizationService::VIEW_OWNERSHIPS,
            'api.v1.vehicle-ownerships.store' => VehicleAuthorizationService::MANAGE_OWNERSHIPS,
            'api.v1.vehicle-ownerships.update' => VehicleAuthorizationService::MANAGE_OWNERSHIPS,
            'api.v1.vehicle-ownerships.set-current' => VehicleAuthorizationService::MANAGE_OWNERSHIPS,
            'api.v1.vehicle-ownerships.clear-current' => VehicleAuthorizationService::MANAGE_OWNERSHIPS,
            'api.v1.vehicle-ownerships.destroy' => VehicleAuthorizationService::MANAGE_OWNERSHIPS,
            ...$this->masterDataRoutes('vehicle-makes'),
            ...$this->masterDataRoutes('vehicle-models'),
            ...$this->masterDataRoutes('vehicle-types'),
            ...$this->masterDataRoutes('vehicle-categories'),
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

    /** @return array<string, string> */
    private function masterDataRoutes(string $resource): array
    {
        return [
            "api.v1.{$resource}.lookup" => VehicleAuthorizationService::VIEW,
            "api.v1.{$resource}.index" => VehicleAuthorizationService::VIEW,
            "api.v1.{$resource}.show" => VehicleAuthorizationService::VIEW,
            "api.v1.{$resource}.store" => VehicleAuthorizationService::UPDATE,
            "api.v1.{$resource}.update" => VehicleAuthorizationService::UPDATE,
            "api.v1.{$resource}.destroy" => VehicleAuthorizationService::UPDATE,
        ];
    }
}
