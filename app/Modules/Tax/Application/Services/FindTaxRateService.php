<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tax\Application\Contracts\FindTaxRateServiceInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;

class FindTaxRateService extends BaseService implements FindTaxRateServiceInterface
{
    protected array $allowedSortColumns = ['id', 'name', 'rate', 'is_compound', 'is_active', 'created_at', 'updated_at'];

    protected array $allowedFilterFields = ['tenant_id', 'tax_group_id', 'name', 'type', 'is_compound', 'is_active'];

    public function __construct(TaxRateRepositoryInterface $taxRateRepository)
    {
        parent::__construct($taxRateRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
