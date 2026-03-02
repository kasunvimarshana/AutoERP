<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests\Unit;

use Modules\Reporting\Application\Services\ReportingService;
use Modules\Reporting\Domain\Entities\ReportDefinition;
use Modules\Reporting\Domain\Entities\ReportExport;
use Modules\Reporting\Domain\Entities\ReportSchedule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ReportingService CRUD methods.
 *
 * Uses reflection only — no database or Laravel bootstrap required.
 */
class ReportingServiceCrudTest extends TestCase
{
    // -------------------------------------------------------------------------
    // updateDefinition — structural compliance
    // -------------------------------------------------------------------------

    public function test_update_definition_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ReportingService::class, 'updateDefinition'),
            'ReportingService must expose a public updateDefinition() method.'
        );
    }

    public function test_update_definition_is_public(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'updateDefinition');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_update_definition_has_int_id_and_array_data_params(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'updateDefinition');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
        $this->assertSame('data', $params[1]->getName());
        $this->assertSame('array', (string) $params[1]->getType());
    }

    public function test_update_definition_return_type_is_report_definition(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'updateDefinition');

        $this->assertSame(ReportDefinition::class, (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // deleteDefinition — structural compliance
    // -------------------------------------------------------------------------

    public function test_delete_definition_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ReportingService::class, 'deleteDefinition'),
            'ReportingService must expose a public deleteDefinition() method.'
        );
    }

    public function test_delete_definition_is_public(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'deleteDefinition');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_delete_definition_has_int_id_param(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'deleteDefinition');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
    }

    public function test_delete_definition_return_type_is_bool(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'deleteDefinition');

        $this->assertSame('bool', (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // listSchedules — structural compliance
    // -------------------------------------------------------------------------

    public function test_list_schedules_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ReportingService::class, 'listSchedules'),
            'ReportingService must expose a public listSchedules() method.'
        );
    }

    public function test_list_schedules_is_public(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'listSchedules');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_schedules_has_no_params(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'listSchedules');

        $this->assertCount(0, $reflection->getParameters());
    }

    // -------------------------------------------------------------------------
    // listExports — structural compliance
    // -------------------------------------------------------------------------

    public function test_list_exports_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ReportingService::class, 'listExports'),
            'ReportingService must expose a public listExports() method.'
        );
    }

    public function test_list_exports_is_public(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'listExports');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_exports_has_no_params(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'listExports');

        $this->assertCount(0, $reflection->getParameters());
    }

    // -------------------------------------------------------------------------
    // showExport — structural compliance
    // -------------------------------------------------------------------------

    public function test_show_export_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ReportingService::class, 'showExport'),
            'ReportingService must expose a public showExport() method.'
        );
    }

    public function test_show_export_is_public(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'showExport');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_show_export_has_int_id_param(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'showExport');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
    }

    public function test_show_export_return_type_is_report_export(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'showExport');

        $this->assertSame(ReportExport::class, (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // updateSchedule — structural compliance
    // -------------------------------------------------------------------------

    public function test_update_schedule_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ReportingService::class, 'updateSchedule'),
            'ReportingService must expose a public updateSchedule() method.'
        );
    }

    public function test_update_schedule_is_public(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'updateSchedule');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_update_schedule_has_int_id_and_array_data_params(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'updateSchedule');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
        $this->assertSame('data', $params[1]->getName());
        $this->assertSame('array', (string) $params[1]->getType());
    }

    public function test_update_schedule_return_type_is_report_schedule(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'updateSchedule');

        $this->assertSame(ReportSchedule::class, (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // deleteSchedule — structural compliance
    // -------------------------------------------------------------------------

    public function test_delete_schedule_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ReportingService::class, 'deleteSchedule'),
            'ReportingService must expose a public deleteSchedule() method.'
        );
    }

    public function test_delete_schedule_is_public(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'deleteSchedule');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_delete_schedule_return_type_is_bool(): void
    {
        $reflection = new \ReflectionMethod(ReportingService::class, 'deleteSchedule');

        $this->assertSame('bool', (string) $reflection->getReturnType());
    }
}
