<?php
declare(strict_types=1);
namespace Modules\Procurement\Application\Commands;
final readonly class CreatePurchaseOrderCommand {
    public function __construct(
        public int     $tenantId,
        public int     $vendorId,
        public array   $lines,
        public ?string $expectedDeliveryDate = null,
        public ?string $notes = null,
        public ?int    $createdBy = null,
    ) {}
}
