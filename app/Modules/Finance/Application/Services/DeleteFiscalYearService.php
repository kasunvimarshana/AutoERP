<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeleteFiscalYearServiceInterface;
use Modules\Finance\Domain\Exceptions\FiscalYearNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalYearRepositoryInterface;

class DeleteFiscalYearService extends BaseService implements DeleteFiscalYearServiceInterface
{
    public function __construct(private readonly FiscalYearRepositoryInterface $fiscalYearRepository)
    {
        parent::__construct($fiscalYearRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $fiscalYear = $this->fiscalYearRepository->find($id);

        if (! $fiscalYear) {
            throw new FiscalYearNotFoundException($id);
        }

        return $this->fiscalYearRepository->delete($id);
    }
}
