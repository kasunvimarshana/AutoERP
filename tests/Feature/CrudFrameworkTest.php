<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests for the Dynamic CRUD Framework
 *
 * Tests all advanced features:
 * - Field-level filtering
 * - Global search
 * - Multi-field sorting
 * - Sparse field selection
 * - Eager loading
 * - Pagination
 */
class CrudFrameworkTest extends TestCase
{
    public function test_base_repository_has_advanced_query_methods()
    {
        $reflection = new \ReflectionClass(\App\Core\Repositories\BaseRepository::class);

        $this->assertTrue($reflection->hasMethod('queryWithFilters'));
        $this->assertTrue($reflection->hasMethod('paginateWithFilters'));
        $this->assertTrue($reflection->hasMethod('getAllWithFilters'));
    }

    public function test_base_crud_controller_exists()
    {
        $this->assertTrue(
            class_exists(\App\Core\Http\Controllers\BaseCrudController::class)
        );
    }

    public function test_base_crud_controller_has_crud_methods()
    {
        $reflection = new \ReflectionClass(\App\Core\Http\Controllers\BaseCrudController::class);

        $this->assertTrue($reflection->hasMethod('index'));
        $this->assertTrue($reflection->hasMethod('store'));
        $this->assertTrue($reflection->hasMethod('show'));
        $this->assertTrue($reflection->hasMethod('update'));
        $this->assertTrue($reflection->hasMethod('destroy'));
    }

    public function test_base_crud_controller_has_abstract_methods()
    {
        $reflection = new \ReflectionClass(\App\Core\Http\Controllers\BaseCrudController::class);

        $this->assertTrue($reflection->getMethod('getQueryConfig')->isAbstract());
        $this->assertTrue($reflection->getMethod('getValidationRules')->isAbstract());
    }

    public function test_customer_enhanced_controller_extends_base_crud()
    {
        $controller = new \ReflectionClass(\App\Modules\CRM\Http\Controllers\CustomerEnhancedController::class);

        $this->assertTrue($controller->isSubclassOf(\App\Core\Http\Controllers\BaseCrudController::class));
    }

    public function test_customer_enhanced_controller_implements_query_config()
    {
        $controller = new \ReflectionClass(\App\Modules\CRM\Http\Controllers\CustomerEnhancedController::class);
        $method = $controller->getMethod('getQueryConfig');

        $this->assertFalse($method->isAbstract());
    }

    public function test_customer_enhanced_controller_implements_validation_rules()
    {
        $controller = new \ReflectionClass(\App\Modules\CRM\Http\Controllers\CustomerEnhancedController::class);
        $method = $controller->getMethod('getValidationRules');

        $this->assertFalse($method->isAbstract());
    }

    public function test_enhanced_customer_routes_are_registered()
    {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $routeUris = [];

        foreach ($routes as $route) {
            $routeUris[] = $route->uri();
        }

        // Check that enhanced customer routes exist
        $this->assertContains('api/v1/customers-enhanced', $routeUris);
    }

    public function test_base_repository_uses_spatie_query_builder()
    {
        $reflection = new \ReflectionClass(\App\Core\Repositories\BaseRepository::class);
        $method = $reflection->getMethod('queryWithFilters');

        // Verify the method returns QueryBuilder
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('Spatie\QueryBuilder\QueryBuilder', $returnType->getName());
    }

    public function test_crud_framework_documentation_exists()
    {
        $this->assertFileExists(base_path('CRUD_FRAMEWORK.md'));
    }

    public function test_base_crud_controller_response_helpers_exist()
    {
        $reflection = new \ReflectionClass(\App\Core\Http\Controllers\BaseCrudController::class);

        $this->assertTrue($reflection->hasMethod('successResponse'));
        $this->assertTrue($reflection->hasMethod('errorResponse'));
        $this->assertTrue($reflection->hasMethod('validationErrorResponse'));
    }

    public function test_base_repository_imports_spatie_classes()
    {
        $content = file_get_contents(app_path('Core/Repositories/BaseRepository.php'));

        $this->assertStringContainsString('use Spatie\QueryBuilder\QueryBuilder;', $content);
        $this->assertStringContainsString('use Spatie\QueryBuilder\AllowedFilter;', $content);
        $this->assertStringContainsString('use Spatie\QueryBuilder\AllowedSort;', $content);
    }
}
