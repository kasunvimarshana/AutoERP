<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\ModuleLoader;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ModuleLoaderTest extends TestCase
{
    private string $testModulesPath;
    private ModuleLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testModulesPath = storage_path('framework/testing/modules');
        $this->loader = new ModuleLoader($this->testModulesPath);
        
        // Clean up before tests
        if (File::isDirectory($this->testModulesPath)) {
            File::deleteDirectory($this->testModulesPath);
        }
        
        File::makeDirectory($this->testModulesPath, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up after tests
        if (File::isDirectory($this->testModulesPath)) {
            File::deleteDirectory($this->testModulesPath);
        }
        
        parent::tearDown();
    }

    private function createTestModule(string $name, array $overrides = []): void
    {
        $modulePath = $this->testModulesPath . '/' . $name;
        File::makeDirectory($modulePath, 0755, true);
        
        $manifest = array_merge([
            'name' => $name,
            'display_name' => ucfirst($name) . ' Module',
            'description' => 'Test module',
            'version' => '1.0.0',
            'author' => 'Test',
            'dependencies' => [],
            'extra' => [
                'enabled' => true,
                'priority' => 10,
            ],
        ], $overrides);
        
        File::put($modulePath . '/module.json', json_encode($manifest, JSON_PRETTY_PRINT));
    }

    public function test_discovers_modules(): void
    {
        $this->createTestModule('test1');
        $this->createTestModule('test2');
        
        $modules = $this->loader->discover();
        
        $this->assertCount(2, $modules);
        $this->assertNotNull($modules->firstWhere('name', 'test1'));
        $this->assertNotNull($modules->firstWhere('name', 'test2'));
    }

    public function test_sorts_modules_by_priority(): void
    {
        $this->createTestModule('low', ['extra' => ['priority' => 100, 'enabled' => true]]);
        $this->createTestModule('high', ['extra' => ['priority' => 1, 'enabled' => true]]);
        $this->createTestModule('medium', ['extra' => ['priority' => 50, 'enabled' => true]]);
        
        $modules = $this->loader->discover();
        
        $this->assertEquals('high', $modules->first()['name']);
        $this->assertEquals('low', $modules->last()['name']);
    }

    public function test_filters_enabled_modules(): void
    {
        $this->createTestModule('enabled', ['extra' => ['enabled' => true, 'priority' => 10]]);
        $this->createTestModule('disabled', ['extra' => ['enabled' => false, 'priority' => 10]]);
        
        $this->loader->discover();
        $enabledModules = $this->loader->getEnabledModules();
        
        $this->assertCount(1, $enabledModules);
        $this->assertEquals('enabled', $enabledModules->first()['name']);
    }

    public function test_loads_module_with_dependencies(): void
    {
        $this->createTestModule('base', ['dependencies' => []]);
        $this->createTestModule('dependent', ['dependencies' => ['base']]);
        
        $this->loader->discover();
        $this->loader->load('dependent');
        
        $this->assertTrue($this->loader->isLoaded('base'));
        $this->assertTrue($this->loader->isLoaded('dependent'));
    }

    public function test_throws_exception_for_missing_dependency(): void
    {
        $this->createTestModule('dependent', ['dependencies' => ['nonexistent']]);
        
        $this->loader->discover();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->loader->load('dependent');
    }

    public function test_loads_all_enabled_modules(): void
    {
        $this->createTestModule('module1', ['extra' => ['enabled' => true, 'priority' => 10]]);
        $this->createTestModule('module2', ['extra' => ['enabled' => true, 'priority' => 20]]);
        $this->createTestModule('module3', ['extra' => ['enabled' => false, 'priority' => 30]]);
        
        $this->loader->discover();
        $this->loader->loadAll();
        
        $loadedModules = $this->loader->getLoadedModules();
        
        $this->assertCount(2, $loadedModules);
        $this->assertArrayHasKey('module1', $loadedModules);
        $this->assertArrayHasKey('module2', $loadedModules);
        $this->assertArrayNotHasKey('module3', $loadedModules);
    }

    public function test_get_module_returns_module_data(): void
    {
        $this->createTestModule('test', ['version' => '2.0.0']);
        
        $this->loader->discover();
        $module = $this->loader->getModule('test');
        
        $this->assertNotNull($module);
        $this->assertEquals('test', $module['name']);
        $this->assertEquals('2.0.0', $module['version']);
    }

    public function test_get_module_returns_null_for_nonexistent(): void
    {
        $this->loader->discover();
        $module = $this->loader->getModule('nonexistent');
        
        $this->assertNull($module);
    }
}
