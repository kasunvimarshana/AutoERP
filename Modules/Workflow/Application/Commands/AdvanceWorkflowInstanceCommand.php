<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Commands;

final readonly class AdvanceWorkflowInstanceCommand
{
    public function __construct(
        public int $tenantId,
        public int $instanceId,
        public int $transitionId,
        public int $actorUserId,
        public ?string $comment,
    ) {}

    public function rules(): array
    {
        return [
            'tenantId' => ['required', 'integer', 'min:1'],
            'instanceId' => ['required', 'integer', 'min:1'],
            'transitionId' => ['required', 'integer', 'min:1'],
            'actorUserId' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'instanceId' => $this->instanceId,
            'transitionId' => $this->transitionId,
            'actorUserId' => $this->actorUserId,
            'comment' => $this->comment,
        ];
    }
}
