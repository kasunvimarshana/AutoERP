<?php

declare(strict_types=1);

namespace Modules\Payment\Constants;

final class PaymentIdempotency
{
    public const CREATE_OPERATION = 'payment.create';
    public const REQUEST_HEADER = 'Idempotency-Key';
    public const REQUEST_ATTRIBUTE = 'idempotency_key';
    public const PAYMENT_ID_KEY = 'payment_id';
    public const MAX_KEY_LENGTH = 255;
}
