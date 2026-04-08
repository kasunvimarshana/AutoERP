<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Accounting\Application\Contracts\JournalEntryServiceInterface;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\Entities\JournalEntryLine;
use Modules\Accounting\Domain\Events\JournalEntryPosted;
use Modules\Accounting\Domain\Events\JournalEntryVoided;
use Modules\Accounting\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\JournalEntryLineRepositoryInterface;
class JournalEntryService implements JournalEntryServiceInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly JournalEntryLineRepositoryInterface $journalEntryLineRepository,
    ) {}
    public function getEntry(string $tenantId, string $id): JournalEntry
    {
        $entry = $this->journalEntryRepository->findById($tenantId, $id);
        if ($entry === null) {
            throw new NotFoundException("Journal entry [{$id}] not found.");
        }
        return $entry;
    }
    public function createEntry(string $tenantId, array $data, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($tenantId, $data, $lines): JournalEntry {
            $now = now();
            $entry = new JournalEntry(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                number: $data['number'],
                date: new \DateTimeImmutable($data['date']),
                description: $data['description'],
                reference: $data['reference'] ?? null,
                status: 'draft',
                sourceType: $data['source_type'] ?? null,
                sourceId: $data['source_id'] ?? null,
                postedAt: null,
                voidedAt: null,
                createdAt: $now,
                updatedAt: $now,
            );
            $this->journalEntryRepository->save($entry);
            foreach ($lines as $seq => $lineData) {
                $line = new JournalEntryLine(
                    id: (string) Str::uuid(),
                    tenantId: $tenantId,
                    journalEntryId: $entry->id,
                    accountId: $lineData['account_id'],
                    type: $lineData['type'],
                    amount: (float) $lineData['amount'],
                    currencyCode: $lineData['currency_code'] ?? 'USD',
                    description: $lineData['description'] ?? null,
                    sequence: (int) ($lineData['sequence'] ?? $seq),
                    createdAt: $now,
                    updatedAt: $now,
                );
                $this->journalEntryLineRepository->save($line);
            }
            return $entry;
        });
    }
    public function postEntry(string $tenantId, string $id): JournalEntry
    {
        return DB::transaction(function () use ($tenantId, $id): JournalEntry {
            $entry  = $this->getEntry($tenantId, $id);
            $posted = $entry->post();
            $this->journalEntryRepository->save($posted);
            Event::dispatch(new JournalEntryPosted($posted));
            return $posted;
        });
    }
    public function voidEntry(string $tenantId, string $id): JournalEntry
    {
        return DB::transaction(function () use ($tenantId, $id): JournalEntry {
            $entry  = $this->getEntry($tenantId, $id);
            $voided = $entry->void();
            $this->journalEntryRepository->save($voided);
            Event::dispatch(new JournalEntryVoided($voided));
            return $voided;
        });
    }
    public function getAllEntries(string $tenantId, array $filters = []): array
    {
        return $this->journalEntryRepository->findAll($tenantId, $filters);
    }
}
