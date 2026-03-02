<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests\Unit;

use Modules\Reporting\Application\Services\ReportingService;
use Modules\Reporting\Domain\Contracts\ReportingRepositoryContract;
use Modules\Reporting\Domain\Entities\ReportSchedule;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Structural unit tests for ReportingService::showSchedule().
 *
 * Validates method existence, visibility, parameter signature, and return type.
 * No DB required.
 */
class ReportingServiceShowScheduleTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence
    // -------------------------------------------------------------------------

    public function test_show_schedule_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ReportingService::class, 'showSchedule'),
            'ReportingService must expose a showSchedule() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Visibility
    // -------------------------------------------------------------------------

    public function test_show_schedule_is_public(): void
    {
        $ref = new ReflectionMethod(ReportingService::class, 'showSchedule');
        $this->assertTrue($ref->isPublic());
    }

    // -------------------------------------------------------------------------
    // Parameter signature
    // -------------------------------------------------------------------------

    public function test_show_schedule_accepts_integer_id(): void
    {
        $ref    = new ReflectionMethod(ReportingService::class, 'showSchedule');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()?->getName());
    }

    // -------------------------------------------------------------------------
    // Return type
    // -------------------------------------------------------------------------

    public function test_show_schedule_return_type_is_report_schedule(): void
    {
        $ref = new ReflectionMethod(ReportingService::class, 'showSchedule');
        $this->assertSame(ReportSchedule::class, $ref->getReturnType()?->getName());
    }

    // -------------------------------------------------------------------------
    // Not static
    // -------------------------------------------------------------------------

    public function test_show_schedule_is_not_static(): void
    {
        $ref = new ReflectionMethod(ReportingService::class, 'showSchedule');
        $this->assertFalse($ref->isStatic());
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_service_can_be_instantiated_with_repository_contract(): void
    {
        $repository = $this->createStub(ReportingRepositoryContract::class);
        $service    = new ReportingService($repository);

        $this->assertInstanceOf(ReportingService::class, $service);
    }
}
