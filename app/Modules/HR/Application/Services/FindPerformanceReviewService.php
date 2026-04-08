<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\FindPerformanceReviewServiceInterface;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;

class FindPerformanceReviewService extends BaseService implements FindPerformanceReviewServiceInterface
{
    public function __construct(private readonly PerformanceReviewRepositoryInterface $performanceReviewRepository)
    {
        parent::__construct($performanceReviewRepository);
    }

    /**
     * @return array<int, \Modules\HR\Domain\Entities\PerformanceReview>
     */
    public function getByEmployee(int $employeeId): array
    {
        return $this->performanceReviewRepository->getByEmployee($employeeId);
    }

    /**
     * @return array<int, \Modules\HR\Domain\Entities\PerformanceReview>
     */
    public function getByReviewer(int $reviewerId): array
    {
        return $this->performanceReviewRepository->getByReviewer($reviewerId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
