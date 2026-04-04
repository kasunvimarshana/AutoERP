<?php
namespace Modules\UoM\Application\Contracts;

interface DeleteUomConversionServiceInterface
{
    public function execute(int $id): bool;
}
