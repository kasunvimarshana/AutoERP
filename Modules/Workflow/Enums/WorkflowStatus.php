<?php

declare(strict_types=1);

namespace Modules\Workflow\Enums;

enum WorkflowStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::ARCHIVED => 'Archived',
        };
    }

    public function canExecute(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::DRAFT, self::INACTIVE]);
    }
}
