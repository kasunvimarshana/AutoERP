<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class ProductModuleGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_product_module_provider_is_registered(): void
    {
        $providersFile = $this->readSource('bootstrap/providers.php');

        $this->assertStringContainsString(
            'Modules\\Product\\Infrastructure\\Providers\\ProductServiceProvider',
            $providersFile
        );
    }

    public function test_product_module_exposes_api_resource_routes(): void
    {
        $routesFile = $this->readSource('app/Modules/Product/routes/api.php');

        $this->assertStringContainsString('Route::apiResource(\'products\'', $routesFile);
        $this->assertStringContainsString('Route::apiResource(\'product-identifiers\'', $routesFile);
        $this->assertStringContainsString('Route::apiResource(\'product-variants\'', $routesFile);
        $this->assertStringContainsString('Route::apiResource(\'product-brands\'', $routesFile);
        $this->assertStringContainsString('Route::apiResource(\'product-categories\'', $routesFile);
        $this->assertStringContainsString('Route::apiResource(\'uom-conversions\'', $routesFile);
        $this->assertStringContainsString('Route::apiResource(\'units-of-measure\'', $routesFile);
        $this->assertStringContainsString('Route::apiResource(\'product-attribute-groups\'', $routesFile);
        $this->assertStringContainsString('Route::apiResource(\'product-attributes\'', $routesFile);
        $this->assertStringContainsString('Route::apiResource(\'product-attribute-values\'', $routesFile);
        $this->assertStringContainsString('Route::apiResource(\'variant-attributes\'', $routesFile);
        $this->assertStringContainsString('Route::apiResource(\'combo-items\'', $routesFile);
        $this->assertStringContainsString('auth:api', $routesFile);
        $this->assertStringContainsString('resolve.tenant', $routesFile);
    }

    public function test_product_module_registers_catalog_bindings(): void
    {
        $providerFile = $this->readSource('app/Modules/Product/Infrastructure/Providers/ProductServiceProvider.php');

        $this->assertStringContainsString('ProductAttributeGroupRepositoryInterface::class', $providerFile);
        $this->assertStringContainsString('ProductAttributeRepositoryInterface::class', $providerFile);
        $this->assertStringContainsString('ProductAttributeValueRepositoryInterface::class', $providerFile);
        $this->assertStringContainsString('VariantAttributeRepositoryInterface::class', $providerFile);
        $this->assertStringContainsString('ComboItemRepositoryInterface::class', $providerFile);

        $this->assertStringContainsString('CreateProductAttributeGroupServiceInterface::class', $providerFile);
        $this->assertStringContainsString('CreateProductAttributeServiceInterface::class', $providerFile);
        $this->assertStringContainsString('CreateProductAttributeValueServiceInterface::class', $providerFile);
        $this->assertStringContainsString('CreateVariantAttributeServiceInterface::class', $providerFile);
        $this->assertStringContainsString('CreateComboItemServiceInterface::class', $providerFile);
    }

    private function readSource(string $relativePath): string
    {
        $fullPath = $this->repoRoot.'/'.$relativePath;
        $contents = file_get_contents($fullPath);

        if ($contents === false) {
            $this->fail('Unable to read source file: '.$relativePath);
        }

        return $contents;
    }
}
