<?php

namespace Modules\Contracts\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Contracts\Domain\Contracts\ContractRepositoryInterface;
use Modules\Contracts\Domain\Events\ContractTerminated;

class TerminateContractUseCase
{
    public function __construct(
        private ContractRepositoryInterface $contractRepo,
    ) {}

    public function execute(string $contractId, ?string $reason = null): object
    {
        return DB::transaction(function () use ($contractId, $reason) {
            $contract = $this->contractRepo->findById($contractId);

            if (! $contract) {
                throw new DomainException('Contract not found.');
            }

            if (in_array($contract->status, ['terminated', 'expired'], true)) {
                throw new DomainException('Contract is already terminated or expired.');
            }

            $updated = $this->contractRepo->update($contractId, [
                'status'               => 'terminated',
                'termination_reason'   => $reason,
                'terminated_at'        => now(),
            ]);

            Event::dispatch(new ContractTerminated($contractId, $contract->tenant_id, $reason));

            return $updated;
        });
    }
}
