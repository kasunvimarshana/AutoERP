<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Customization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomizationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        $tenantResponse = $this->postJson('/api/v1/tenants', [
            'name' => 'Customization Test Tenant',
            'slug' => 'customization-test-tenant',
        ]);
        $this->tenantId = $tenantResponse->json('data.id');
    }

    // ─────────────────────────────────────────────
    // Custom Field tests
    // ─────────────────────────────────────────────

    public function test_can_create_custom_field(): void
    {
        $response = $this->postJson('/api/v1/custom-fields', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'product',
            'field_key' => 'color',
            'field_label' => 'Color',
            'field_type' => 'text',
            'is_required' => false,
            'sort_order' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.entity_type', 'product')
            ->assertJsonPath('data.field_key', 'color')
            ->assertJsonPath('data.field_label', 'Color')
            ->assertJsonPath('data.field_type', 'text')
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'entity_type', 'field_key', 'field_label',
                'field_type', 'is_required', 'default_value', 'sort_order',
                'options', 'validation_rules', 'created_at', 'updated_at',
            ]]);
    }

    public function test_field_key_must_be_unique_per_tenant_and_entity_type(): void
    {
        $payload = [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'product',
            'field_key' => 'weight',
            'field_label' => 'Weight',
            'field_type' => 'number',
            'is_required' => false,
            'sort_order' => 1,
        ];

        $this->postJson('/api/v1/custom-fields', $payload)->assertStatus(201);

        $response = $this->postJson('/api/v1/custom-fields', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_list_custom_fields(): void
    {
        $this->postJson('/api/v1/custom-fields', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'product',
            'field_key' => 'field_one',
            'field_label' => 'Field One',
            'field_type' => 'text',
            'sort_order' => 1,
        ])->assertStatus(201);

        $this->postJson('/api/v1/custom-fields', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'product',
            'field_key' => 'field_two',
            'field_label' => 'Field Two',
            'field_type' => 'number',
            'sort_order' => 2,
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/custom-fields?tenant_id={$this->tenantId}&entity_type=product");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_can_get_custom_field_by_id(): void
    {
        $created = $this->postJson('/api/v1/custom-fields', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'customer',
            'field_key' => 'loyalty_level',
            'field_label' => 'Loyalty Level',
            'field_type' => 'select',
            'sort_order' => 1,
        ])->json('data');

        $response = $this->getJson("/api/v1/custom-fields/{$created['id']}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $created['id'])
            ->assertJsonPath('data.field_key', 'loyalty_level');
    }

    public function test_returns_404_for_nonexistent_field(): void
    {
        $response = $this->getJson("/api/v1/custom-fields/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_custom_field(): void
    {
        $created = $this->postJson('/api/v1/custom-fields', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'order',
            'field_key' => 'priority',
            'field_label' => 'Priority',
            'field_type' => 'select',
            'is_required' => false,
            'sort_order' => 1,
        ])->json('data');

        $response = $this->putJson(
            "/api/v1/custom-fields/{$created['id']}?tenant_id={$this->tenantId}",
            [
                'field_label' => 'Order Priority',
                'is_required' => true,
                'sort_order' => 5,
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.field_label', 'Order Priority')
            ->assertJsonPath('data.is_required', true)
            ->assertJsonPath('data.sort_order', 5);
    }

    public function test_can_delete_custom_field(): void
    {
        $created = $this->postJson('/api/v1/custom-fields', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'invoice',
            'field_key' => 'po_number',
            'field_label' => 'PO Number',
            'field_type' => 'text',
            'sort_order' => 1,
        ])->json('data');

        $this->deleteJson("/api/v1/custom-fields/{$created['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/custom-fields/{$created['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    public function test_delete_cascades_to_values(): void
    {
        $field = $this->postJson('/api/v1/custom-fields', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'product',
            'field_key' => 'weight_kg',
            'field_label' => 'Weight (kg)',
            'field_type' => 'number',
            'sort_order' => 1,
        ])->json('data');

        // Set a value for an entity
        $this->postJson('/api/v1/custom-field-values', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'product',
            'entity_id' => 1,
            'values' => [
                ['field_id' => $field['id'], 'value' => '2.5'],
            ],
        ])->assertStatus(200);

        // Verify value exists
        $before = $this->getJson("/api/v1/custom-field-values?tenant_id={$this->tenantId}&entity_type=product&entity_id=1");
        $before->assertStatus(200);
        $this->assertCount(1, $before->json('data'));

        // Delete the field
        $this->deleteJson("/api/v1/custom-fields/{$field['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(200);

        // Values should be gone
        $after = $this->getJson("/api/v1/custom-field-values?tenant_id={$this->tenantId}&entity_type=product&entity_id=1");
        $after->assertStatus(200);
        $this->assertCount(0, $after->json('data'));
    }

    // ─────────────────────────────────────────────
    // Custom Field Value tests
    // ─────────────────────────────────────────────

    public function test_can_set_custom_field_values(): void
    {
        $field = $this->postJson('/api/v1/custom-fields', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'contact',
            'field_key' => 'birthday',
            'field_label' => 'Birthday',
            'field_type' => 'date',
            'sort_order' => 1,
        ])->json('data');

        $response = $this->postJson('/api/v1/custom-field-values', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'contact',
            'entity_id' => 10,
            'values' => [
                ['field_id' => $field['id'], 'value' => '1990-05-15'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.field_id', $field['id'])
            ->assertJsonPath('data.0.value', '1990-05-15');
    }

    public function test_invalid_field_id_is_rejected(): void
    {
        $this->postJson('/api/v1/custom-fields', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'lead',
            'field_key' => 'source',
            'field_label' => 'Source',
            'field_type' => 'text',
            'sort_order' => 1,
        ])->assertStatus(201);

        $response = $this->postJson('/api/v1/custom-field-values', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'lead',
            'entity_id' => 5,
            'values' => [
                ['field_id' => 99999, 'value' => 'some value'],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_get_custom_field_values_for_entity(): void
    {
        $fieldA = $this->postJson('/api/v1/custom-fields', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'deal',
            'field_key' => 'deal_source',
            'field_label' => 'Deal Source',
            'field_type' => 'text',
            'sort_order' => 1,
        ])->json('data');

        $fieldB = $this->postJson('/api/v1/custom-fields', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'deal',
            'field_key' => 'close_probability',
            'field_label' => 'Close Probability',
            'field_type' => 'number',
            'sort_order' => 2,
        ])->json('data');

        $this->postJson('/api/v1/custom-field-values', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'deal',
            'entity_id' => 7,
            'values' => [
                ['field_id' => $fieldA['id'], 'value' => 'Referral'],
                ['field_id' => $fieldB['id'], 'value' => '75'],
            ],
        ])->assertStatus(200);

        $response = $this->getJson("/api/v1/custom-field-values?tenant_id={$this->tenantId}&entity_type=deal&entity_id=7");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'tenant_id', 'entity_type', 'entity_id', 'field_id', 'value']]]);
    }

    public function test_field_type_validation_rejects_unknown_types(): void
    {
        $response = $this->postJson('/api/v1/custom-fields', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'product',
            'field_key' => 'bad_field',
            'field_label' => 'Bad Field',
            'field_type' => 'unknown_type',
            'sort_order' => 1,
        ]);

        $response->assertStatus(422);
    }
}
