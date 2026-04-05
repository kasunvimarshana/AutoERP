<?php
declare(strict_types=1);
namespace Modules\Returns\Application\Services;

use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Returns\Application\Contracts\CompleteReturnServiceInterface;
use Modules\Returns\Domain\Entities\ReturnRequest;
use Modules\Returns\Domain\Events\ReturnCompleted;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnRequestRepositoryInterface;

class CompleteReturnService implements CompleteReturnServiceInterface
{
    public function __construct(private readonly ReturnRequestRepositoryInterface $repo) {}

    public function execute(int $returnRequestId): ReturnRequest
    {
        $ret = $this->repo->findById($returnRequestId);
        if (!$ret) {
            throw new NotFoundException('ReturnRequest', $returnRequestId);
        }

        $ret->complete();
        $this->repo->update($returnRequestId, ['status' => ReturnRequest::STATUS_COMPLETED]);

        event(new ReturnCompleted($ret->getTenantId(), $returnRequestId));

        return $this->repo->findById($returnRequestId);
    }
}
