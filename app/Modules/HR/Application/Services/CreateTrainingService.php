<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\HR\Application\Contracts\CreateTrainingServiceInterface;
use Modules\HR\Application\DTOs\TrainingData;
use Modules\HR\Domain\Entities\Training;
use Modules\HR\Domain\Events\TrainingCreated;
use Modules\HR\Domain\RepositoryInterfaces\TrainingRepositoryInterface;

class CreateTrainingService extends BaseService implements CreateTrainingServiceInterface
{
    public function __construct(private readonly TrainingRepositoryInterface $trainingRepository)
    {
        parent::__construct($trainingRepository);
    }

    protected function handle(array $data): Training
    {
        $dto = TrainingData::fromArray($data);

        $training = new Training(
            tenantId:        $dto->tenant_id,
            title:           $dto->title,
            startDate:       $dto->start_date,
            description:     $dto->description,
            trainer:         $dto->trainer,
            location:        $dto->location,
            endDate:         $dto->end_date,
            maxParticipants: $dto->max_participants,
            status:          $dto->status ?? 'scheduled',
            metadata:        $dto->metadata !== null ? new Metadata($dto->metadata) : null,
            isActive:        $dto->is_active ?? true,
        );

        $saved = $this->trainingRepository->save($training);
        $this->addEvent(new TrainingCreated($saved));

        return $saved;
    }
}
