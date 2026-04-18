<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeleteFiscalPeriodServiceInterface;
use Modules\Finance\Domain\Exceptions\FiscalPeriodNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;

class DeleteFiscalPeriodService extends BaseService implements DeleteFiscalPeriodServiceInterface
{
    public function __construct(private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository)
    {
        parent::__construct($fiscalPeriodRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $fiscalPeriod = $this->fiscalPeriodRepository->find($id);

        if (! $fiscalPeriod) {
            throw FiscalPeriodNotFoundException::byId($id);
        }

        return $this->fiscalPeriodRepository->delete($id);
    }
}
