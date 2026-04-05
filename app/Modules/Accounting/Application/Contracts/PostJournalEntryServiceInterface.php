<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Contracts;
use Modules\Accounting\Domain\Entities\JournalEntry;
interface PostJournalEntryServiceInterface { public function execute(int $id): JournalEntry; }
