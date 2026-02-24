<?php

namespace Modules\Recruitment\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Recruitment\Domain\Contracts\JobPositionRepositoryInterface;

class CreateJobPositionUseCase
{
    public function __construct(
        private JobPositionRepositoryInterface $positionRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            return $this->positionRepo->create([
                'tenant_id'           => $data['tenant_id'],
                'title'               => $data['title'],
                'department_id'       => $data['department_id'] ?? null,
                'location'            => $data['location'] ?? null,
                'employment_type'     => $data['employment_type'] ?? 'full_time',
                'description'         => $data['description'] ?? null,
                'requirements'        => $data['requirements'] ?? null,
                'vacancies'           => $data['vacancies'] ?? 1,
                'status'              => 'open',
                'expected_start_date' => $data['expected_start_date'] ?? null,
            ]);
        });
    }
}
