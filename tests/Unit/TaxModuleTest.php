<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Modules\Tax\Application\Services\CalculateTaxService;
use Modules\Tax\Application\Services\ManageTaxGroupService;
use Modules\Tax\Domain\Entities\TaxCalculationResult;
use Modules\Tax\Domain\Entities\TaxGroup;
use Modules\Tax\Domain\Entities\TaxGroupRate;
use Modules\Tax\Domain\Exceptions\TaxGroupNotFoundException;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRateRepositoryInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;

class TaxModuleTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────
    // Factory helpers
    // ──────────────────────────────────────────────────────────────────────

    private function makeTaxGroup(int $id = 1, bool $active = true): TaxGroup
    {
        return new TaxGroup($id, 1, 'Standard Tax', 'STANDARD', 'Standard 10% GST', $active, null, null);
    }

    private function makeTaxGroupRate(
        int $id = 1,
        float $rate = 10.0,
        int $sortOrder = 0,
        bool $isCompound = false
    ): TaxGroupRate {
        return new TaxGroupRate(
            $id, 1, 1, 'GST10', 'GST 10%', $rate, $sortOrder, $isCompound, null, null
        );
    }

    // ──────────────────────────────────────────────────────────────────────
    // TaxGroup entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_tax_group_creation(): void
    {
        $group = $this->makeTaxGroup();
        $this->assertEquals(1, $group->getId());
        $this->assertEquals('Standard Tax', $group->getName());
        $this->assertEquals('STANDARD', $group->getCode());
        $this->assertTrue($group->isActive());
    }

    public function test_tax_group_activate_deactivate(): void
    {
        $group = $this->makeTaxGroup(1, false);
        $this->assertFalse($group->isActive());
        $group->activate();
        $this->assertTrue($group->isActive());
        $group->deactivate();
        $this->assertFalse($group->isActive());
    }

    public function test_tax_group_update(): void
    {
        $group = $this->makeTaxGroup();
        $group->update('Updated Tax', 'UPDATED', 'new desc');
        $this->assertEquals('Updated Tax', $group->getName());
        $this->assertEquals('UPDATED', $group->getCode());
        $this->assertEquals('new desc', $group->getDescription());
    }

    // ──────────────────────────────────────────────────────────────────────
    // TaxGroupRate entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_tax_group_rate_creation(): void
    {
        $rate = $this->makeTaxGroupRate(1, 10.0, 0, false);
        $this->assertEquals(1, $rate->getId());
        $this->assertEquals(10.0, $rate->getRate());
        $this->assertEquals('GST10', $rate->getTaxRateCode());
        $this->assertFalse($rate->isCompound());
    }

    public function test_tax_group_rate_simple_calculation(): void
    {
        $rate = $this->makeTaxGroupRate(1, 10.0);
        $this->assertEqualsWithDelta(10.0, $rate->calculate(100.0), 0.0001);
    }

    public function test_tax_group_rate_compound_calculation(): void
    {
        $rate = $this->makeTaxGroupRate(1, 5.0, 1, true);
        // compound: applies on base + accumulated
        $this->assertEqualsWithDelta(5.5, $rate->calculateCompound(100.0, 10.0), 0.0001);
    }

    // ──────────────────────────────────────────────────────────────────────
    // TaxCalculationResult entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_tax_calculation_result(): void
    {
        $result = new TaxCalculationResult(
            taxGroupId:   1,
            taxGroupCode: 'STANDARD',
            baseAmount:   100.0,
            totalTax:     10.0,
            breakdown:    [['code' => 'GST10', 'name' => 'GST 10%', 'rate' => 10.0, 'tax' => 10.0]],
        );
        $this->assertEquals(1, $result->getTaxGroupId());
        $this->assertEquals(100.0, $result->getBaseAmount());
        $this->assertEquals(10.0, $result->getTotalTax());
        $this->assertEqualsWithDelta(110.0, $result->getAmountWithTax(), 0.0001);
        $this->assertCount(1, $result->getBreakdown());
    }

    // ──────────────────────────────────────────────────────────────────────
    // ManageTaxGroupService tests
    // ──────────────────────────────────────────────────────────────────────

    private function makeMockGroupRepo(): MockObject&TaxGroupRepositoryInterface
    {
        return $this->createMock(TaxGroupRepositoryInterface::class);
    }

    public function test_manage_tax_group_service_find_by_id(): void
    {
        $repo = $this->makeMockGroupRepo();
        $group = $this->makeTaxGroup(5);
        $repo->method('findById')->with(5)->willReturn($group);

        $service = new ManageTaxGroupService($repo);
        $result  = $service->findById(5);
        $this->assertEquals(5, $result->getId());
    }

    public function test_manage_tax_group_service_find_not_found_throws(): void
    {
        $repo = $this->makeMockGroupRepo();
        $repo->method('findById')->willReturn(null);

        $this->expectException(TaxGroupNotFoundException::class);
        (new ManageTaxGroupService($repo))->findById(99);
    }

    public function test_manage_tax_group_service_create(): void
    {
        $repo  = $this->makeMockGroupRepo();
        $group = $this->makeTaxGroup(1);
        $repo->method('create')->willReturn($group);

        $service = new ManageTaxGroupService($repo);
        $result  = $service->create(['tenant_id' => 1, 'name' => 'Standard Tax', 'code' => 'STANDARD']);
        $this->assertEquals('Standard Tax', $result->getName());
    }

    public function test_manage_tax_group_service_delete(): void
    {
        $repo  = $this->makeMockGroupRepo();
        $group = $this->makeTaxGroup(1);
        $repo->method('findById')->willReturn($group);
        $repo->expects($this->once())->method('delete')->with(1)->willReturn(true);

        (new ManageTaxGroupService($repo))->delete(1);
    }

    public function test_manage_tax_group_service_activate(): void
    {
        $repo     = $this->makeMockGroupRepo();
        $group    = $this->makeTaxGroup(1, false);
        $active   = $this->makeTaxGroup(1, true);
        $repo->method('findById')->willReturn($group);
        $repo->method('update')->willReturn($active);

        $result = (new ManageTaxGroupService($repo))->activate(1);
        $this->assertTrue($result->isActive());
    }

    public function test_manage_tax_group_service_deactivate(): void
    {
        $repo       = $this->makeMockGroupRepo();
        $group      = $this->makeTaxGroup(1, true);
        $inactive   = $this->makeTaxGroup(1, false);
        $repo->method('findById')->willReturn($group);
        $repo->method('update')->willReturn($inactive);

        $result = (new ManageTaxGroupService($repo))->deactivate(1);
        $this->assertFalse($result->isActive());
    }

    public function test_manage_tax_group_service_find_all(): void
    {
        $repo = $this->makeMockGroupRepo();
        $repo->method('findAllByTenant')->willReturn([$this->makeTaxGroup(1), $this->makeTaxGroup(2)]);

        $results = (new ManageTaxGroupService($repo))->findAllByTenant(1);
        $this->assertCount(2, $results);
    }

    // ──────────────────────────────────────────────────────────────────────
    // CalculateTaxService tests
    // ──────────────────────────────────────────────────────────────────────

    private function makeMockRateRepo(): MockObject&TaxGroupRateRepositoryInterface
    {
        return $this->createMock(TaxGroupRateRepositoryInterface::class);
    }

    private function makeCalcService(
        ?TaxGroup $group = null,
        array $rates = []
    ): CalculateTaxService {
        $groupRepo = $this->makeMockGroupRepo();
        $rateRepo  = $this->makeMockRateRepo();
        $groupRepo->method('findById')->willReturn($group ?? $this->makeTaxGroup(1));
        $rateRepo->method('findByTaxGroup')->willReturn($rates);
        return new CalculateTaxService($groupRepo, $rateRepo);
    }

    public function test_calculate_tax_single_rate(): void
    {
        $service = $this->makeCalcService(
            group: $this->makeTaxGroup(1),
            rates: [$this->makeTaxGroupRate(1, 10.0, 0, false)]
        );
        $result = $service->calculate(1, 100.0);
        $this->assertEqualsWithDelta(10.0, $result->getTotalTax(), 0.0001);
        $this->assertEqualsWithDelta(110.0, $result->getAmountWithTax(), 0.0001);
    }

    public function test_calculate_tax_multiple_simple_rates(): void
    {
        $rates = [
            $this->makeTaxGroupRate(1, 10.0, 0, false),
            $this->makeTaxGroupRate(2, 5.0, 1, false),
        ];
        $service = $this->makeCalcService(rates: $rates);
        $result = $service->calculate(1, 100.0);
        // 10 + 5 = 15 (both on base 100)
        $this->assertEqualsWithDelta(15.0, $result->getTotalTax(), 0.0001);
        $this->assertCount(2, $result->getBreakdown());
    }

    public function test_calculate_tax_compound_rate(): void
    {
        $rates = [
            $this->makeTaxGroupRate(1, 10.0, 0, false),  // 10% on 100 = 10
            $this->makeTaxGroupRate(2, 5.0, 1, true),    // 5% compound on (100+10) = 5.5
        ];
        $service = $this->makeCalcService(rates: $rates);
        $result = $service->calculate(1, 100.0);
        $this->assertEqualsWithDelta(15.5, $result->getTotalTax(), 0.0001);
    }

    public function test_calculate_tax_zero_rates_returns_zero(): void
    {
        $service = $this->makeCalcService(rates: []);
        $result = $service->calculate(1, 100.0);
        $this->assertEqualsWithDelta(0.0, $result->getTotalTax(), 0.0001);
    }

    public function test_calculate_tax_group_not_found_throws(): void
    {
        $groupRepo = $this->makeMockGroupRepo();
        $rateRepo  = $this->makeMockRateRepo();
        $groupRepo->method('findById')->willReturn(null);
        $service = new CalculateTaxService($groupRepo, $rateRepo);

        $this->expectException(TaxGroupNotFoundException::class);
        $service->calculate(99, 100.0);
    }

    public function test_calculate_tax_breakdown_structure(): void
    {
        $rates = [$this->makeTaxGroupRate(1, 10.0, 0, false)];
        $service = $this->makeCalcService(rates: $rates);
        $result = $service->calculate(1, 200.0);
        $breakdown = $result->getBreakdown();
        $this->assertArrayHasKey('code', $breakdown[0]);
        $this->assertArrayHasKey('name', $breakdown[0]);
        $this->assertArrayHasKey('rate', $breakdown[0]);
        $this->assertArrayHasKey('tax', $breakdown[0]);
        $this->assertEqualsWithDelta(20.0, $breakdown[0]['tax'], 0.0001);
    }

    public function test_tax_group_not_found_exception_message(): void
    {
        $ex = new TaxGroupNotFoundException(42);
        $this->assertStringContainsString('42', $ex->getMessage());
    }
}
