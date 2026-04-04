<?php
namespace Modules\UoM\Application\Contracts;

interface DeleteUomCategoryServiceInterface
{
    public function execute(int $id): bool;
}
