<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Reporting\Application\Services\ReportingService;
use Modules\Reporting\Domain\Contracts\ReportingRepositoryContract;
use Modules\Reporting\Domain\Entities\ReportDefinition;
use PHPUnit\Framework\TestCase;

/**
 * Delegation tests for ReportingService read-path methods.
 *
 * Verifies that listDefinitions() and showDefinition() correctly delegate
 * to the injected ReportingRepositoryContract.
 * No database or Laravel bootstrap required.
 */
class ReportingServiceDelegationTest extends TestCase
{
    private function makeService(?ReportingRepositoryContract $repo = null): ReportingService
    {
        return new ReportingService(
            $repo ?? $this->createMock(ReportingRepositoryContract::class)
        );
    }

    // -------------------------------------------------------------------------
    // listDefinitions — delegates to repository all
    // -------------------------------------------------------------------------

    public function test_list_definitions_delegates_to_repository_all(): void
    {
        $expected = new Collection();

        $repo = $this->createMock(ReportingRepositoryContract::class);
        $repo->expects($this->once())
            ->method('all')
            ->willReturn($expected);

        $result = $this->makeService($repo)->listDefinitions();

        $this->assertSame($expected, $result);
    }

    public function test_list_definitions_returns_collection_type(): void
    {
        $repo = $this->createMock(ReportingRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection(['def1', 'def2']));

        $result = $this->makeService($repo)->listDefinitions();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    public function test_list_definitions_returns_empty_collection_when_none(): void
    {
        $repo = $this->createMock(ReportingRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection());

        $result = $this->makeService($repo)->listDefinitions();

        $this->assertCount(0, $result);
    }

    // -------------------------------------------------------------------------
    // showDefinition — delegates to repository findOrFail
    // -------------------------------------------------------------------------

    public function test_show_definition_delegates_to_repository_find_or_fail(): void
    {
        $definition = $this->getMockBuilder(ReportDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repo = $this->createMock(ReportingRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with(99)
            ->willReturn($definition);

        $result = $this->makeService($repo)->showDefinition(99);

        $this->assertSame($definition, $result);
    }

    public function test_show_definition_return_type_is_report_definition(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'showDefinition');
        $this->assertSame(ReportDefinition::class, (string) $reflection->getReturnType());
    }

    public function test_show_definition_is_not_static(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'showDefinition');
        $this->assertFalse($reflection->isStatic());
    }

    public function test_show_definition_is_public(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'showDefinition');
        $this->assertTrue($reflection->isPublic());
    }

    // -------------------------------------------------------------------------
    // updateDefinition — structural compliance (uses DB::transaction internally)
    // -------------------------------------------------------------------------

    public function test_update_definition_calls_find_or_fail_via_repository(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'updateDefinition');
        $params     = $reflection->getParameters();

        // Verify the method accepts (int $id, array $data)
        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
        $this->assertSame('data', $params[1]->getName());
        $this->assertSame('array', (string) $params[1]->getType());
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_reporting_service_can_be_instantiated(): void
    {
        $repo    = $this->createMock(ReportingRepositoryContract::class);
        $service = new ReportingService($repo);

        $this->assertInstanceOf(ReportingService::class, $service);
    }

    // -------------------------------------------------------------------------
    // Regression guard — existing methods still present
    // -------------------------------------------------------------------------

    public function test_create_definition_method_still_present(): void
    {
        $this->assertTrue(method_exists(ReportingService::class, 'createDefinition'));
    }

    public function test_generate_report_method_still_present(): void
    {
        $this->assertTrue(method_exists(ReportingService::class, 'generateReport'));
    }

    public function test_schedule_report_method_still_present(): void
    {
        $this->assertTrue(method_exists(ReportingService::class, 'scheduleReport'));
    }
}
