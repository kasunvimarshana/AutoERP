<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\BaseService;
use App\Modules\CRM\Repositories\OpportunityRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OpportunityService extends BaseService
{
    /**
     * OpportunityService constructor
     */
    public function __construct(OpportunityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update opportunity stage
     */
    public function updateStage(int $opportunityId, string $stage, array $additionalData = []): bool
    {
        DB::beginTransaction();

        try {
            $data = array_merge([
                'stage' => $stage,
                'stage_changed_at' => now(),
            ], $additionalData);

            if ($stage === 'won') {
                $data['won_at'] = now();
                $data['is_won'] = true;
            } elseif ($stage === 'lost') {
                $data['lost_at'] = now();
                $data['is_lost'] = true;
            }

            $result = $this->repository->update($opportunityId, $data);
            DB::commit();

            Log::info("Opportunity {$opportunityId} stage updated to {$stage}");

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating opportunity stage: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get opportunities by stage
     */
    public function getByStage(string $stage)
    {
        try {
            return $this->repository->getByStage($stage);
        } catch (\Exception $e) {
            Log::error("Error fetching opportunities by stage {$stage}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get won opportunities
     */
    public function getWon()
    {
        try {
            return $this->repository->getWon();
        } catch (\Exception $e) {
            Log::error('Error fetching won opportunities: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate win rate
     */
    public function calculateWinRate(?int $userId = null): array
    {
        try {
            $query = $this->repository->model;

            if ($userId) {
                $query = $query->where('assigned_to', $userId);
            }

            $total = $query->count();
            $won = $query->where('stage', 'won')->count();
            $lost = $query->where('stage', 'lost')->count();
            $active = $total - $won - $lost;

            $winRate = $total > 0 ? ($won / $total) * 100 : 0;

            return [
                'total_opportunities' => $total,
                'won' => $won,
                'lost' => $lost,
                'active' => $active,
                'win_rate' => round($winRate, 2),
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating win rate: '.$e->getMessage());
            throw $e;
        }
    }
}
