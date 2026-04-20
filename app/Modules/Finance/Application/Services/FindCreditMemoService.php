<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\FindCreditMemoServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;

class FindCreditMemoService extends BaseService implements FindCreditMemoServiceInterface
{
    public function __construct(private readonly CreditMemoRepositoryInterface $creditMemoRepository)
    {
        parent::__construct($creditMemoRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
