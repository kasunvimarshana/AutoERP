<?php

namespace Modules\QualityControl\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\QualityControl\Domain\Contracts\QualityAlertRepositoryInterface;
use Modules\QualityControl\Domain\Events\QualityAlertRaised;

class CreateQualityAlertUseCase
{
    public function __construct(
        private QualityAlertRepositoryInterface $alertRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $alert = $this->alertRepo->create([
                'tenant_id'     => $data['tenant_id'],
                'inspection_id' => $data['inspection_id'] ?? null,
                'title'         => $data['title'],
                'description'   => $data['description'] ?? null,
                'product_id'    => $data['product_id'] ?? null,
                'lot_number'    => $data['lot_number'] ?? null,
                'priority'      => $data['priority'] ?? 'medium',
                'status'        => 'open',
                'assigned_to'   => $data['assigned_to'] ?? null,
                'deadline'      => $data['deadline'] ?? null,
                'resolved_at'   => null,
            ]);

            Event::dispatch(new QualityAlertRaised($alert->id, $data['tenant_id']));

            return $alert;
        });
    }
}
