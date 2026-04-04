<?php

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Returns\Application\Contracts\ApproveReturnAuthorizationServiceInterface;
use Modules\Returns\Domain\Entities\ReturnAuthorization;
use Modules\Returns\Domain\Events\ReturnAuthorizationApproved;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;
use Modules\Returns\Domain\ValueObjects\RmaStatus;

class ApproveReturnAuthorizationService implements ApproveReturnAuthorizationServiceInterface
{
    public function __construct(
        private readonly ReturnAuthorizationRepositoryInterface $repository,
    ) {}

    public function execute(ReturnAuthorization $rma, int $approvedBy): ReturnAuthorization
    {
        $updated = $this->repository->update($rma, [
            'status'      => RmaStatus::APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        Event::dispatch(new ReturnAuthorizationApproved($updated->tenantId, $updated->id));

        return $updated;
    }
}
