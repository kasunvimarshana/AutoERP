<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\Training;
use Modules\HR\Domain\RepositoryInterfaces\TrainingRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\TrainingModel;

class EloquentTrainingRepository extends EloquentRepository implements TrainingRepositoryInterface
{
    public function __construct(TrainingModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (TrainingModel $model): Training => $this->mapModelToDomainEntity($model));
    }

    public function save(Training $training): Training
    {
        $savedModel = null;

        DB::transaction(function () use ($training, &$savedModel) {
            $data = [
                'tenant_id'        => $training->getTenantId(),
                'title'            => $training->getTitle(),
                'description'      => $training->getDescription(),
                'trainer'          => $training->getTrainer(),
                'location'         => $training->getLocation(),
                'start_date'       => $training->getStartDate(),
                'end_date'         => $training->getEndDate(),
                'max_participants' => $training->getMaxParticipants(),
                'status'           => $training->getStatus(),
                'metadata'         => $training->getMetadata()->toArray(),
                'is_active'        => $training->isActive(),
            ];

            if ($training->getId()) {
                $savedModel = $this->update($training->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof TrainingModel) {
            throw new \RuntimeException('Failed to save training.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function getByStatus(string $status): array
    {
        return $this->model->where('status', $status)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    private function mapModelToDomainEntity(TrainingModel $model): Training
    {
        return new Training(
            tenantId:        $model->tenant_id,
            title:           (string) $model->title,
            startDate:       $model->start_date instanceof \DateTimeInterface ? $model->start_date->format('Y-m-d') : (string) $model->start_date,
            description:     $model->description,
            trainer:         $model->trainer,
            location:        $model->location,
            endDate:         $model->end_date !== null ? ($model->end_date instanceof \DateTimeInterface ? $model->end_date->format('Y-m-d') : (string) $model->end_date) : null,
            maxParticipants: $model->max_participants !== null ? (int) $model->max_participants : null,
            status:          (string) ($model->status ?? 'scheduled'),
            metadata:        new Metadata(is_array($model->metadata) ? $model->metadata : []),
            isActive:        (bool) ($model->is_active ?? true),
            id:              $model->id,
            createdAt:       $model->created_at ? new \DateTimeImmutable($model->created_at instanceof \DateTimeInterface ? $model->created_at->format('Y-m-d H:i:s') : $model->created_at) : null,
            updatedAt:       $model->updated_at ? new \DateTimeImmutable($model->updated_at instanceof \DateTimeInterface ? $model->updated_at->format('Y-m-d H:i:s') : $model->updated_at) : null,
        );
    }
}
