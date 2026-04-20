<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\FindApTransactionServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ApTransactionRepositoryInterface;

class FindApTransactionService extends BaseService implements FindApTransactionServiceInterface
{
    public function __construct(private readonly ApTransactionRepositoryInterface $apTransactionRepository)
    {
        parent::__construct($apTransactionRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
