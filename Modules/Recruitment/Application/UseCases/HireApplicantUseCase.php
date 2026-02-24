<?php

namespace Modules\Recruitment\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Recruitment\Domain\Contracts\JobApplicationRepositoryInterface;
use Modules\Recruitment\Domain\Events\ApplicantHired;

class HireApplicantUseCase
{
    public function __construct(
        private JobApplicationRepositoryInterface $applicationRepo,
    ) {}

    public function execute(string $applicationId, string $reviewerId): object
    {
        return DB::transaction(function () use ($applicationId, $reviewerId) {
            $application = $this->applicationRepo->findById($applicationId);

            if (! $application) {
                throw new DomainException('Job application not found.');
            }

            if ($application->status === 'hired') {
                throw new DomainException('Applicant is already hired.');
            }

            if (! in_array($application->status, ['new', 'in_review', 'interview', 'offer'], true)) {
                throw new DomainException('Applicant cannot be hired from the current stage.');
            }

            $updated = $this->applicationRepo->update($applicationId, [
                'status'      => 'hired',
                'reviewer_id' => $reviewerId,
            ]);

            Event::dispatch(new ApplicantHired(
                applicationId: $applicationId,
                tenantId:      $application->tenant_id,
                positionId:    $application->position_id,
                reviewerId:    $reviewerId,
                candidateName: (string) ($application->candidate_name ?? ''),
                email:         (string) ($application->email ?? ''),
                phone:         (string) ($application->phone ?? ''),
            ));

            return $updated;
        });
    }
}
