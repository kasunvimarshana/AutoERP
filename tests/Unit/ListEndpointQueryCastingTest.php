<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitController;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantController;
use Modules\User\Infrastructure\Http\Controllers\UserController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ListEndpointQueryCastingTest extends TestCase
{
    #[DataProvider('listControllers')]
    public function test_list_controllers_cast_pagination_query_parameters(string $controllerClass): void
    {
        $source = $this->controllerSource($controllerClass);

        $this->assertStringContainsString("\$request->integer('per_page', 15)", $source);
        $this->assertStringContainsString("\$request->integer('page', 1)", $source);
    }

    #[DataProvider('typedFilterControllers')]
    public function test_list_controllers_cast_typed_filter_query_parameters(string $controllerClass, string $expectedSnippet): void
    {
        $source = $this->controllerSource($controllerClass);

        $this->assertStringContainsString($expectedSnippet, $source);
    }

    public static function listControllers(): array
    {
        return [
            'tenant' => [TenantController::class],
            'organization-unit' => [OrganizationUnitController::class],
            'user' => [UserController::class],
        ];
    }

    public static function typedFilterControllers(): array
    {
        return [
            'tenant-active' => [TenantController::class, "\$request->boolean('active')"],
            'organization-unit-parent-id' => [OrganizationUnitController::class, "\$request->integer('parent_id')"],
            'user-active' => [UserController::class, "\$request->boolean('active')"],
        ];
    }

    private function controllerSource(string $controllerClass): string
    {
        $reflection = new \ReflectionClass($controllerClass);

        return (string) file_get_contents($reflection->getFileName());
    }
}
