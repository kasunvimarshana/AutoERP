<?php
declare(strict_types=1);
namespace Modules\PurchaseOrder\Domain\Exceptions;
use Modules\Core\Domain\Exceptions\NotFoundException;
class PurchaseOrderNotFoundException extends NotFoundException {
    public function __construct(int|string $id) { parent::__construct("PurchaseOrder [{$id}] not found."); }
}
