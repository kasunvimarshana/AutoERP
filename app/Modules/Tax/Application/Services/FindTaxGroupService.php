<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tax\Application\Contracts\FindTaxGroupServiceInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;

class FindTaxGroupService extends BaseService implements FindTaxGroupServiceInterface
{
    protected array $allowedSortColumns = ['id', 'name', 'created_at', 'updated_at'];

    protected array $allowedFilterFields = ['tenant_id', 'name'];

    public function __construct(TaxGroupRepositoryInterface $taxGroupRepository)
    {
        parent::__construct($taxGroupRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
