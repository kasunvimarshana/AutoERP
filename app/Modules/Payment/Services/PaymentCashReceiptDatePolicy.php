<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Services;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Configuration\Constants\ConfigurationKey;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Contracts\TimezoneResolverInterface;
use Modules\Payment\Constants\PaymentPermission;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;

final class PaymentCashReceiptDatePolicy
{
    public function __construct(
        private readonly ConfigurationResolverInterface $configuration,
        private readonly PermissionCheckerInterface $permissions,
        private readonly ?TimezoneResolverInterface $timezoneResolver = null,
    ) {}

    public function assertCanPost(Payment $payment, ?int $postedBy): void
    {
        $today = $this->timezoneResolver !== null
            ? $this->timezoneResolver->todayDateString((int) $payment->tenant_id, $payment->organization_unit_id)
            : CarbonImmutable::now(
                (string) ($this->configuration->value(
                    ConfigurationKey::WORKSPACE_TIMEZONE,
                    (int) $payment->tenant_id,
                    $payment->organization_unit_id,
                ) ?: config('app.timezone', 'UTC'))
            )->toDateString();
        $paymentDate = $payment->payment_date->toDateString();

        $direction = $payment->direction instanceof PaymentDirection
            ? $payment->direction
            : PaymentDirection::tryFrom((string) $payment->direction);
        $hasCash = $payment->lines->contains(fn (PaymentLine $line): bool => (string) $line->payment_method_type_snapshot === PaymentMethodType::Cash->value);
        $isCashReceipt = $direction === PaymentDirection::Inbound && $hasCash;

        if ($paymentDate > $today) {
            throw new InvalidArgumentException($isCashReceipt ? 'Cash receipt date cannot be in the future.' : 'Payment date cannot be in the future.');
        }

        if ($postedBy === null || ! $isCashReceipt) {
            return;
        }

        if ($paymentDate < $today && ! $this->permissions->allows($postedBy, (int) $payment->tenant_id, PaymentPermission::PAYMENTS_BACKDATE_CASH)) {
            throw new AuthorizationException('Backdated cash receipts require the backdate-cash permission.');
        }
        $paymentType = $payment->payment_type instanceof PaymentType
            ? $payment->payment_type
            : PaymentType::tryFrom((string) $payment->payment_type);
        $isServiceReceipt = $paymentType === PaymentType::ServiceReceipt;
        $reason = $isServiceReceipt
            ? ($payment->metadata['backdate_reason'] ?? null)
            : $payment->notes;
        if ($paymentDate < $today && trim((string) $reason) === '') {
            $field = $isServiceReceipt ? 'backdate_reason' : 'notes';
            throw ValidationException::withMessages([$field => ['A reason is required for a backdated cash receipt.']]);
        }
    }
}
