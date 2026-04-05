<?php
declare(strict_types=1);
namespace Tests\Unit;

use Modules\OrgUnit\Application\Services\OrgUnitService;
use Modules\OrgUnit\Domain\Entities\OrgUnit;
use Modules\OrgUnit\Domain\Exceptions\OrgUnitCircularReferenceException;
use Modules\OrgUnit\Domain\Exceptions\OrgUnitNotFoundException;
use Modules\OrgUnit\Domain\RepositoryInterfaces\OrgUnitRepositoryInterface;
use PHPUnit\Framework\TestCase;

class OrgUnitModuleTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────

    private function makeUnit(
        ?int    $id       = 1,
        ?int    $parentId = null,
        string  $type     = OrgUnit::TYPE_DEPARTMENT,
        string  $code     = 'DEPT-001',
        string  $name     = 'Engineering',
        int     $level    = 0,
        string  $path     = '/1/',
        bool    $active   = true,
    ): OrgUnit {
        return new OrgUnit(
            $id, 1, $parentId, $type, $code, $name, 'Engineering dept',
            null, $level, $path, $active, null, null,
        );
    }

    // ── Entity tests ──────────────────────────────────────────────────────

    public function testOrgUnitCreation(): void
    {
        $u = $this->makeUnit();
        $this->assertSame(1, $u->getId());
        $this->assertSame('Engineering', $u->getName());
        $this->assertSame(OrgUnit::TYPE_DEPARTMENT, $u->getType());
        $this->assertSame('DEPT-001', $u->getCode());
        $this->assertSame(0, $u->getLevel());
        $this->assertSame('/1/', $u->getPath());
        $this->assertTrue($u->isActive());
        $this->assertTrue($u->isRoot());
    }

    public function testOrgUnitWithParent(): void
    {
        $u = $this->makeUnit(id: 5, parentId: 1, level: 1, path: '/1/5/');
        $this->assertSame(1, $u->getParentId());
        $this->assertSame(1, $u->getLevel());
        $this->assertFalse($u->isRoot());
    }

    public function testActivateDeactivate(): void
    {
        $u = $this->makeUnit(active: false);
        $this->assertFalse($u->isActive());
        $u->activate();
        $this->assertTrue($u->isActive());
        $u->deactivate();
        $this->assertFalse($u->isActive());
    }

    public function testUpdate(): void
    {
        $u = $this->makeUnit();
        $u->update(OrgUnit::TYPE_TEAM, 'TEAM-001', 'Backend Team', 'Backend engineers', 42);
        $this->assertSame(OrgUnit::TYPE_TEAM, $u->getType());
        $this->assertSame('TEAM-001', $u->getCode());
        $this->assertSame('Backend Team', $u->getName());
        $this->assertSame(42, $u->getManagerId());
    }

    public function testInitializePath(): void
    {
        $u = $this->makeUnit(id: 10, parentId: null, path: '/');
        $u->initializePath('/');
        $this->assertSame('/10/', $u->getPath());
    }

    public function testInitializePathWithParent(): void
    {
        $u = $this->makeUnit(id: 15, parentId: 10, path: '/');
        $u->initializePath('/10/');
        $this->assertSame('/10/15/', $u->getPath());
    }

    public function testMoveTo(): void
    {
        $u = $this->makeUnit(id: 5, parentId: null, level: 0, path: '/5/');
        $u->moveTo(1, '/1/', 0);
        $this->assertSame(1, $u->getParentId());
        $this->assertSame(1, $u->getLevel());
        $this->assertSame('/1/5/', $u->getPath());
    }

    public function testMoveToRoot(): void
    {
        $u = $this->makeUnit(id: 5, parentId: 1, level: 1, path: '/1/5/');
        $u->moveTo(null, '/', -1);
        $this->assertNull($u->getParentId());
        $this->assertSame(0, $u->getLevel());
        $this->assertSame('/5/', $u->getPath());
    }

    public function testValidTypes(): void
    {
        $expected = [
            'company', 'division', 'business_unit', 'department',
            'team', 'branch', 'site', 'other',
        ];
        $this->assertSame($expected, OrgUnit::VALID_TYPES);
    }

    // ── Exception tests ───────────────────────────────────────────────────

    public function testOrgUnitNotFoundException(): void
    {
        $e = new OrgUnitNotFoundException(99);
        $this->assertStringContainsString('99', $e->getMessage());
    }

    public function testCircularReferenceException(): void
    {
        $e = new OrgUnitCircularReferenceException(5, 10);
        $this->assertStringContainsString('5', $e->getMessage());
        $this->assertStringContainsString('10', $e->getMessage());
    }

    // ── Service + repository mock tests ──────────────────────────────────

    private function mockRepo(array $methods = []): OrgUnitRepositoryInterface
    {
        $repo = $this->createMock(OrgUnitRepositoryInterface::class);
        foreach ($methods as $method => $return) {
            $repo->method($method)->willReturn($return);
        }
        return $repo;
    }

    public function testFindByIdThrowsWhenNotFound(): void
    {
        $repo    = $this->mockRepo(['findById' => null]);
        $service = new OrgUnitService($repo);
        $this->expectException(OrgUnitNotFoundException::class);
        $service->findById(999);
    }

    public function testFindByIdReturnsUnit(): void
    {
        $unit    = $this->makeUnit();
        $repo    = $this->mockRepo(['findById' => $unit]);
        $service = new OrgUnitService($repo);
        $this->assertSame($unit, $service->findById(1));
    }

    public function testFindAllByTenant(): void
    {
        $units   = [$this->makeUnit(1), $this->makeUnit(2, null, OrgUnit::TYPE_TEAM, 'T1', 'Team A', 0, '/2/')];
        $repo    = $this->mockRepo(['findAllByTenant' => $units]);
        $service = new OrgUnitService($repo);
        $this->assertCount(2, $service->findAllByTenant(1));
    }

    public function testGetTreeOrdersByPath(): void
    {
        $root  = $this->makeUnit(1, null, OrgUnit::TYPE_COMPANY, 'CO', 'Corp', 0, '/1/');
        $child = $this->makeUnit(2, 1, OrgUnit::TYPE_DEPARTMENT, 'D1', 'Dept', 1, '/1/2/');
        $repo  = $this->mockRepo(['findAllByTenant' => [$child, $root]]);
        $service = new OrgUnitService($repo);
        $tree    = $service->getTree(1);
        // After sorting by path, root (/1/) comes before child (/1/2/)
        $this->assertSame('/1/', $tree[0]->getPath());
        $this->assertSame('/1/2/', $tree[1]->getPath());
    }

    public function testGetChildren(): void
    {
        $child = $this->makeUnit(2, 1, OrgUnit::TYPE_TEAM, 'T1', 'Team', 1, '/1/2/');
        $repo  = $this->mockRepo(['findChildren' => [$child]]);
        $service = new OrgUnitService($repo);
        $this->assertCount(1, $service->getChildren(1, 1));
    }

    public function testGetDescendants(): void
    {
        $root = $this->makeUnit(1, null, OrgUnit::TYPE_COMPANY, 'CO', 'Corp', 0, '/1/');
        $d1   = $this->makeUnit(2, 1, OrgUnit::TYPE_DEPARTMENT, 'D1', 'Dept', 1, '/1/2/');
        $d2   = $this->makeUnit(3, 2, OrgUnit::TYPE_TEAM, 'T1', 'Team', 2, '/1/2/3/');

        $repo = $this->createMock(OrgUnitRepositoryInterface::class);
        $repo->method('findById')->willReturn($root);
        $repo->method('findDescendants')->willReturn([$d1, $d2]);

        $service = new OrgUnitService($repo);
        $this->assertCount(2, $service->getDescendants(1));
    }

    public function testGetAncestors(): void
    {
        $leaf = $this->makeUnit(3, 2, OrgUnit::TYPE_TEAM, 'T1', 'Team', 2, '/1/2/3/');
        $p1   = $this->makeUnit(1, null, OrgUnit::TYPE_COMPANY, 'CO', 'Corp', 0, '/1/');
        $p2   = $this->makeUnit(2, 1, OrgUnit::TYPE_DEPARTMENT, 'D1', 'Dept', 1, '/1/2/');

        $repo = $this->createMock(OrgUnitRepositoryInterface::class);
        $repo->method('findById')->willReturn($leaf);
        $repo->method('findAncestors')->willReturn([$p1, $p2]);

        $service = new OrgUnitService($repo);
        $this->assertCount(2, $service->getAncestors(3));
    }

    public function testCreateRootUnit(): void
    {
        $created = $this->makeUnit(10, null, OrgUnit::TYPE_COMPANY, 'CO', 'Corp', 0, '/');
        $saved   = $this->makeUnit(10, null, OrgUnit::TYPE_COMPANY, 'CO', 'Corp', 0, '/10/');

        $repo = $this->createMock(OrgUnitRepositoryInterface::class);
        $repo->method('create')->willReturn($created);
        $repo->method('update')->willReturn($saved);

        $service = new OrgUnitService($repo);
        $result  = $service->create(['tenant_id' => 1, 'type' => 'company', 'code' => 'CO', 'name' => 'Corp']);
        $this->assertSame('/10/', $result->getPath());
    }

    public function testCreateChildUnit(): void
    {
        $parent  = $this->makeUnit(1, null, OrgUnit::TYPE_COMPANY, 'CO', 'Corp', 0, '/1/');
        $created = $this->makeUnit(5, 1, OrgUnit::TYPE_DEPARTMENT, 'D1', 'Dept', 0, '/');
        $saved   = $this->makeUnit(5, 1, OrgUnit::TYPE_DEPARTMENT, 'D1', 'Dept', 1, '/1/5/');

        $repo = $this->createMock(OrgUnitRepositoryInterface::class);
        $repo->method('findById')->willReturn($parent);
        $repo->method('create')->willReturn($created);
        $repo->method('update')->willReturn($saved);

        $service = new OrgUnitService($repo);
        $result  = $service->create(['tenant_id' => 1, 'parent_id' => 1, 'type' => 'department', 'code' => 'D1', 'name' => 'Dept']);
        $this->assertSame('/1/5/', $result->getPath());
        $this->assertSame(1, $result->getLevel());
    }

    public function testUpdateUnit(): void
    {
        $existing = $this->makeUnit();
        $updated  = $this->makeUnit(code: 'DEPT-002', name: 'DevOps');

        $repo = $this->createMock(OrgUnitRepositoryInterface::class);
        $repo->method('findById')->willReturn($existing);
        $repo->method('update')->willReturn($updated);

        $service = new OrgUnitService($repo);
        $result  = $service->update(1, ['code' => 'DEPT-002', 'name' => 'DevOps']);
        $this->assertSame('DevOps', $result->getName());
    }

    public function testDeleteUnit(): void
    {
        $unit = $this->makeUnit();
        $repo = $this->createMock(OrgUnitRepositoryInterface::class);
        $repo->method('findById')->willReturn($unit);
        $repo->expects($this->once())->method('delete')->with(1)->willReturn(true);

        $service = new OrgUnitService($repo);
        $service->delete(1);   // should not throw
        $this->assertTrue(true);
    }

    public function testDeleteThrowsWhenNotFound(): void
    {
        $repo = $this->mockRepo(['findById' => null]);
        $service = new OrgUnitService($repo);
        $this->expectException(OrgUnitNotFoundException::class);
        $service->delete(99);
    }

    public function testActivateUnit(): void
    {
        $inactive = $this->makeUnit(active: false);
        $active   = $this->makeUnit(active: true);

        $repo = $this->createMock(OrgUnitRepositoryInterface::class);
        $repo->method('findById')->willReturn($inactive);
        $repo->method('update')->willReturn($active);

        $service = new OrgUnitService($repo);
        $result  = $service->activate(1);
        $this->assertTrue($result->isActive());
    }

    public function testDeactivateUnit(): void
    {
        $active   = $this->makeUnit(active: true);
        $inactive = $this->makeUnit(active: false);

        $repo = $this->createMock(OrgUnitRepositoryInterface::class);
        $repo->method('findById')->willReturn($active);
        $repo->method('update')->willReturn($inactive);

        $service = new OrgUnitService($repo);
        $result  = $service->deactivate(1);
        $this->assertFalse($result->isActive());
    }

    public function testMoveUnit(): void
    {
        $unit      = $this->makeUnit(id: 5, parentId: null, level: 0, path: '/5/');
        $newParent = $this->makeUnit(id: 1, parentId: null, level: 0, path: '/1/');
        $moved     = $this->makeUnit(id: 5, parentId: 1, level: 1, path: '/1/5/');

        $repo = $this->createMock(OrgUnitRepositoryInterface::class);
        $repo->method('findById')->willReturnOnConsecutiveCalls($unit, $newParent, $newParent);
        $repo->method('update')->willReturn($moved);
        $repo->expects($this->once())->method('updateDescendantPaths');

        $service = new OrgUnitService($repo);
        $result  = $service->move(5, 1);
        $this->assertSame(1, $result->getParentId());
    }

    public function testMoveUnitToRoot(): void
    {
        $unit  = $this->makeUnit(id: 5, parentId: 1, level: 1, path: '/1/5/');
        $moved = $this->makeUnit(id: 5, parentId: null, level: 0, path: '/5/');

        $repo = $this->createMock(OrgUnitRepositoryInterface::class);
        $repo->method('findById')->willReturn($unit);
        $repo->method('update')->willReturn($moved);
        $repo->expects($this->once())->method('updateDescendantPaths');

        $service = new OrgUnitService($repo);
        $result  = $service->move(5, null);
        $this->assertNull($result->getParentId());
    }

    public function testMoveDetectsSelfCircularReference(): void
    {
        $unit = $this->makeUnit(id: 5, parentId: null, level: 0, path: '/5/');
        $repo = $this->createMock(OrgUnitRepositoryInterface::class);
        $repo->method('findById')->willReturn($unit);

        $service = new OrgUnitService($repo);
        $this->expectException(OrgUnitCircularReferenceException::class);
        $service->move(5, 5);  // moving unit under itself
    }

    public function testMoveDetectsDescendantCircularReference(): void
    {
        // unit 5 has path /5/, new parent (10) has path /5/10/ — 10 is a descendant of 5
        $unit      = $this->makeUnit(id: 5, parentId: null, level: 0, path: '/5/');
        $newParent = $this->makeUnit(id: 10, parentId: 5, level: 1, path: '/5/10/');

        $repo = $this->createMock(OrgUnitRepositoryInterface::class);
        $repo->method('findById')->willReturnOnConsecutiveCalls($unit, $newParent);

        $service = new OrgUnitService($repo);
        $this->expectException(OrgUnitCircularReferenceException::class);
        $service->move(5, 10);
    }

    // ── Repository interface contract tests ──────────────────────────────

    public function testRepositoryInterfaceMethods(): void
    {
        $methods = get_class_methods(OrgUnitRepositoryInterface::class);
        $expected = [
            'findById', 'findRoots', 'findChildren', 'findDescendants',
            'findAncestors', 'findAllByTenant', 'create', 'update', 'delete',
            'updateDescendantPaths', 'existsByCode',
        ];
        foreach ($expected as $m) {
            $this->assertContains($m, $methods, "Missing method: {$m}");
        }
    }

    // ── Type constant tests ───────────────────────────────────────────────

    public function testAllTypeConstantsDefined(): void
    {
        $this->assertSame('company',       OrgUnit::TYPE_COMPANY);
        $this->assertSame('division',      OrgUnit::TYPE_DIVISION);
        $this->assertSame('business_unit', OrgUnit::TYPE_BUSINESS_UNIT);
        $this->assertSame('department',    OrgUnit::TYPE_DEPARTMENT);
        $this->assertSame('team',          OrgUnit::TYPE_TEAM);
        $this->assertSame('branch',        OrgUnit::TYPE_BRANCH);
        $this->assertSame('site',          OrgUnit::TYPE_SITE);
        $this->assertSame('other',         OrgUnit::TYPE_OTHER);
    }

    public function testDeepHierarchyPathBuilding(): void
    {
        // Simulate a 4-level hierarchy: Corp / Div / Dept / Team
        $corp = $this->makeUnit(1, null, OrgUnit::TYPE_COMPANY, 'CO', 'Corp', 0, '/1/');
        $div  = $this->makeUnit(2, 1, OrgUnit::TYPE_DIVISION, 'DV', 'Div', 1, '/1/2/');
        $dept = $this->makeUnit(3, 2, OrgUnit::TYPE_DEPARTMENT, 'DP', 'Dept', 2, '/1/2/3/');
        $team = $this->makeUnit(4, 3, OrgUnit::TYPE_TEAM, 'TM', 'Team', 3, '/1/2/3/4/');

        $this->assertSame(0, $corp->getLevel());
        $this->assertSame(1, $div->getLevel());
        $this->assertSame(2, $dept->getLevel());
        $this->assertSame(3, $team->getLevel());

        // Check path includes ancestor IDs
        $this->assertStringContainsString('/1/', $team->getPath());
        $this->assertStringContainsString('/2/', $team->getPath());
        $this->assertStringContainsString('/3/', $team->getPath());
    }
}
