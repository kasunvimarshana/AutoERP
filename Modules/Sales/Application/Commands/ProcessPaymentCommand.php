<?php
declare(strict_types=1);
namespace Modules\Sales\Application\Commands;
final readonly class ProcessPaymentCommand {
    public function __construct(
        public int     $saleId,
        public int     $tenantId,
        public string  $amount,
        public string  $paymentMethod,
        public ?string $referenceNumber = null,
        public ?string $notes           = null,
        public ?int    $receivedBy      = null,
    ) {}
}
