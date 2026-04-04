<?php

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Returns\Application\Contracts\CreateReturnAuthorizationServiceInterface;
use Modules\Returns\Application\DTOs\ReturnAuthorizationData;
use Modules\Returns\Domain\Entities\ReturnAuthorization;
use Modules\Returns\Domain\Events\ReturnAuthorizationCreated;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;
use Modules\Returns\Domain\ValueObjects\RmaStatus;

class CreateReturnAuthorizationService implements CreateReturnAuthorizationServiceInterface
{
    public function __construct(
        private readonly ReturnAuthorizationRepositoryInterface $repository,
    ) {}

    public function execute(ReturnAuthorizationData $data): ReturnAuthorization
    {
        $rma = $this->repository->create([
            'tenant_id'       => $data->tenantId,
            'stock_return_id' => $data->stockReturnId,
            'rma_number'      => $data->rmaNumber,
            'status'          => RmaStatus::PENDING,
            'expires_at'      => $data->expiresAt,
            'notes'           => $data->notes,
        ]);

        Event::dispatch(new ReturnAuthorizationCreated($rma->tenantId, $rma->id));

        return $rma;
    }
}
