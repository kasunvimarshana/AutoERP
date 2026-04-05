<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Modules\Configuration\Domain\Entities\Setting;
use Modules\Configuration\Domain\Entities\OrgUnit;
use Modules\Configuration\Domain\Exceptions\SettingNotFoundException;
use Modules\Configuration\Domain\Exceptions\OrgUnitNotFoundException;
use Modules\Configuration\Domain\RepositoryInterfaces\SettingRepositoryInterface;
use Modules\Configuration\Domain\RepositoryInterfaces\OrgUnitRepositoryInterface;
use Modules\Configuration\Application\Services\SettingService;
use Modules\Configuration\Application\Services\OrgUnitService;

class ConfigurationModuleTest extends TestCase
{
    private function makeSetting(): Setting
    {
        return new Setting(1, 1, 'site_name', 'My ERP', 'string', null, new \DateTime(), new \DateTime());
    }

    private function makeOrgUnit(int $id = 1, ?int $parentId = null): OrgUnit
    {
        return new OrgUnit($id, 1, $parentId, 'Head Office', 'HO', 'company', 0, true, new \DateTime(), new \DateTime());
    }

    public function test_setting_entity_getters(): void
    {
        $setting = $this->makeSetting();
        $this->assertEquals(1, $setting->getId());
        $this->assertEquals(1, $setting->getTenantId());
        $this->assertEquals('site_name', $setting->getKey());
        $this->assertEquals('My ERP', $setting->getValue());
        $this->assertEquals('string', $setting->getType());
    }

    public function test_setting_update_value(): void
    {
        $setting = $this->makeSetting();
        $setting->updateValue('New ERP');
        $this->assertEquals('New ERP', $setting->getValue());
    }

    public function test_org_unit_entity_getters(): void
    {
        $unit = $this->makeOrgUnit();
        $this->assertEquals(1, $unit->getId());
        $this->assertEquals(1, $unit->getTenantId());
        $this->assertNull($unit->getParentId());
        $this->assertEquals('Head Office', $unit->getName());
        $this->assertEquals('HO', $unit->getCode());
        $this->assertTrue($unit->isActive());
    }

    public function test_org_unit_activate_deactivate(): void
    {
        $unit = $this->makeOrgUnit();
        $unit->deactivate();
        $this->assertFalse($unit->isActive());
        $unit->activate();
        $this->assertTrue($unit->isActive());
    }

    public function test_org_unit_rename(): void
    {
        $unit = $this->makeOrgUnit();
        $unit->rename('New HQ');
        $this->assertEquals('New HQ', $unit->getName());
    }

    public function test_org_unit_change_parent(): void
    {
        $unit = $this->makeOrgUnit();
        $unit->changeParent(5);
        $this->assertEquals(5, $unit->getParentId());
    }

    public function test_setting_service_throws_not_found(): void
    {
        /** @var SettingRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(SettingRepositoryInterface::class);
        $repo->method('findByKey')->willReturn(null);

        $service = new SettingService($repo);
        $this->expectException(SettingNotFoundException::class);
        $service->get(1, 'non_existent_key');
    }

    public function test_org_unit_service_throws_not_found(): void
    {
        /** @var OrgUnitRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(OrgUnitRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new OrgUnitService($repo);
        $this->expectException(OrgUnitNotFoundException::class);
        $service->findById(999);
    }

    public function test_setting_not_found_exception_message(): void
    {
        $e = new SettingNotFoundException('my_key');
        $this->assertStringContainsString('my_key', $e->getMessage());
        $this->assertStringContainsString('Setting', $e->getMessage());
    }

    public function test_org_unit_not_found_exception_message(): void
    {
        $e = new OrgUnitNotFoundException(7);
        $this->assertStringContainsString('7', $e->getMessage());
        $this->assertStringContainsString('OrgUnit', $e->getMessage());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Setting – additional tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_setting_description(): void
    {
        $s = new Setting(null, 1, 'tax_rate', '10', 'float', 'Default tax rate', null, null);
        $this->assertEquals('float', $s->getType());
        $this->assertEquals('Default tax rate', $s->getDescription());
        $this->assertNull($s->getId());
    }

    public function test_setting_update_to_null(): void
    {
        $s = $this->makeSetting();
        $s->updateValue(null);
        $this->assertNull($s->getValue());
    }

    public function test_setting_update_to_integer(): void
    {
        $s = $this->makeSetting();
        $s->updateValue(42);
        $this->assertEquals(42, $s->getValue());
    }

    // ──────────────────────────────────────────────────────────────────────
    // OrgUnit – additional hierarchy tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_org_unit_clear_parent(): void
    {
        $unit = $this->makeOrgUnit(2, 1);
        $this->assertEquals(1, $unit->getParentId());
        $unit->changeParent(null);
        $this->assertNull($unit->getParentId());
    }

    public function test_org_unit_type_and_level(): void
    {
        $division = new OrgUnit(3, 1, 1, 'Sales Division', 'SD', 'division', 1, true, null, null);
        $this->assertEquals('division', $division->getType());
        $this->assertEquals(1, $division->getLevel());
        $this->assertEquals(1, $division->getParentId());
    }

    public function test_org_unit_various_types(): void
    {
        foreach (['company', 'division', 'department', 'team', 'branch'] as $type) {
            $unit = new OrgUnit(1, 1, null, 'Unit', 'U', $type, 0, true, null, null);
            $this->assertEquals($type, $unit->getType());
        }
    }

    public function test_setting_service_gets_existing_setting(): void
    {
        $repo = $this->createMock(SettingRepositoryInterface::class);
        $repo->method('findByKey')->willReturn($this->makeSetting());

        $service = new SettingService($repo);
        $result = $service->get(1, 'site_name');
        $this->assertEquals('My ERP', $result);
    }
}
