<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class PartyManagementRoutesTest extends TestCase
{
    public function test_parties_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/parties');
        $response->assertStatus(401);
    }

    public function test_parties_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/parties', []);
        $response->assertStatus(401);
    }

    public function test_parties_show_requires_authentication(): void
    {
        $response = $this->getJson('/api/parties/' . fake()->uuid());
        $response->assertStatus(401);
    }

    public function test_parties_update_requires_authentication(): void
    {
        $response = $this->putJson('/api/parties/' . fake()->uuid(), []);
        $response->assertStatus(401);
    }

    public function test_parties_destroy_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/parties/' . fake()->uuid());
        $response->assertStatus(401);
    }

    public function test_asset_ownerships_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/asset-ownerships');
        $response->assertStatus(401);
    }

    public function test_asset_ownerships_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/asset-ownerships', []);
        $response->assertStatus(401);
    }

    public function test_asset_ownerships_show_requires_authentication(): void
    {
        $response = $this->getJson('/api/asset-ownerships/' . fake()->uuid());
        $response->assertStatus(401);
    }

    public function test_party_ownerships_requires_authentication(): void
    {
        $response = $this->getJson('/api/parties/' . fake()->uuid() . '/ownerships');
        $response->assertStatus(401);
    }

    public function test_asset_ownerships_by_asset_requires_authentication(): void
    {
        $response = $this->getJson('/api/assets/' . fake()->uuid() . '/ownerships');
        $response->assertStatus(401);
    }
}
