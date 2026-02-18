<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Workflow Contract Interface
 *
 * Defines standardized workflow behavior for business processes.
 * Enables event-driven, asynchronous workflow execution.
 */
interface WorkflowContract
{
    /**
     * Get workflow identifier
     */
    public function getWorkflowId(): string;

    /**
     * Get workflow name
     */
    public function getWorkflowName(): string;

    /**
     * Get workflow steps in execution order
     *
     * @return array<string, array> Step configurations
     */
    public function getSteps(): array;

    /**
     * Get workflow transitions
     * Defines valid state transitions
     *
     * @return array<string, array<string>> From state => [to states]
     */
    public function getTransitions(): array;

    /**
     * Get workflow permissions
     * Defines who can perform actions on this workflow
     *
     * @return array<string, array<string>> Action => [permissions]
     */
    public function getPermissions(): array;

    /**
     * Execute workflow step
     */
    public function executeStep(string $stepId, array $context): mixed;

    /**
     * Validate workflow transition
     */
    public function canTransition(string $fromState, string $toState): bool;

    /**
     * Get workflow events
     * Events emitted during workflow execution
     *
     * @return array<string, string> Event name => Event class
     */
    public function getEvents(): array;
}
