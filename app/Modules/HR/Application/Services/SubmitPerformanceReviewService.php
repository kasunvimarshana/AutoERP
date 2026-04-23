<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\HR\Application\Contracts\SubmitPerformanceReviewServiceInterface;
use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Domain\Exceptions\PerformanceReviewNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;

class SubmitPerformanceReviewService extends BaseService implements SubmitPerformanceReviewServiceInterface
{
    public function __construct(
        private readonly PerformanceReviewRepositoryInterface $reviewRepository,
    ) {
        parent::__construct($this->reviewRepository);
    }

    protected function handle(array $data): PerformanceReview
    {
        $id = (int) ($data['id'] ?? 0);
        $review = $this->reviewRepository->find($id);

        if ($review === null) {
            throw new PerformanceReviewNotFoundException($id);
        }

        if ($review->getStatus() !== 'draft') {
            throw new DomainException('Only draft performance reviews can be submitted.');
        }

        $review->submit();

        return $this->reviewRepository->save($review);
    }
}
