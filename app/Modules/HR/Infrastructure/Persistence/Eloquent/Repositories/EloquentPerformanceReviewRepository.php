<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;
use Modules\HR\Domain\ValueObjects\PerformanceRating;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PerformanceReviewModel;

class EloquentPerformanceReviewRepository extends EloquentRepository implements PerformanceReviewRepositoryInterface
{
    public function __construct(PerformanceReviewModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PerformanceReviewModel $m): PerformanceReview => $this->map($m));
    }

    public function save(PerformanceReview $e): PerformanceReview
    {
        $data = ['tenant_id' => $e->getTenantId(), 'employee_id' => $e->getEmployeeId(), 'cycle_id' => $e->getCycleId(), 'reviewer_id' => $e->getReviewerId(), 'overall_rating' => $e->getOverallRating()?->value, 'goals' => $e->getGoals(), 'strengths' => $e->getStrengths(), 'improvements' => $e->getImprovements(), 'reviewer_comments' => $e->getReviewerComments(), 'employee_comments' => $e->getEmployeeComments(), 'status' => $e->getStatus(), 'acknowledged_at' => $e->getAcknowledgedAt()?->format('Y-m-d H:i:s'), 'metadata' => $e->getMetadata()];
        $m = $e->getId() ? $this->update($e->getId(), $data) : $this->create($data);

        return $this->toDomainEntity($m);
    }

    public function find(int|string $id, array $columns = ['*']): ?PerformanceReview
    {
        return parent::find($id, $columns);
    }

    public function findByEmployeeAndCycle(int $tenantId, int $employeeId, int $cycleId): ?PerformanceReview
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('employee_id', $employeeId)->where('cycle_id', $cycleId)->first();

        return $m ? $this->toDomainEntity($m) : null;
    }

    private function map(PerformanceReviewModel $m): PerformanceReview
    {
        $now = fn ($v) => $v instanceof \DateTimeInterface ? $v : new \DateTimeImmutable($v ?? 'now');
        $dt = fn ($v) => $v ? ($v instanceof \DateTimeInterface ? $v : new \DateTimeImmutable($v)) : null;

        return new PerformanceReview($m->tenant_id, $m->employee_id, $m->cycle_id, $m->reviewer_id, $m->overall_rating ? PerformanceRating::from($m->overall_rating) : null, $m->goals ?? [], $m->strengths ?? '', $m->improvements ?? '', $m->reviewer_comments ?? '', $m->employee_comments ?? '', $m->status, $dt($m->acknowledged_at), $m->metadata ?? [], $now($m->created_at), $now($m->updated_at), $m->id);
    }
}
