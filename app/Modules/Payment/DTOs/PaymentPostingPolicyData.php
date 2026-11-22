<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\DTOs;

use Modules\Payment\Enums\PaymentPostingRole;

final readonly class PaymentPostingPolicyData
{
    public function __construct(
        public string $postingProfileCode,
        public PaymentPostingRole $allocatedRole,
        public PaymentPostingRole $unappliedRole,
        public PaymentPostingRole $allocationTargetRole,
    ) {}
}
