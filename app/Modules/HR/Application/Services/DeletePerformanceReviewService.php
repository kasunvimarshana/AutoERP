<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\DeletePerformanceReviewServiceInterface;
use Modules\HR\Domain\Events\PerformanceReviewDeleted;
use Modules\HR\Domain\Exceptions\PerformanceReviewNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;

class DeletePerformanceReviewService extends BaseService implements DeletePerformanceReviewServiceInterface
{
    public function __construct(private readonly PerformanceReviewRepositoryInterface $performanceReviewRepository)
    {
        parent::__construct($performanceReviewRepository);
    }

    protected function handle(array $data): bool
    {
        $id     = $data['id'];
        $review = $this->performanceReviewRepository->find($id);
        if (! $review) {
            throw new PerformanceReviewNotFoundException($id);
        }

        $tenantId = $review->getTenantId();
        $deleted  = $this->performanceReviewRepository->delete($id);
        if ($deleted) {
            $this->addEvent(new PerformanceReviewDeleted($tenantId, $id));
        }

        return $deleted;
    }
}
