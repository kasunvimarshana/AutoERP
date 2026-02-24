<?php

namespace Modules\Contracts\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Contracts\Domain\Contracts\ContractRepositoryInterface;
use Modules\Contracts\Domain\Events\ContractActivated;

class ActivateContractUseCase
{
    public function __construct(
        private ContractRepositoryInterface $contractRepo,
    ) {}

    public function execute(string $contractId): object
    {
        return DB::transaction(function () use ($contractId) {
            $contract = $this->contractRepo->findById($contractId);

            if (! $contract) {
                throw new DomainException('Contract not found.');
            }

            if ($contract->status !== 'draft') {
                throw new DomainException('Only draft contracts can be activated.');
            }

            $updated = $this->contractRepo->update($contractId, [
                'status'       => 'active',
                'activated_at' => now(),
            ]);

            Event::dispatch(new ContractActivated($contractId, $contract->tenant_id));

            return $updated;
        });
    }
}
