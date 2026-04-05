<?php
declare(strict_types=1);
namespace Modules\CRM\Application\Services;

use Modules\CRM\Domain\Entities\Activity;
use Modules\CRM\Domain\Exceptions\ActivityNotFoundException;
use Modules\CRM\Domain\RepositoryInterfaces\ActivityRepositoryInterface;

class ActivityService
{
    public function __construct(private readonly ActivityRepositoryInterface $repository) {}

    public function findById(int $id): Activity
    {
        $activity = $this->repository->findById($id);
        if ($activity === null) throw new ActivityNotFoundException($id);
        return $activity;
    }

    public function findAllByTenant(int $tenantId, array $filters = []): array
    {
        return $this->repository->findAllByTenant($tenantId, $filters);
    }

    public function create(array $data): Activity
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Activity
    {
        $this->findById($id);
        return $this->repository->update($id, $data) ?? $this->findById($id);
    }

    public function complete(int $id, ?string $description = null): Activity
    {
        $activity = $this->findById($id);
        $activity->complete($description);
        return $this->repository->update($id, [
            'status'       => Activity::STATUS_COMPLETED,
            'completed_at' => new \DateTimeImmutable(),
            'description'  => $activity->getDescription(),
        ]) ?? $activity;
    }

    public function cancel(int $id): Activity
    {
        $activity = $this->findById($id);
        $activity->cancel();
        return $this->repository->update($id, ['status' => Activity::STATUS_CANCELLED]) ?? $activity;
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }
}
