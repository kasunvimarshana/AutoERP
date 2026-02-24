<?php

namespace Modules\Recruitment\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Recruitment\Domain\Contracts\JobApplicationRepositoryInterface;
use Modules\Recruitment\Domain\Contracts\JobPositionRepositoryInterface;
use Modules\Recruitment\Domain\Events\JobApplicationReceived;

class CreateJobApplicationUseCase
{
    public function __construct(
        private JobApplicationRepositoryInterface $applicationRepo,
        private JobPositionRepositoryInterface    $positionRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $position = $this->positionRepo->findById($data['position_id']);

            if (! $position) {
                throw new DomainException('Job position not found.');
            }

            if ($position->status !== 'open') {
                throw new DomainException('Job position is not open for applications.');
            }

            $application = $this->applicationRepo->create([
                'tenant_id'      => $data['tenant_id'],
                'position_id'    => $data['position_id'],
                'candidate_name' => $data['candidate_name'],
                'email'          => $data['email'],
                'phone'          => $data['phone'] ?? null,
                'resume_url'     => $data['resume_url'] ?? null,
                'cover_letter'   => $data['cover_letter'] ?? null,
                'source'         => $data['source'] ?? null,
                'status'         => 'new',
            ]);

            Event::dispatch(new JobApplicationReceived(
                $application->id,
                $application->tenant_id,
                $application->position_id,
                $application->candidate_name,
            ));

            return $application;
        });
    }
}
