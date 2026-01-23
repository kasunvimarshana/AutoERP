<?php

declare(strict_types=1);

namespace Modules\JobCard\Services;

use App\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\JobCard\Repositories\InspectionItemRepository;

/**
 * InspectionItem Service
 *
 * Contains business logic for InspectionItem operations
 */
class InspectionItemService extends BaseService
{
    /**
     * InspectionItemService constructor
     */
    public function __construct(InspectionItemRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new inspection item
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): mixed
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $inspectionItem = parent::create($data);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $inspectionItem;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    /**
     * Add inspection item to job card
     *
     * @param  array<string, mixed>  $itemData
     */
    public function addToJobCard(int $jobCardId, array $itemData): mixed
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $itemData['job_card_id'] = $jobCardId;
            $inspectionItem = $this->create($itemData);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $inspectionItem;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    /**
     * Add photos to inspection item
     *
     * @param  array<int, string>  $photos
     */
    public function addPhotos(int $id, array $photos): mixed
    {
        $inspectionItem = $this->repository->findOrFail($id);

        $existingPhotos = $inspectionItem->photos ?? [];
        $updatedPhotos = array_merge($existingPhotos, $photos);

        return $this->update($id, ['photos' => $updatedPhotos]);
    }

    /**
     * Get inspection items for job card
     */
    public function getForJobCard(int $jobCardId): mixed
    {
        return $this->repository->getForJobCard($jobCardId);
    }

    /**
     * Get items needing attention
     */
    public function getNeedingAttention(): mixed
    {
        return $this->repository->getNeedingAttention();
    }
}
