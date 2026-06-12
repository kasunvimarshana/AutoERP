<?php

declare(strict_types=1);

namespace Modules\Extension\DTOs;

use Illuminate\Database\Eloquent\Model;

final readonly class AttachmentTarget
{
    public function __construct(
        public string $alias,
        public string $module,
        public Model $model,
        public int $tenantId,
        public ?int $organizationUnitId,
        public ?string $reference,
    ) {}
}
