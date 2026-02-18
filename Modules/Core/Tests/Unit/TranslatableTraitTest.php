<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Modules\Inventory\Entities\Product;
use Tests\TestCase;

/**
 * Translatable Trait Tests
 *
 * Tests for native multi-language translation system
 */
class TranslatableTraitTest extends TestCase
{
    use RefreshDatabase;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create([
            'name' => ['en' => 'Product', 'es' => 'Producto', 'fr' => 'Produit'],
            'description' => ['en' => 'Description', 'es' => 'Descripción', 'fr' => 'Description'],
        ]);
    }

    public function test_it_retrieves_translation_for_current_locale(): void
    {
        App::setLocale('en');
        $this->assertEquals('Product', $this->product->getTranslation('name'));

        App::setLocale('es');
        $this->assertEquals('Producto', $this->product->getTranslation('name'));

        App::setLocale('fr');
        $this->assertEquals('Produit', $this->product->getTranslation('name'));
    }

    public function test_it_retrieves_translation_for_specific_locale(): void
    {
        $this->assertEquals('Product', $this->product->getTranslation('name', 'en'));
        $this->assertEquals('Producto', $this->product->getTranslation('name', 'es'));
        $this->assertEquals('Produit', $this->product->getTranslation('name', 'fr'));
    }

    public function test_it_falls_back_to_default_locale_when_translation_missing(): void
    {
        App::setLocale('de'); // German not available

        config(['app.fallback_locale' => 'en']);

        $translation = $this->product->getTranslation('name');
        $this->assertEquals('Product', $translation);
    }

    public function test_it_sets_translation_for_specific_locale(): void
    {
        $this->product->setTranslation('name', 'de', 'Produkt');
        $this->product->save();

        $this->assertEquals('Produkt', $this->product->getTranslation('name', 'de'));
    }

    public function test_it_updates_existing_translation(): void
    {
        $this->product->setTranslation('name', 'en', 'Updated Product');
        $this->product->save();

        $this->assertEquals('Updated Product', $this->product->getTranslation('name', 'en'));
    }

    public function test_it_handles_multiple_translatable_fields(): void
    {
        $this->assertEquals('Product', $this->product->getTranslation('name', 'en'));
        $this->assertEquals('Description', $this->product->getTranslation('description', 'en'));

        $this->assertEquals('Producto', $this->product->getTranslation('name', 'es'));
        $this->assertEquals('Descripción', $this->product->getTranslation('description', 'es'));
    }

    public function test_it_automatically_retrieves_translation_via_attribute(): void
    {
        App::setLocale('es');
        $this->assertEquals('Producto', $this->product->name);

        App::setLocale('fr');
        $this->assertEquals('Produit', $this->product->name);
    }

    public function test_it_stores_translations_as_json_in_database(): void
    {
        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
        ]);

        $dbProduct = \DB::table('products')->find($this->product->id);
        $nameJson = json_decode($dbProduct->name, true);

        $this->assertIsArray($nameJson);
        $this->assertEquals('Product', $nameJson['en']);
        $this->assertEquals('Producto', $nameJson['es']);
    }

    public function test_it_works_with_mass_assignment(): void
    {
        $product = Product::create([
            'name' => ['en' => 'Mass Assigned', 'es' => 'Asignación Masiva'],
            'description' => ['en' => 'Description', 'es' => 'Descripción'],
            'sku' => 'TEST-SKU',
            'unit_price' => 100.00,
            'cost_price' => 50.00,
            'type' => 'goods',
            'status' => 'active',
        ]);

        $this->assertEquals('Mass Assigned', $product->getTranslation('name', 'en'));
        $this->assertEquals('Asignación Masiva', $product->getTranslation('name', 'es'));
    }
}
