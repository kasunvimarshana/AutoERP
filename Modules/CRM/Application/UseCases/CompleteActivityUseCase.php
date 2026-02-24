<?php
namespace Modules\CRM\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\CRM\Domain\Contracts\ActivityRepositoryInterface;
use Modules\CRM\Domain\Events\ActivityCompleted;
class CompleteActivityUseCase
{
    public function __construct(private ActivityRepositoryInterface $repo) {}
    public function execute(string $activityId, array $data = []): object
    {
        return DB::transaction(function () use ($activityId, $data) {
            $activity = $this->repo->findById($activityId);
            if (!$activity) throw new \RuntimeException('Activity not found.');
            $updated = $this->repo->update($activityId, array_merge($data, [
                'status' => 'done',
                'completed_at' => now(),
            ]));
            Event::dispatch(new ActivityCompleted($activityId));
            return $updated;
        });
    }
}
