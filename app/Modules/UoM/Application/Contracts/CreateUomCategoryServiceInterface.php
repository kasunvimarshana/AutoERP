<?php
namespace Modules\UoM\Application\Contracts;
use Modules\UoM\Application\DTOs\UomCategoryData;
use Modules\UoM\Domain\Entities\UomCategory;

interface CreateUomCategoryServiceInterface
{
    public function execute(UomCategoryData $data): UomCategory;
}
