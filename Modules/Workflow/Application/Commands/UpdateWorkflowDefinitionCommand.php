<?php

declare(strict_types=1);

namespace Modules\Workflow\Application\Commands;

final readonly class UpdateWorkflowDefinitionCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?string $name,
        public ?string $description,
        public ?bool $isActive,
    ) {}

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
            'tenantId' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'name' => $this->name,
            'description' => $this->description,
            'isActive' => $this->isActive,
        ];
    }
}
