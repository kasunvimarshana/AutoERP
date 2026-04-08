<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\HR\Application\DTOs\TrainingData;
use Modules\HR\Domain\Entities\Training;
use Modules\HR\Domain\Events\TrainingCreated;
use Modules\HR\Domain\RepositoryInterfaces\TrainingRepositoryInterface;

class CreateTraining
{
    public function __construct(private readonly TrainingRepositoryInterface $repo) {}

    public function execute(TrainingData $data): Training
    {
        $training = new Training(
            tenantId:        $data->tenant_id,
            title:           $data->title,
            startDate:       $data->start_date,
            description:     $data->description,
            trainer:         $data->trainer,
            location:        $data->location,
            endDate:         $data->end_date,
            maxParticipants: $data->max_participants,
            status:          $data->status ?? 'scheduled',
            metadata:        $data->metadata !== null ? new Metadata($data->metadata) : null,
            isActive:        $data->is_active ?? true,
        );

        $saved = $this->repo->save($training);
        TrainingCreated::dispatch($saved);

        return $saved;
    }
}
