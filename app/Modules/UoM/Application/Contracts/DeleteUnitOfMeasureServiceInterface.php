<?php
namespace Modules\UoM\Application\Contracts;

interface DeleteUnitOfMeasureServiceInterface
{
    public function execute(int $id): bool;
}
