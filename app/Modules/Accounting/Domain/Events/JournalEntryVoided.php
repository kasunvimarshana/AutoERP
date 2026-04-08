<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Events;
use Modules\Accounting\Domain\Entities\JournalEntry;
class JournalEntryVoided {
    public function __construct(public readonly JournalEntry $entry) {}
}
