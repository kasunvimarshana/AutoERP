<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private array $defaultProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $response = $this->postJson('/api/v1/tenants', [
            'name' => 'Product Test Tenant',
            'slug' => 'product-test-tenant',
        ]);
        $this->tenantId = $response->json('data.id');

        $this->defaultProduct = [
            'tenant_id' => $this->tenantId,
            'sku' => 'WIDGET-001',
            'name' => 'Blue Widget',
            'description' => 'A small blue widget',
            'type' => 'stockable',
            'uom' => 'pcs',
            'costing_method' => 'fifo',
            'cost_price' => '10.50',
            'sale_price' => '19.99',
        ];
    }

    public function test_can_create_product(): void
    {
        $response = $this->postJson('/api/v1/products', $this->defaultProduct);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id', 'tenant_id', 'sku', 'name', 'description',
                    'type', 'uom', 'buying_uom', 'selling_uom',
                    'costing_method', 'cost_price', 'sale_price',
                    'barcode', 'status', 'created_at',
                ],
                'errors',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sku', 'WIDGET-001')
            ->assertJsonPath('data.name', 'Blue Widget')
            ->assertJsonPath('data.type', 'stockable')
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            // When no buying/selling UOM is provided they fall back to the inventory UOM
            ->assertJsonPath('data.buying_uom', 'pcs')
            ->assertJsonPath('data.selling_uom', 'pcs');
    }

    public function test_can_list_products(): void
    {
        $this->postJson('/api/v1/products', $this->defaultProduct);
        $this->postJson('/api/v1/products', array_merge($this->defaultProduct, ['sku' => 'WIDGET-002', 'name' => 'Red Widget']));

        $response = $this->getJson("/api/v1/products?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message', 'data', 'errors',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_sku_must_be_unique_per_tenant(): void
    {
        $this->postJson('/api/v1/products', $this->defaultProduct);

        $response = $this->postJson('/api/v1/products', array_merge($this->defaultProduct, ['name' => 'Another Widget']));

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_get_product_by_id(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $id = $createResponse->json('data.id');

        $response = $this->getJson("/api/v1/products/{$id}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.sku', 'WIDGET-001');
    }

    public function test_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson("/api/v1/products/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_product(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $id = $createResponse->json('data.id');

        $response = $this->putJson("/api/v1/products/{$id}?tenant_id={$this->tenantId}", [
            'name' => 'Updated Widget',
            'description' => 'Updated description',
            'uom' => 'kg',
            'costing_method' => 'weighted_average',
            'cost_price' => '12.00',
            'sale_price' => '24.99',
            'barcode' => '5901234123457',
            'status' => 'active',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Widget')
            ->assertJsonPath('data.uom', 'kg')
            ->assertJsonPath('data.costing_method', 'weighted_average')
            ->assertJsonPath('data.barcode', '5901234123457');
    }

    public function test_can_delete_product(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $id = $createResponse->json('data.id');

        $response = $this->deleteJson("/api/v1/products/{$id}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/products/{$id}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    public function test_product_type_validation(): void
    {
        $response = $this->postJson('/api/v1/products', array_merge($this->defaultProduct, [
            'type' => 'invalid_type',
        ]));

        $response->assertStatus(422);
    }

    public function test_costing_method_validation(): void
    {
        $response = $this->postJson('/api/v1/products', array_merge($this->defaultProduct, [
            'costing_method' => 'invalid_method',
        ]));

        $response->assertStatus(422);
    }

    public function test_sku_is_normalized_to_uppercase(): void
    {
        $response = $this->postJson('/api/v1/products', array_merge($this->defaultProduct, [
            'sku' => 'widget-lowercase',
        ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.sku', 'WIDGET-LOWERCASE');
    }

    public function test_can_create_product_with_separate_buying_and_selling_uoms(): void
    {
        $response = $this->postJson('/api/v1/products', array_merge($this->defaultProduct, [
            'uom' => 'pcs',
            'buying_uom' => 'box',
            'selling_uom' => 'pack',
        ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.uom', 'pcs')
            ->assertJsonPath('data.buying_uom', 'box')
            ->assertJsonPath('data.selling_uom', 'pack');
    }

    public function test_buying_uom_defaults_to_inventory_uom_when_not_provided(): void
    {
        $response = $this->postJson('/api/v1/products', $this->defaultProduct);

        $response->assertStatus(201)
            ->assertJsonPath('data.uom', 'pcs')
            ->assertJsonPath('data.buying_uom', 'pcs')
            ->assertJsonPath('data.selling_uom', 'pcs');
    }

    public function test_can_set_and_retrieve_uom_conversions(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $response = $this->postJson(
            "/api/v1/products/{$productId}/uom-conversions?tenant_id={$this->tenantId}",
            [
                'conversions' => [
                    ['from_uom' => 'box',   'to_uom' => 'pcs', 'factor' => 12],
                    ['from_uom' => 'dozen', 'to_uom' => 'pcs', 'factor' => 12],
                ],
            ]
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertCount(2, $response->json('data'));
        $this->assertEquals('box', $response->json('data.0.from_uom'));
        $this->assertEquals('pcs', $response->json('data.0.to_uom'));
        $this->assertEquals('12.0000', $response->json('data.0.factor'));
    }

    public function test_can_list_uom_conversions(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $this->postJson(
            "/api/v1/products/{$productId}/uom-conversions?tenant_id={$this->tenantId}",
            ['conversions' => [['from_uom' => 'box', 'to_uom' => 'pcs', 'factor' => 12]]]
        );

        $response = $this->getJson(
            "/api/v1/products/{$productId}/uom-conversions?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_uom_conversions_are_replaced_on_re_post(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        // First set: 2 conversions
        $this->postJson(
            "/api/v1/products/{$productId}/uom-conversions?tenant_id={$this->tenantId}",
            [
                'conversions' => [
                    ['from_uom' => 'box',   'to_uom' => 'pcs', 'factor' => 12],
                    ['from_uom' => 'dozen', 'to_uom' => 'pcs', 'factor' => 12],
                ],
            ]
        );

        // Second set: 1 conversion — should replace the previous 2
        $this->postJson(
            "/api/v1/products/{$productId}/uom-conversions?tenant_id={$this->tenantId}",
            ['conversions' => [['from_uom' => 'carton', 'to_uom' => 'pcs', 'factor' => 24]]]
        );

        $response = $this->getJson(
            "/api/v1/products/{$productId}/uom-conversions?tenant_id={$this->tenantId}"
        );

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('carton', $response->json('data.0.from_uom'));
    }

    public function test_can_convert_quantity_between_uoms(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $this->postJson(
            "/api/v1/products/{$productId}/uom-conversions?tenant_id={$this->tenantId}",
            ['conversions' => [['from_uom' => 'box', 'to_uom' => 'pcs', 'factor' => 12]]]
        );

        $response = $this->getJson(
            "/api/v1/products/{$productId}/uom-conversions/convert"
            ."?tenant_id={$this->tenantId}&quantity=3&from_uom=box&to_uom=pcs"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.output_quantity', '36.0000');
    }

    public function test_convert_returns_same_quantity_when_uoms_are_identical(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $response = $this->getJson(
            "/api/v1/products/{$productId}/uom-conversions/convert"
            ."?tenant_id={$this->tenantId}&quantity=5&from_uom=pcs&to_uom=pcs"
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.output_quantity', '5.0000');
    }

    public function test_can_set_and_retrieve_product_images(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $response = $this->postJson(
            "/api/v1/products/{$productId}/images?tenant_id={$this->tenantId}",
            [
                'images' => [
                    ['image_path' => 'https://cdn.example.com/product-front.jpg', 'alt_text' => 'Front view', 'sort_order' => 0, 'is_primary' => true],
                    ['image_path' => 'https://cdn.example.com/product-back.jpg', 'alt_text' => 'Back view', 'sort_order' => 1, 'is_primary' => false],
                ],
            ]
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertCount(2, $response->json('data'));
        $this->assertEquals('https://cdn.example.com/product-front.jpg', $response->json('data.0.image_path'));
        $this->assertTrue($response->json('data.0.is_primary'));
        $this->assertEquals('Back view', $response->json('data.1.alt_text'));
    }

    public function test_can_list_product_images(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $this->postJson(
            "/api/v1/products/{$productId}/images?tenant_id={$this->tenantId}",
            ['images' => [['image_path' => 'https://cdn.example.com/img1.jpg']]]
        );

        $response = $this->getJson(
            "/api/v1/products/{$productId}/images?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_product_images_are_replaced_on_re_post(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        // First set: 2 images
        $this->postJson(
            "/api/v1/products/{$productId}/images?tenant_id={$this->tenantId}",
            [
                'images' => [
                    ['image_path' => 'https://cdn.example.com/img1.jpg'],
                    ['image_path' => 'https://cdn.example.com/img2.jpg'],
                ],
            ]
        );

        // Second set: 1 image — should replace the previous 2
        $this->postJson(
            "/api/v1/products/{$productId}/images?tenant_id={$this->tenantId}",
            ['images' => [['image_path' => 'https://cdn.example.com/img3.jpg']]]
        );

        $response = $this->getJson(
            "/api/v1/products/{$productId}/images?tenant_id={$this->tenantId}"
        );

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('https://cdn.example.com/img3.jpg', $response->json('data.0.image_path'));
    }

    public function test_can_delete_single_product_image(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $setResponse = $this->postJson(
            "/api/v1/products/{$productId}/images?tenant_id={$this->tenantId}",
            [
                'images' => [
                    ['image_path' => 'https://cdn.example.com/img1.jpg'],
                    ['image_path' => 'https://cdn.example.com/img2.jpg'],
                ],
            ]
        );

        $imageId = $setResponse->json('data.0.id');

        $this->deleteJson(
            "/api/v1/products/{$productId}/images/{$imageId}?tenant_id={$this->tenantId}"
        )->assertStatus(200);

        $listResponse = $this->getJson(
            "/api/v1/products/{$productId}/images?tenant_id={$this->tenantId}"
        );

        $this->assertCount(1, $listResponse->json('data'));
    }

    public function test_can_set_and_retrieve_product_attributes(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $response = $this->postJson(
            "/api/v1/products/{$productId}/attributes?tenant_id={$this->tenantId}",
            [
                'attributes' => [
                    ['attribute_key' => 'colour', 'attribute_label' => 'Colour', 'attribute_value' => 'Blue', 'attribute_type' => 'text', 'sort_order' => 0],
                    ['attribute_key' => 'net_weight_kg', 'attribute_label' => 'Net Weight (kg)', 'attribute_value' => '0.25', 'attribute_type' => 'number', 'sort_order' => 1],
                ],
            ]
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertCount(2, $response->json('data'));
        $this->assertEquals('colour', $response->json('data.0.attribute_key'));
        $this->assertEquals('Blue', $response->json('data.0.attribute_value'));
        $this->assertEquals('number', $response->json('data.1.attribute_type'));
    }

    public function test_can_list_product_attributes(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $this->postJson(
            "/api/v1/products/{$productId}/attributes?tenant_id={$this->tenantId}",
            ['attributes' => [['attribute_key' => 'material', 'attribute_label' => 'Material', 'attribute_value' => 'Plastic']]]
        );

        $response = $this->getJson(
            "/api/v1/products/{$productId}/attributes?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_product_attributes_are_replaced_on_re_post(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        // First set: 2 attributes
        $this->postJson(
            "/api/v1/products/{$productId}/attributes?tenant_id={$this->tenantId}",
            [
                'attributes' => [
                    ['attribute_key' => 'colour', 'attribute_label' => 'Colour', 'attribute_value' => 'Red'],
                    ['attribute_key' => 'size', 'attribute_label' => 'Size', 'attribute_value' => 'M'],
                ],
            ]
        );

        // Second set: 1 attribute — should replace the previous 2
        $this->postJson(
            "/api/v1/products/{$productId}/attributes?tenant_id={$this->tenantId}",
            ['attributes' => [['attribute_key' => 'weight', 'attribute_label' => 'Weight', 'attribute_value' => '500g']]]
        );

        $response = $this->getJson(
            "/api/v1/products/{$productId}/attributes?tenant_id={$this->tenantId}"
        );

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('weight', $response->json('data.0.attribute_key'));
    }

    public function test_can_delete_single_product_attribute(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $setResponse = $this->postJson(
            "/api/v1/products/{$productId}/attributes?tenant_id={$this->tenantId}",
            [
                'attributes' => [
                    ['attribute_key' => 'colour', 'attribute_label' => 'Colour', 'attribute_value' => 'Green'],
                    ['attribute_key' => 'size', 'attribute_label' => 'Size', 'attribute_value' => 'L'],
                ],
            ]
        );

        $attributeId = $setResponse->json('data.0.id');

        $this->deleteJson(
            "/api/v1/products/{$productId}/attributes/{$attributeId}?tenant_id={$this->tenantId}"
        )->assertStatus(200);

        $listResponse = $this->getJson(
            "/api/v1/products/{$productId}/attributes?tenant_id={$this->tenantId}"
        );

        $this->assertCount(1, $listResponse->json('data'));
    }

    public function test_attribute_type_validation_rejects_invalid_type(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $response = $this->postJson(
            "/api/v1/products/{$productId}/attributes?tenant_id={$this->tenantId}",
            [
                'attributes' => [
                    ['attribute_key' => 'colour', 'attribute_label' => 'Colour', 'attribute_value' => 'Blue', 'attribute_type' => 'invalid_type'],
                ],
            ]
        );

        $response->assertStatus(422);
    }

    public function test_images_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson("/api/v1/products/99999/images?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_attributes_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson("/api/v1/products/99999/attributes?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_url_images_have_url_source_type(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $response = $this->postJson(
            "/api/v1/products/{$productId}/images?tenant_id={$this->tenantId}",
            [
                'images' => [
                    ['image_path' => 'https://cdn.example.com/product.jpg', 'alt_text' => 'Front view'],
                ],
            ]
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.0.image_source_type', 'url');
    }

    public function test_can_upload_product_image(): void
    {
        Storage::fake('local');

        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->post(
            "/api/v1/products/{$productId}/images/upload?tenant_id={$this->tenantId}",
            [
                'image' => $file,
                'alt_text' => 'Uploaded front view',
                'sort_order' => 0,
                'is_primary' => true,
            ]
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.image_source_type', 'upload')
            ->assertJsonPath('data.is_primary', true)
            ->assertJsonPath('data.alt_text', 'Uploaded front view')
            ->assertJsonPath('data.product_id', $productId)
            ->assertJsonPath('data.tenant_id', $this->tenantId);

        Storage::disk('local')->assertExists($response->json('data.image_path'));
    }

    public function test_uploaded_image_appears_in_image_list(): void
    {
        Storage::fake('local');

        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $file = UploadedFile::fake()->image('widget.png');

        $this->post(
            "/api/v1/products/{$productId}/images/upload?tenant_id={$this->tenantId}",
            ['image' => $file, 'alt_text' => 'Widget photo']
        );

        $listResponse = $this->getJson(
            "/api/v1/products/{$productId}/images?tenant_id={$this->tenantId}"
        );

        $listResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertCount(1, $listResponse->json('data'));
        $this->assertEquals('upload', $listResponse->json('data.0.image_source_type'));
        $this->assertEquals('Widget photo', $listResponse->json('data.0.alt_text'));
    }

    public function test_upload_returns_404_for_nonexistent_product(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->post(
            "/api/v1/products/99999/images/upload?tenant_id={$this->tenantId}",
            ['image' => $file]
        );

        $response->assertStatus(404)
            ->assertJsonPath('success', false);

        // Uploaded file must be cleaned up on failure.
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_upload_requires_image_file(): void
    {
        $createResponse = $this->postJson('/api/v1/products', $this->defaultProduct);
        $productId = $createResponse->json('data.id');

        $response = $this->post(
            "/api/v1/products/{$productId}/images/upload?tenant_id={$this->tenantId}",
            [],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
    }
}
