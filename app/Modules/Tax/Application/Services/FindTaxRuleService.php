<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tax\Application\Contracts\FindTaxRuleServiceInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRuleRepositoryInterface;

class FindTaxRuleService extends BaseService implements FindTaxRuleServiceInterface
{
    protected array $allowedSortColumns = ['id', 'priority', 'created_at', 'updated_at'];

    protected array $allowedFilterFields = ['tenant_id', 'tax_group_id', 'product_category_id', 'party_type', 'region', 'priority'];

    public function __construct(TaxRuleRepositoryInterface $taxRuleRepository)
    {
        parent::__construct($taxRuleRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
