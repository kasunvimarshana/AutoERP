<?php
declare(strict_types=1);
namespace Modules\SalesOrder\Application\Contracts;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
interface StartPackingSalesOrderServiceInterface { public function execute(int $id): SalesOrder; }
