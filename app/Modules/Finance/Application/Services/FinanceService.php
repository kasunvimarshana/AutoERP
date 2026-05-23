<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Application\DTOs\FinanceRecordData;
use Modules\Finance\Application\Repositories\BankAccountRepositoryInterface;
use Modules\Finance\Application\Repositories\BankTransactionRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryLineRepositoryInterface;
use Modules\Finance\Application\Repositories\JournalEntryRepositoryInterface;
use Modules\Finance\Domain\Exceptions\FinanceIntegrityException;
use Modules\Finance\Domain\Exceptions\FinanceRecordNotFoundException;
use Modules\Finance\Domain\Services\FinanceIntegrityService;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;

class FinanceService
{
    public function __construct(
        private readonly Container $container,
        private readonly TenantRepositoryInterface $tenants,
        private readonly JournalEntryRepositoryInterface $journalEntries,
        private readonly JournalEntryLineRepositoryInterface $journalEntryLines,
        private readonly BankAccountRepositoryInterface $bankAccounts,
        private readonly BankTransactionRepositoryInterface $bankTransactions,
        private readonly FinanceIntegrityService $integrity,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function definition(string $resource): array
    {
        $key = $this->integrity->normalizeResourceKey($resource);
        $definition = config("finance.resources.{$key}");

        if (! is_array($definition)) {
            throw FinanceRecordNotFoundException::for('Finance resource', $resource);
        }

        return ['key' => $key, ...$definition];
    }

    public function list(string $resource, int|string $tenantId, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->ensureTenantExists($tenantId);
        $repository = $this->repository($resource);

        return $perPage === null
            ? $repository->getWhere(['tenant_id' => $tenantId])
            : $repository->paginateWhere(['tenant_id' => $tenantId], $perPage);
    }

    public function find(string $resource, int|string $tenantId, int|string $id): Model
    {
        $repository = $this->repository($resource);
        $record = method_exists($repository, 'findForTenantById')
            ? $repository->findForTenantById($tenantId, $id)
            : $repository->getWhere(['tenant_id' => $tenantId, 'id' => $id])->first();

        if ($record === null) {
            throw FinanceRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }

    public function create(string $resource, FinanceRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $attributes = $this->prepareAttributes($definition, $data);

        return $repository->transaction(fn (): Model => $repository->create($attributes));
    }

    public function update(string $resource, int|string $tenantId, int|string $id, FinanceRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->integrity->ensureMutable($definition['key'], $record, $definition, true);

        $attributes = $this->prepareAttributes($definition, $data);

        return $repository->transaction(fn (): Model => $repository->update($record, $attributes));
    }

    public function delete(string $resource, int|string $tenantId, int|string $id): bool
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->integrity->ensureMutable($definition['key'], $record, $definition, true);

        return $repository->transaction(fn (): bool => $repository->delete($record));
    }

    public function postJournalEntry(int|string $tenantId, int|string $id, ?int $postedBy = null): Model
    {
        $entry = $this->find('journal_entries', $tenantId, $id);

        $this->integrity->ensureMutable('journal_entries', $entry, $this->definition('journal_entries'), true);

        $lines = $this->journalEntryLines->getWhere([
            'tenant_id' => $tenantId,
            'journal_entry_id' => $id,
        ]);

        $this->integrity->assertBalancedJournalLines($lines);

        return $this->journalEntries->transaction(fn (): Model => $this->journalEntries->update($entry, [
            'status' => config('finance.statuses.journal_entry.1', 'POSTED'),
            'posting_date' => now()->toDateString(),
            'posted_at' => now(),
            'posted_by' => $postedBy,
        ]));
    }

    public function recalculateBankAccountBalance(int|string $tenantId, int|string $bankAccountId): Model
    {
        $bankAccount = $this->find('bank_accounts', $tenantId, $bankAccountId);
        $transactions = $this->bankTransactions->getWhere([
            'tenant_id' => $tenantId,
            'bank_account_id' => $bankAccountId,
        ]);

        $balance = $this->integrity->calculateBankAccountBalance($bankAccount->opening_balance ?? 0, $transactions);

        return $this->bankAccounts->transaction(fn (): Model => $this->bankAccounts->update($bankAccount, [
            'current_balance' => $balance,
        ]));
    }

    private function ensureTenantExists(int|string $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw FinanceRecordNotFoundException::for('Tenant', $tenantId);
        }
    }

    private function repository(string $resource): BaseRepositoryInterface
    {
        $definition = $this->definition($resource);
        $repository = $this->container->make($definition['repository']);

        if (! $repository instanceof BaseRepositoryInterface) {
            throw FinanceIntegrityException::rule("Repository for [{$definition['key']}] must implement BaseRepositoryInterface.");
        }

        return $repository;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function prepareAttributes(array $definition, FinanceRecordData $data): array
    {
        $attributes = [
            ...$data->attributes,
            'tenant_id' => $data->tenantId,
        ];

        return $this->integrity->prepareAttributes($definition['key'], $attributes, $definition);
    }
}
