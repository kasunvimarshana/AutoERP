<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Commands;
final readonly class PostJournalEntryCommand {
    public function __construct(
        public int     $tenantId,
        public string  $entryDate,
        public string  $description,
        public array   $lines,
        public ?string $referenceType = null,
        public ?int    $referenceId   = null,
        public ?int    $postedBy      = null,
    ) {}
}
