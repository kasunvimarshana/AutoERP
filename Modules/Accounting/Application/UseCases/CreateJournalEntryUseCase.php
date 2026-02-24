<?php

namespace Modules\Accounting\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;

class CreateJournalEntryUseCase
{
    public function __construct(
        private JournalEntryRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = auth()->user()?->tenant_id ?? $data['tenant_id'] ?? null;

            return $this->repo->create(array_merge($data, [
                'tenant_id'  => $tenantId,
                'number'     => $this->repo->nextNumber($tenantId),
                'status'     => 'draft',
                'created_by' => auth()->id(),
            ]));
        });
    }
}
