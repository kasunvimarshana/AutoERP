<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Application\DTOs\UpdateTrainingData;
use Modules\HR\Domain\Entities\Training;
use Modules\HR\Domain\Events\TrainingUpdated;
use Modules\HR\Domain\Exceptions\TrainingNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\TrainingRepositoryInterface;

class UpdateTraining
{
    public function __construct(private readonly TrainingRepositoryInterface $repo) {}

    public function execute(UpdateTrainingData $data): Training
    {
        $id       = (int) ($data->id ?? 0);
        $training = $this->repo->find($id);
        if (! $training) {
            throw new TrainingNotFoundException($id);
        }

        $title           = $data->isProvided('title') ? (string) $data->title : $training->getTitle();
        $startDate       = $data->isProvided('start_date') ? (string) $data->start_date : $training->getStartDate();
        $description     = $data->isProvided('description') ? $data->description : $training->getDescription();
        $trainer         = $data->isProvided('trainer') ? $data->trainer : $training->getTrainer();
        $location        = $data->isProvided('location') ? $data->location : $training->getLocation();
        $endDate         = $data->isProvided('end_date') ? $data->end_date : $training->getEndDate();
        $maxParticipants = $data->isProvided('max_participants') ? $data->max_participants : $training->getMaxParticipants();

        $training->updateDetails($title, $startDate, $description, $trainer, $location, $endDate, $maxParticipants);

        $saved = $this->repo->save($training);
        TrainingUpdated::dispatch($saved);

        return $saved;
    }
}
