<?php

namespace Tests\Unit;

use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitCreated;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitDeleted;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitMoved;
use Modules\OrganizationUnit\Domain\Events\OrganizationUnitUpdated;
use PHPUnit\Framework\TestCase;

class OrganizationUnitEntityTest extends TestCase
{
    private function createUnit(?string $code = 'ORG001'): OrganizationUnit
    {
        return new OrganizationUnit(
            tenantId: 1,
            name: new Name('IT Department'),
            code: $code !== null ? new Code($code) : null,
        );
    }

    public function test_organization_unit_can_be_created_with_code(): void
    {
        $unit = $this->createUnit('ORG001');

        $this->assertSame('IT Department', $unit->getName()->value());
        $this->assertSame('ORG001', $unit->getCode()->value());
        $this->assertSame(1, $unit->getTenantId());
    }

    public function test_organization_unit_can_be_created_without_code(): void
    {
        $unit = $this->createUnit(null);

        $this->assertNull($unit->getCode());
    }

    public function test_organization_unit_update_details(): void
    {
        $unit = $this->createUnit('OLD001');

        $unit->updateDetails(
            new Name('HR Department'),
            new Code('HR001'),
            'Human Resources',
            null
        );

        $this->assertSame('HR Department', $unit->getName()->value());
        $this->assertSame('HR001', $unit->getCode()->value());
        $this->assertSame('Human Resources', $unit->getDescription());
    }

    public function test_organization_unit_update_details_with_null_code(): void
    {
        $unit = $this->createUnit('OLD001');

        $unit->updateDetails(new Name('Finance'), null, null, null);

        $this->assertNull($unit->getCode());
    }

    public function test_organization_unit_created_event_exists(): void
    {
        $unit = $this->createUnit();
        $event = new OrganizationUnitCreated($unit);
        $this->assertSame($unit, $event->unit);
    }

    public function test_organization_unit_updated_event_exists(): void
    {
        $unit = $this->createUnit();
        $event = new OrganizationUnitUpdated($unit);
        $this->assertSame($unit, $event->unit);
    }

    public function test_organization_unit_deleted_event_exists(): void
    {
        $event = new OrganizationUnitDeleted(42, 1);
        $this->assertSame(42, $event->unitId);
        $this->assertSame(1, $event->tenantId);
    }

    public function test_organization_unit_moved_event_exists(): void
    {
        $unit = $this->createUnit();
        $event = new OrganizationUnitMoved($unit, 5);
        $this->assertSame($unit, $event->unit);
        $this->assertSame(5, $event->previousParentId);
    }

    public function test_add_and_remove_children(): void
    {
        $parent = new OrganizationUnit(tenantId: 1, name: new Name('Parent'));
        $child = new OrganizationUnit(tenantId: 1, name: new Name('Child'));

        $this->assertNull($parent->getCode());

        $parent->addChild($child);
        $this->assertCount(1, $parent->getChildren());

        $parent->removeChild($child);
        $this->assertCount(0, $parent->getChildren());
    }
}
