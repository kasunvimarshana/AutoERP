<?php
namespace Modules\CRM\Domain\Entities;
class Lead
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly ?string $company,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly string $source,
        public readonly string $status,
        public readonly float $score,
        public readonly ?string $assignedTo,
    ) {}
}
