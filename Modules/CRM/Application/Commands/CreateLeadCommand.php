<?php
declare(strict_types=1);
namespace Modules\CRM\Application\Commands;
final readonly class CreateLeadCommand {
    public function __construct(
        public int     $tenantId,
        public string  $title,
        public ?int    $contactId      = null,
        public ?string $source         = null,
        public string  $value          = '0',
        public ?string $expectedCloseDate = null,
        public ?int    $assignedTo     = null,
        public ?string $notes          = null,
    ) {}
}
