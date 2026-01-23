<?php

namespace App\Modules\JobCardManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\JobCardManagement\Events\InspectionCompleted;
use App\Modules\JobCardManagement\Repositories\DigitalInspectionRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DigitalInspectionService extends BaseService
{
    public function __construct(DigitalInspectionRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Complete an inspection
     */
    public function complete(int $inspectionId): Model
    {
        try {
            DB::beginTransaction();

            $inspection = $this->repository->findOrFail($inspectionId);
            $inspection->status = 'completed';
            $inspection->completed_at = now();
            $inspection->save();

            event(new InspectionCompleted($inspection));

            DB::commit();

            return $inspection;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Add inspection item
     */
    public function addItem(int $inspectionId, array $itemData): void
    {
        $inspection = $this->repository->findOrFail($inspectionId);
        $items = $inspection->items ?? [];
        $items[] = array_merge($itemData, ['id' => uniqid(), 'created_at' => now()]);
        
        $this->update($inspectionId, ['items' => $items]);
    }

    /**
     * Update inspection item
     */
    public function updateItem(int $inspectionId, string $itemId, array $itemData): Model
    {
        $inspection = $this->repository->findOrFail($inspectionId);
        $items = $inspection->items ?? [];
        
        foreach ($items as $key => $item) {
            if ($item['id'] === $itemId) {
                $items[$key] = array_merge($item, $itemData);
                break;
            }
        }
        
        return $this->update($inspectionId, ['items' => $items]);
    }

    /**
     * Get inspections by job card
     */
    public function getByJobCard(int $jobCardId)
    {
        return $this->repository->getByJobCard($jobCardId);
    }

    /**
     * Get inspections by vehicle
     */
    public function getByVehicle(int $vehicleId)
    {
        return $this->repository->getByVehicle($vehicleId);
    }

    /**
     * Generate inspection report
     */
    public function generateReport(int $inspectionId): array
    {
        $inspection = $this->repository->findOrFail($inspectionId);
        
        return [
            'inspection_id' => $inspection->id,
            'vehicle_id' => $inspection->vehicle_id,
            'inspector' => $inspection->inspector_name,
            'date' => $inspection->inspection_date,
            'items' => $inspection->items,
            'overall_status' => $inspection->status,
            'recommendations' => $inspection->recommendations ?? []
        ];
    }

    /**
     * Calculate inspection score
     */
    public function calculateScore(int $inspectionId): float
    {
        $inspection = $this->repository->findOrFail($inspectionId);
        $items = $inspection->items ?? [];
        
        if (empty($items)) {
            return 0;
        }
        
        $totalScore = 0;
        foreach ($items as $item) {
            $totalScore += $item['condition_score'] ?? 0;
        }
        
        return $totalScore / count($items);
    }
}
