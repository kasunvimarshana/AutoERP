<?php
namespace Modules\Accounting\Application\Contracts;

use Modules\Accounting\Application\DTOs\PaymentData;
use Modules\Accounting\Domain\Entities\Payment;

interface ProcessPaymentServiceInterface
{
    public function execute(PaymentData $data): Payment;
}
