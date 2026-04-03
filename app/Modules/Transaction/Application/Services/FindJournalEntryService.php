<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Transaction\Application\Contracts\FindJournalEntryServiceInterface;
use Modules\Transaction\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;

class FindJournalEntryService extends BaseService implements FindJournalEntryServiceInterface
{
    public function __construct(private readonly JournalEntryRepositoryInterface $journalEntryRepository)
    {
        parent::__construct($journalEntryRepository);
    }

    protected function handle(array $data): mixed
    {
        return $this->journalEntryRepository->find($data['id'] ?? null);
    }
}
