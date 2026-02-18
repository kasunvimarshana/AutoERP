<?php

declare(strict_types=1);

namespace Modules\Organization\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Organization\Entities\Organization;
use Modules\Organization\Services\OrganizationHierarchyService;
use Modules\Tenancy\Entities\Tenant;
use Tests\TestCase;

class OrganizationHierarchyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrganizationHierarchyService $service;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrganizationHierarchyService::class);
        
        // Create a test tenant
        $this->tenant = Tenant::factory()->create();
        session(['tenant_id' => $this->tenant->id]);
    }

    public function test_it_gets_ancestors_including_self(): void
    {
        // Create hierarchy: Root -> Parent -> Child
        $root = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
            'name' => ['en' => 'Root'],
        ]);

        $parent = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root->id,
            'name' => ['en' => 'Parent'],
        ]);

        $child = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $parent->id,
            'name' => ['en' => 'Child'],
        ]);

        $ancestors = $this->service->getAncestorsIncludingSelf($child->id);

        $this->assertCount(3, $ancestors);
        $this->assertTrue($ancestors->contains('id', $root->id));
        $this->assertTrue($ancestors->contains('id', $parent->id));
        $this->assertTrue($ancestors->contains('id', $child->id));
    }

    public function test_it_gets_ancestors_excluding_self(): void
    {
        // Create hierarchy: Root -> Parent -> Child
        $root = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $parent = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root->id,
        ]);

        $child = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $parent->id,
        ]);

        $ancestors = $this->service->getAncestors($child->id);

        $this->assertCount(2, $ancestors);
        $this->assertTrue($ancestors->contains('id', $root->id));
        $this->assertTrue($ancestors->contains('id', $parent->id));
        $this->assertFalse($ancestors->contains('id', $child->id));
    }

    public function test_it_gets_descendants_including_self(): void
    {
        // Create hierarchy
        $root = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $child1 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root->id,
        ]);

        $child2 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root->id,
        ]);

        $grandchild = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $child1->id,
        ]);

        $descendants = $this->service->getDescendantsIncludingSelf($root->id);

        $this->assertCount(4, $descendants);
        $this->assertTrue($descendants->contains('id', $root->id));
        $this->assertTrue($descendants->contains('id', $child1->id));
        $this->assertTrue($descendants->contains('id', $child2->id));
        $this->assertTrue($descendants->contains('id', $grandchild->id));
    }

    public function test_it_gets_immediate_children_only(): void
    {
        $root = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $child1 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root->id,
        ]);

        $child2 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root->id,
        ]);

        $grandchild = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $child1->id,
        ]);

        $children = $this->service->getChildren($root->id);

        $this->assertCount(2, $children);
        $this->assertTrue($children->contains('id', $child1->id));
        $this->assertTrue($children->contains('id', $child2->id));
        $this->assertFalse($children->contains('id', $grandchild->id));
    }

    public function test_it_gets_siblings(): void
    {
        $root = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $sibling1 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root->id,
            'name' => ['en' => 'Sibling 1'],
        ]);

        $sibling2 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root->id,
            'name' => ['en' => 'Sibling 2'],
        ]);

        $sibling3 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root->id,
            'name' => ['en' => 'Sibling 3'],
        ]);

        // Get siblings excluding self
        $siblings = $this->service->getSiblings($sibling2->id, false);

        $this->assertCount(2, $siblings);
        $this->assertTrue($siblings->contains('id', $sibling1->id));
        $this->assertFalse($siblings->contains('id', $sibling2->id));
        $this->assertTrue($siblings->contains('id', $sibling3->id));

        // Get siblings including self
        $siblingsWithSelf = $this->service->getSiblings($sibling2->id, true);
        $this->assertCount(3, $siblingsWithSelf);
        $this->assertTrue($siblingsWithSelf->contains('id', $sibling2->id));
    }

    public function test_it_checks_if_in_subtree(): void
    {
        $root = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $child = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root->id,
        ]);

        $grandchild = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $child->id,
        ]);

        $this->assertTrue($this->service->isInSubtree($child->id, $root->id));
        $this->assertTrue($this->service->isInSubtree($grandchild->id, $root->id));
        $this->assertTrue($this->service->isInSubtree($grandchild->id, $child->id));
        $this->assertFalse($this->service->isInSubtree($root->id, $child->id));
    }

    public function test_it_checks_if_in_same_tree(): void
    {
        $root1 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $child1 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root1->id,
        ]);

        $root2 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $child2 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root2->id,
        ]);

        $this->assertTrue($this->service->isInSameTree($root1->id, $child1->id));
        $this->assertTrue($this->service->isInSameTree($root2->id, $child2->id));
        $this->assertFalse($this->service->isInSameTree($child1->id, $child2->id));
    }

    public function test_it_prevents_circular_reference_on_move(): void
    {
        $root = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $child = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root->id,
        ]);

        $grandchild = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $child->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('circular reference');

        // Try to move root to be child of grandchild (would create circle)
        $this->service->moveOrganization($root->id, $grandchild->id);
    }

    public function test_it_moves_organization_successfully(): void
    {
        $root = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $branch1 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root->id,
        ]);

        $branch2 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root->id,
        ]);

        $child = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $branch1->id,
        ]);

        // Move child from branch1 to branch2
        $moved = $this->service->moveOrganization($child->id, $branch2->id);

        $this->assertEquals($branch2->id, $moved->parent_id);
        
        // Verify hierarchy is updated
        $moved = $moved->fresh();
        $this->assertEquals($branch2->id, $moved->parent_id);
    }

    public function test_it_clears_hierarchy_cache(): void
    {
        $root = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        // Populate cache
        $this->service->getDescendantsIncludingSelf($root->id);
        $this->assertTrue(Cache::has("org_descendants_incl_{$root->id}"));

        // Clear cache
        $this->service->clearHierarchyCache($root->id);

        $this->assertFalse(Cache::has("org_descendants_incl_{$root->id}"));
    }

    public function test_it_gets_organization_breadcrumb(): void
    {
        $root = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
            'code' => 'ROOT',
            'name' => ['en' => 'Root Organization'],
        ]);

        $child = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root->id,
            'code' => 'CHILD',
            'name' => ['en' => 'Child Organization'],
        ]);

        $grandchild = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $child->id,
            'code' => 'GRAND',
            'name' => ['en' => 'Grandchild Organization'],
        ]);

        $breadcrumb = $this->service->getBreadcrumb($grandchild->id);

        $this->assertCount(3, $breadcrumb);
        $this->assertEquals('ROOT', $breadcrumb[0]['code']);
        $this->assertEquals('CHILD', $breadcrumb[1]['code']);
        $this->assertEquals('GRAND', $breadcrumb[2]['code']);
    }

    public function test_it_gets_root_organizations(): void
    {
        $root1 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $root2 = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $child = Organization::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $root1->id,
        ]);

        $roots = $this->service->getRootOrganizations($this->tenant->id);

        $this->assertCount(2, $roots);
        $this->assertTrue($roots->contains('id', $root1->id));
        $this->assertTrue($roots->contains('id', $root2->id));
        $this->assertFalse($roots->contains('id', $child->id));
    }
}
