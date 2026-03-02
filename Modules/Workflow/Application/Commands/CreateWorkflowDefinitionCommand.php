<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Commands;

final readonly class CreateWorkflowDefinitionCommand
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public ?string $description,
        public string $entityType,
        public array $states,
        public array $transitions,
    ) {}

    public function rules(): array
    {
        return [
            'tenantId' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'entityType' => ['required', 'string', 'max:100'],
            'states' => ['required', 'array', 'min:2'],
            'transitions' => ['required', 'array'],
        ];
    }

    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'name' => $this->name,
            'description' => $this->description,
            'entityType' => $this->entityType,
            'states' => $this->states,
            'transitions' => $this->transitions,
        ];
    }
}
