<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Reporting\Application\Services\ReportingService;
use Modules\Reporting\Domain\Contracts\ReportingRepositoryContract;
use Modules\Reporting\Domain\Entities\ReportDefinition;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ReportingService read-path methods.
 *
 * Validates showDefinition() delegation and error handling.
 * No database or Laravel bootstrap required.
 */
class ReportingServiceReadPathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_reporting_service_has_show_definition_method(): void
    {
        $this->assertTrue(
            method_exists(ReportingService::class, 'showDefinition'),
            'ReportingService must expose a public showDefinition() method.'
        );
    }

    // -------------------------------------------------------------------------
    // showDefinition — parameter inspection
    // -------------------------------------------------------------------------

    public function test_show_definition_accepts_integer_id(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'showDefinition');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
    }

    public function test_show_definition_return_type_is_report_definition(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'showDefinition');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame(ReportDefinition::class, $returnType);
    }

    // -------------------------------------------------------------------------
    // showDefinition — delegation to repository
    // -------------------------------------------------------------------------

    public function test_show_definition_delegates_to_repository_find_or_fail(): void
    {
        $definition = $this->createMock(ReportDefinition::class);

        $repo = $this->createMock(ReportingRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with(42)
            ->willReturn($definition);

        $service = new ReportingService($repo);
        $result  = $service->showDefinition(42);

        $this->assertSame($definition, $result);
    }

    public function test_show_definition_throws_when_not_found(): void
    {
        $repo = $this->createMock(ReportingRepositoryContract::class);
        $repo->method('findOrFail')
            ->willThrowException(new ModelNotFoundException());

        $service = new ReportingService($repo);

        $this->expectException(ModelNotFoundException::class);
        $service->showDefinition(999);
    }

    // -------------------------------------------------------------------------
    // showDefinition is public
    // -------------------------------------------------------------------------

    public function test_show_definition_is_public(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'showDefinition');

        $this->assertTrue($reflection->isPublic());
    }
}
