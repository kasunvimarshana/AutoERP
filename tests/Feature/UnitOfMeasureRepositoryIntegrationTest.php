<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Product\Domain\Entities\UnitOfMeasure;
use Modules\Product\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Tests\TestCase;

class UnitOfMeasureRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenants();
    }

    public function test_save_creates_and_updates_unit_of_measure(): void
    {
        /** @var UnitOfMeasureRepositoryInterface $repository */
        $repository = app(UnitOfMeasureRepositoryInterface::class);

        $created = $repository->save(new UnitOfMeasure(
            tenantId: 11,
            name: 'Each',
            symbol: 'EA',
            type: 'unit',
            isBase: true,
        ));

        $this->assertNotNull($created->getId());
        $this->assertSame('Each', $created->getName());

        $updated = $repository->save(new UnitOfMeasure(
            id: $created->getId(),
            tenantId: 11,
            name: 'Each Updated',
            symbol: 'EA',
            type: 'unit',
            isBase: true,
        ));

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertSame('Each Updated', $updated->getName());
    }

    public function test_find_by_tenant_and_symbol_returns_domain_entity(): void
    {
        $this->insertUnitRow(id: 821, tenantId: 21, name: 'Each', symbol: 'EA');
        $this->insertUnitRow(id: 822, tenantId: 22, name: 'Each Other', symbol: 'EA');

        /** @var UnitOfMeasureRepositoryInterface $repository */
        $repository = app(UnitOfMeasureRepositoryInterface::class);

        $found = $repository->findByTenantAndSymbol(21, 'EA');

        $this->assertInstanceOf(UnitOfMeasure::class, $found);
        $this->assertSame(821, $found->getId());
        $this->assertSame(21, $found->getTenantId());
    }

    public function test_paginate_and_where_return_mapped_domain_entities(): void
    {
        $this->insertUnitRow(id: 921, tenantId: 31, name: 'Centimeter', symbol: 'CM');
        $this->insertUnitRow(id: 922, tenantId: 31, name: 'Byte', symbol: 'BY');
        $this->insertUnitRow(id: 923, tenantId: 32, name: 'XUnit', symbol: 'XU');

        /** @var UnitOfMeasureRepositoryInterface $repository */
        $repository = app(UnitOfMeasureRepositoryInterface::class);

        $paginator = $repository
            ->resetCriteria()
            ->where('tenant_id', 31)
            ->orderBy('name', 'asc')
            ->paginate(15, ['*'], 'page', 1);

        $items = $paginator->items();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(UnitOfMeasure::class, $items[0]);
        $this->assertSame('Byte', $items[0]->getName());
        $this->assertSame('Centimeter', $items[1]->getName());

        $collection = $repository
            ->resetCriteria()
            ->where('tenant_id', 31)
            ->get();

        $this->assertCount(2, $collection);
        $this->assertContainsOnlyInstancesOf(UnitOfMeasure::class, $collection);
    }

    private function insertUnitRow(int $id, int $tenantId, string $name, string $symbol): void
    {
        DB::table('units_of_measure')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'name' => $name,
            'symbol' => $symbol,
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTenants(): void
    {
        foreach ([11, 21, 22, 31, 32] as $tenantId) {
            DB::table('tenants')->insert([
                'id' => $tenantId,
                'name' => 'Tenant '.$tenantId,
                'slug' => 'tenant-'.$tenantId,
                'domain' => null,
                'logo_path' => null,
                'database_config' => null,
                'mail_config' => null,
                'cache_config' => null,
                'queue_config' => null,
                'feature_flags' => null,
                'api_keys' => null,
                'settings' => null,
                'plan' => 'free',
                'tenant_plan_id' => null,
                'status' => 'active',
                'active' => true,
                'trial_ends_at' => null,
                'subscription_ends_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        }
    }
}
