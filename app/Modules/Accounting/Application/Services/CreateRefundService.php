<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Services;

use Modules\Accounting\Application\Contracts\CreateRefundServiceInterface;
use Modules\Accounting\Domain\Entities\Refund;
use Modules\Accounting\Domain\RepositoryInterfaces\RefundRepositoryInterface;

class CreateRefundService implements CreateRefundServiceInterface
{
    public function __construct(private readonly RefundRepositoryInterface $repo) {}

    public function execute(array $data): Refund
    {
        return $this->repo->create($data);
    }
}
