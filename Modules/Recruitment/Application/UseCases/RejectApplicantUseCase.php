<?php

namespace Modules\Recruitment\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Recruitment\Domain\Contracts\JobApplicationRepositoryInterface;
use Modules\Recruitment\Domain\Events\ApplicantRejected;

class RejectApplicantUseCase
{
    public function __construct(
        private JobApplicationRepositoryInterface $applicationRepo,
    ) {}

    public function execute(string $applicationId, string $reviewerId, ?string $reason = null): object
    {
        return DB::transaction(function () use ($applicationId, $reviewerId, $reason) {
            $application = $this->applicationRepo->findById($applicationId);

            if (! $application) {
                throw new DomainException('Job application not found.');
            }

            if ($application->status === 'rejected') {
                throw new DomainException('Applicant is already rejected.');
            }

            $updated = $this->applicationRepo->update($applicationId, [
                'status'          => 'rejected',
                'reviewer_id'     => $reviewerId,
                'rejection_reason' => $reason,
            ]);

            Event::dispatch(new ApplicantRejected(
                $applicationId,
                $application->tenant_id,
                $application->position_id,
                $reviewerId,
            ));

            return $updated;
        });
    }
}
