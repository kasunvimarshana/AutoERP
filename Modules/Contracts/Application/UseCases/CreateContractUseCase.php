<?php

namespace Modules\Contracts\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Contracts\Domain\Contracts\ContractRepositoryInterface;
use Modules\Contracts\Domain\Events\ContractCreated;

class CreateContractUseCase
{
    public function __construct(
        private ContractRepositoryInterface $contractRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $contract = $this->contractRepo->create([
                'tenant_id'          => $data['tenant_id'],
                'title'              => $data['title'],
                'description'        => $data['description'] ?? null,
                'type'               => $data['type'] ?? 'other',
                'party_name'         => $data['party_name'],
                'party_email'        => $data['party_email'] ?? null,
                'party_reference'    => $data['party_reference'] ?? null,
                'start_date'         => $data['start_date'] ?? null,
                'end_date'           => $data['end_date'] ?? null,
                'total_value'        => bcadd((string) ($data['total_value'] ?? '0'), '0', 8),
                'currency'           => $data['currency'] ?? 'USD',
                'payment_terms'      => $data['payment_terms'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'status'             => 'draft',
            ]);

            Event::dispatch(new ContractCreated(
                $contract->id,
                $contract->tenant_id,
                $contract->title,
            ));

            return $contract;
        });
    }
}
