<?php

declare(strict_types=1);

namespace Tests\Feature;

use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class InventoryApiValidationTest extends TestCase
{
    public function test_inventory_valuate_requires_minimum_payload(): void
    {
        $response = $this->postJson('/api/inventory/valuate', []);

        $response
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors([
                'item_id',
                'location_id',
                'uom_id',
                'direction',
                'quantity',
            ]);
    }

    public function test_inventory_allocate_requires_minimum_payload(): void
    {
        $response = $this->postJson('/api/inventory/allocate', []);

        $response
            ->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors([
                'item_id',
                'required_quantity',
            ]);
    }
}
