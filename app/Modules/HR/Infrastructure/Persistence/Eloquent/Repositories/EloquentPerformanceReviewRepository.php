<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PerformanceReviewModel;

class EloquentPerformanceReviewRepository extends EloquentRepository implements PerformanceReviewRepositoryInterface
{
    public function __construct(PerformanceReviewModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PerformanceReviewModel $model): PerformanceReview => $this->mapModelToDomainEntity($model));
    }

    public function save(PerformanceReview $performanceReview): PerformanceReview
    {
        $savedModel = null;

        DB::transaction(function () use ($performanceReview, &$savedModel) {
            $data = [
                'tenant_id'           => $performanceReview->getTenantId(),
                'employee_id'         => $performanceReview->getEmployeeId(),
                'reviewer_id'         => $performanceReview->getReviewerId(),
                'review_period_start' => $performanceReview->getReviewPeriodStart(),
                'review_period_end'   => $performanceReview->getReviewPeriodEnd(),
                'rating'              => $performanceReview->getRating(),
                'comments'            => $performanceReview->getComments(),
                'goals'               => $performanceReview->getGoals(),
                'achievements'        => $performanceReview->getAchievements(),
                'status'              => $performanceReview->getStatus(),
                'metadata'            => $performanceReview->getMetadata()->toArray(),
            ];

            if ($performanceReview->getId()) {
                $savedModel = $this->update($performanceReview->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof PerformanceReviewModel) {
            throw new \RuntimeException('Failed to save performance review.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function getByEmployee(int $employeeId): array
    {
        return $this->model->where('employee_id', $employeeId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    public function getByReviewer(int $reviewerId): array
    {
        return $this->model->where('reviewer_id', $reviewerId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    private function mapModelToDomainEntity(PerformanceReviewModel $model): PerformanceReview
    {
        return new PerformanceReview(
            tenantId:          $model->tenant_id,
            employeeId:        $model->employee_id,
            reviewerId:        $model->reviewer_id,
            reviewPeriodStart: $model->review_period_start instanceof \DateTimeInterface ? $model->review_period_start->format('Y-m-d') : (string) $model->review_period_start,
            reviewPeriodEnd:   $model->review_period_end instanceof \DateTimeInterface ? $model->review_period_end->format('Y-m-d') : (string) $model->review_period_end,
            rating:            (float) $model->rating,
            comments:          $model->comments,
            goals:             $model->goals,
            achievements:      $model->achievements,
            status:            (string) ($model->status ?? 'draft'),
            metadata:          new Metadata(is_array($model->metadata) ? $model->metadata : []),
            id:                $model->id,
            createdAt:         $model->created_at ? new \DateTimeImmutable($model->created_at instanceof \DateTimeInterface ? $model->created_at->format('Y-m-d H:i:s') : $model->created_at) : null,
            updatedAt:         $model->updated_at ? new \DateTimeImmutable($model->updated_at instanceof \DateTimeInterface ? $model->updated_at->format('Y-m-d H:i:s') : $model->updated_at) : null,
        );
    }
}
