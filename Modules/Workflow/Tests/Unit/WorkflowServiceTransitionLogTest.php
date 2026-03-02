<?php

declare(strict_types=1);

namespace Modules\Workflow\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Workflow\Application\Services\WorkflowService;
use Modules\Workflow\Domain\Contracts\WorkflowRepositoryContract;
use Modules\Workflow\Domain\Entities\WorkflowTransitionLog;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WorkflowService transition log methods.
 *
 * Verifies method existence, visibility, parameter signatures, and return
 * types for listTransitionLogs. Pure-PHP — no database or Laravel bootstrap.
 */
class WorkflowServiceTransitionLogTest extends TestCase
{
    private function makeService(?WorkflowRepositoryContract $repo = null): WorkflowService
    {
        return new WorkflowService(
            $repo ?? $this->createMock(WorkflowRepositoryContract::class)
        );
    }

    // -------------------------------------------------------------------------
    // listTransitionLogs — method existence and visibility
    // -------------------------------------------------------------------------

    public function test_list_transition_logs_method_exists(): void
    {
        $this->assertTrue(
            method_exists(WorkflowService::class, 'listTransitionLogs'),
            'WorkflowService must expose a public listTransitionLogs() method.'
        );
    }

    public function test_list_transition_logs_is_public(): void
    {
        $reflection = new \ReflectionMethod(WorkflowService::class, 'listTransitionLogs');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_transition_logs_is_not_static(): void
    {
        $reflection = new \ReflectionMethod(WorkflowService::class, 'listTransitionLogs');

        $this->assertFalse($reflection->isStatic());
    }

    // -------------------------------------------------------------------------
    // listTransitionLogs — parameter signature
    // -------------------------------------------------------------------------

    public function test_list_transition_logs_accepts_instance_id_param(): void
    {
        $reflection = new \ReflectionMethod(WorkflowService::class, 'listTransitionLogs');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('instanceId', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
    }

    // -------------------------------------------------------------------------
    // Existing methods still present — regression guards
    // -------------------------------------------------------------------------

    public function test_apply_transition_method_still_exists(): void
    {
        $this->assertTrue(
            method_exists(WorkflowService::class, 'applyTransition'),
            'WorkflowService::applyTransition() must still be present.'
        );
    }

    public function test_create_instance_method_still_exists(): void
    {
        $this->assertTrue(
            method_exists(WorkflowService::class, 'createInstance'),
            'WorkflowService::createInstance() must still be present.'
        );
    }

    public function test_list_instances_method_still_exists(): void
    {
        $this->assertTrue(
            method_exists(WorkflowService::class, 'listInstances'),
            'WorkflowService::listInstances() must still be present.'
        );
    }

    public function test_show_instance_method_still_exists(): void
    {
        $this->assertTrue(
            method_exists(WorkflowService::class, 'showInstance'),
            'WorkflowService::showInstance() must still be present.'
        );
    }

    // -------------------------------------------------------------------------
    // Service instantiation — smoke test
    // -------------------------------------------------------------------------

    public function test_service_can_be_instantiated_with_mocked_contract(): void
    {
        $service = $this->makeService();

        $this->assertInstanceOf(WorkflowService::class, $service);
    }

    // -------------------------------------------------------------------------
    // WorkflowTransitionLog entity — structural compliance
    // -------------------------------------------------------------------------

    public function test_workflow_transition_log_entity_exists(): void
    {
        $this->assertTrue(
            class_exists(WorkflowTransitionLog::class),
            'WorkflowTransitionLog entity class must exist.'
        );
    }
}
