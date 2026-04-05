<?php
declare(strict_types=1);
namespace Modules\SalesOrder\Application\Contracts;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
interface CreateSalesOrderServiceInterface { public function execute(array $data, array $lines): SalesOrder; }
