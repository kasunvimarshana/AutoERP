<?php
namespace Modules\UoM\Application\Contracts;
use Modules\UoM\Application\DTOs\UomCategoryData;
use Modules\UoM\Domain\Entities\UomCategory;

interface UpdateUomCategoryServiceInterface
{
    public function execute(int $id, UomCategoryData $data): UomCategory;
}
