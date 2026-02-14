<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\BaseService;
use App\Modules\CRM\Repositories\CampaignRepository;
use Illuminate\Support\Facades\Log;

class CampaignService extends BaseService
{
    /**
     * CampaignService constructor
     */
    public function __construct(CampaignRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get active campaigns
     */
    public function getActive()
    {
        try {
            return $this->repository->getActive();
        } catch (\Exception $e) {
            Log::error('Error fetching active campaigns: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get campaigns by type
     */
    public function getByType(string $type)
    {
        try {
            return $this->repository->getByType($type);
        } catch (\Exception $e) {
            Log::error("Error fetching campaigns by type {$type}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate campaign ROI
     */
    public function calculateROI(int $campaignId): array
    {
        try {
            $campaign = $this->repository->findOrFail($campaignId);

            $cost = $campaign->budget ?? 0;
            $revenue = $campaign->revenue_generated ?? 0;
            $roi = $cost > 0 ? (($revenue - $cost) / $cost) * 100 : 0;

            return [
                'campaign_id' => $campaignId,
                'campaign_name' => $campaign->name,
                'total_cost' => $cost,
                'revenue_generated' => $revenue,
                'roi_percentage' => round($roi, 2),
                'profit' => $revenue - $cost,
            ];
        } catch (\Exception $e) {
            Log::error("Error calculating ROI for campaign {$campaignId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get campaign performance
     */
    public function getPerformance(int $campaignId): array
    {
        try {
            $campaign = $this->repository->findOrFail($campaignId);

            return [
                'campaign_id' => $campaignId,
                'name' => $campaign->name,
                'leads_generated' => $campaign->leads_count ?? 0,
                'opportunities_created' => $campaign->opportunities_count ?? 0,
                'conversions' => $campaign->conversions_count ?? 0,
                'conversion_rate' => $campaign->leads_count > 0
                    ? round(($campaign->conversions_count / $campaign->leads_count) * 100, 2)
                    : 0,
                'budget' => $campaign->budget ?? 0,
                'spent' => $campaign->spent ?? 0,
                'revenue' => $campaign->revenue_generated ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching performance for campaign {$campaignId}: ".$e->getMessage());
            throw $e;
        }
    }
}
