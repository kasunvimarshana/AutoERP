<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\UpdateTrainingServiceInterface;
use Modules\HR\Application\DTOs\UpdateTrainingData;
use Modules\HR\Domain\Entities\Training;
use Modules\HR\Domain\Events\TrainingUpdated;
use Modules\HR\Domain\Exceptions\TrainingNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\TrainingRepositoryInterface;

class UpdateTrainingService extends BaseService implements UpdateTrainingServiceInterface
{
    public function __construct(private readonly TrainingRepositoryInterface $trainingRepository)
    {
        parent::__construct($trainingRepository);
    }

    protected function handle(array $data): Training
    {
        $dto      = UpdateTrainingData::fromArray($data);
        $id       = (int) ($dto->id ?? 0);
        $training = $this->trainingRepository->find($id);
        if (! $training) {
            throw new TrainingNotFoundException($id);
        }

        $title           = $dto->isProvided('title') ? (string) $dto->title : $training->getTitle();
        $startDate       = $dto->isProvided('start_date') ? (string) $dto->start_date : $training->getStartDate();
        $description     = $dto->isProvided('description') ? $dto->description : $training->getDescription();
        $trainer         = $dto->isProvided('trainer') ? $dto->trainer : $training->getTrainer();
        $location        = $dto->isProvided('location') ? $dto->location : $training->getLocation();
        $endDate         = $dto->isProvided('end_date') ? $dto->end_date : $training->getEndDate();
        $maxParticipants = $dto->isProvided('max_participants') ? $dto->max_participants : $training->getMaxParticipants();

        $training->updateDetails($title, $startDate, $description, $trainer, $location, $endDate, $maxParticipants);

        $saved = $this->trainingRepository->save($training);
        $this->addEvent(new TrainingUpdated($saved));

        return $saved;
    }
}
