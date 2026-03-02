<?php
declare(strict_types=1);
namespace Modules\Sales\Application\Commands;
final readonly class CreateSaleCommand {
    public function __construct(
        public int     $tenantId,
        public int     $organisationId,
        public ?int    $customerId,
        public array   $lines,
        public string  $discountPercent = '0',
        public string  $taxPercent      = '0',
        public string  $paymentMethod   = 'cash',
        public ?string $notes           = null,
        public ?string $saleDate        = null,
        public ?int    $cashRegisterId  = null,
        public ?int    $createdBy       = null,
    ) {}
}
