<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\User\Services\UserAccessResolver;

final class PaymentAuthorizationService
{
    public const PAYMENTS_VIEW = 'payments.view';

    public const PAYMENTS_CREATE = 'payments.create';

    public const PAYMENTS_UPDATE = 'payments.update';

    public const PAYMENTS_SUBMIT = 'payments.submit';

    public const PAYMENTS_APPROVE = 'payments.approve';

    public const PAYMENTS_POST = 'payments.post';

    public const PAYMENTS_VOID = 'payments.void';

    public const PAYMENTS_REVERSE = 'payments.reverse';

    public const PAYMENTS_ALLOCATE = 'payments.allocate';

    public const PAYMENTS_REFUND = 'payments.refund';

    public const PAYMENTS_SETTLE = 'payments.settle';

    public const METHODS_VIEW = 'payment-methods.view';

    public const METHODS_CREATE = 'payment-methods.create';

    public const METHODS_UPDATE = 'payment-methods.update';

    public const METHODS_DELETE = 'payment-methods.delete';

    public const TEMPLATES_VIEW = 'cheque-templates.view';

    public const TEMPLATES_CREATE = 'cheque-templates.create';

    public const TEMPLATES_UPDATE = 'cheque-templates.update';

    public const TEMPLATES_DELETE = 'cheque-templates.delete';

    public const CHEQUES_PREVIEW = 'cheques.preview';

    public const CHEQUES_PRINT = 'cheques.print';

    public function __construct(private readonly UserAccessResolver $access) {}

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            self::PAYMENTS_VIEW => 'View payment registers and payment details.',
            self::PAYMENTS_CREATE => 'Create draft payments and receipts.',
            self::PAYMENTS_UPDATE => 'Update editable draft payments.',
            self::PAYMENTS_SUBMIT => 'Submit payments for approval.',
            self::PAYMENTS_APPROVE => 'Approve submitted payments.',
            self::PAYMENTS_POST => 'Post approved payments to Finance journals.',
            self::PAYMENTS_VOID => 'Void eligible unposted payments.',
            self::PAYMENTS_REVERSE => 'Reverse posted payments and linked effects.',
            self::PAYMENTS_ALLOCATE => 'Allocate posted payments to invoices.',
            self::PAYMENTS_REFUND => 'Create linked refund payments.',
            self::PAYMENTS_SETTLE => 'Settle payment instruments and lines.',
            self::METHODS_VIEW => 'View payment method setup.',
            self::METHODS_CREATE => 'Create payment methods.',
            self::METHODS_UPDATE => 'Update, activate, or deactivate payment methods.',
            self::METHODS_DELETE => 'Delete unused payment methods.',
            self::TEMPLATES_VIEW => 'View cheque templates.',
            self::TEMPLATES_CREATE => 'Create cheque templates.',
            self::TEMPLATES_UPDATE => 'Update, activate, deactivate, or default cheque templates.',
            self::TEMPLATES_DELETE => 'Delete unused cheque templates.',
            self::CHEQUES_PREVIEW => 'Preview cheques for cheque-capable payment lines.',
            self::CHEQUES_PRINT => 'Print and record cheque print activity.',
        ];
    }

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This Payment action requires permission: '.$permission);
        }
    }

    public function can(?int $userId, int $tenantId, string $permission): bool
    {
        return $userId !== null && $this->access->can($userId, $tenantId, $permission);
    }
}
