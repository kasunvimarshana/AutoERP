<?php

namespace Modules\Reporting\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Reporting\Domain\Contracts\DashboardRepositoryInterface;
use Modules\Reporting\Domain\Events\DashboardCreated;

class CreateDashboardUseCase
{
    public function __construct(
        private DashboardRepositoryInterface $dashboardRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $dashboard = $this->dashboardRepo->create([
                'tenant_id'      => $data['tenant_id'],
                'user_id'        => $data['user_id'],
                'name'           => $data['name'],
                'description'    => $data['description'] ?? null,
                'layout'         => $data['layout'] ?? [],
                'is_shared'      => $data['is_shared'] ?? false,
                'refresh_seconds'=> $data['refresh_seconds'] ?? 300,
            ]);

            Event::dispatch(new DashboardCreated(
                $dashboard->id,
                $dashboard->tenant_id,
                $dashboard->name,
            ));

            return $dashboard;
        });
    }
}
