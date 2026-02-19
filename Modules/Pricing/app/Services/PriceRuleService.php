<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use App\Core\Services\BaseService;
use Modules\Pricing\Repositories\PriceRuleRepository;

/**
 * PriceRule Service
 *
 * Contains business logic for PriceRule operations
 */
class PriceRuleService extends BaseService
{
    public function __construct(PriceRuleRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new price rule
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): mixed
    {
        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        if (! isset($data['priority'])) {
            $data['priority'] = 0;
        }

        return parent::create($data);
    }

    /**
     * Get active rules
     */
    public function getActive(): mixed
    {
        return $this->repository->getActive();
    }

    /**
     * Get active rules ordered by priority
     */
    public function getActiveRulesOrderedByPriority(): mixed
    {
        return $this->repository->getActiveRulesOrderedByPriority();
    }

    /**
     * Search rules
     */
    public function search(string $query): mixed
    {
        return $this->repository->search($query);
    }
}
