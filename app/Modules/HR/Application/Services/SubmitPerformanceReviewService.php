<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\SubmitPerformanceReviewServiceInterface;
use Modules\HR\Domain\Events\PerformanceReviewSubmitted;
use Modules\HR\Domain\Exceptions\PerformanceReviewNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;

class SubmitPerformanceReviewService extends BaseService implements SubmitPerformanceReviewServiceInterface
{
    public function __construct(private readonly PerformanceReviewRepositoryInterface $performanceReviewRepository)
    {
        parent::__construct($performanceReviewRepository);
    }

    protected function handle(array $data): mixed
    {
        $id     = $data['id'];
        $review = $this->performanceReviewRepository->find($id);
        if (! $review) {
            throw new PerformanceReviewNotFoundException($id);
        }

        $review->submit();

        $saved = $this->performanceReviewRepository->save($review);
        $this->addEvent(new PerformanceReviewSubmitted($saved));

        return $saved;
    }
}
