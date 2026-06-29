<?php

declare(strict_types=1);

namespace Modules\Payment\Constants;

final class PaymentPermission
{
    public const VIEW = 'payments.view';
    public const CREATE = 'payments.create';
    public const SUBMIT = 'payments.submit';
    public const APPROVE = 'payments.approve';
    public const POST = 'payments.post';
    public const VOID = 'payments.void';
    public const REVERSE = 'payments.reverse';
    public const ALLOCATE = 'payments.allocate';
    public const SETTLE = 'payments.settle';
    public const REFUND = 'payments.refund';
    public const METHODS_VIEW = 'payment_methods.view';
    public const METHODS_MANAGE = 'payment_methods.manage';
    public const CHEQUES_VIEW = 'payment_cheques.view';
    public const CHEQUES_PRINT = 'payment_cheques.print';
    public const TEMPLATES_VIEW = 'payment_cheque_templates.view';
    public const TEMPLATES_MANAGE = 'payment_cheque_templates.manage';

    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View payments and lifecycle details.',
            self::CREATE => 'Create draft payments.',
            self::SUBMIT => 'Submit payments for approval.',
            self::APPROVE => 'Approve submitted payments.',
            self::POST => 'Post approved payments.',
            self::VOID => 'Void unposted payments.',
            self::REVERSE => 'Reverse posted payments.',
            self::ALLOCATE => 'Allocate payments to invoices.',
            self::SETTLE => 'Settle payment instruments.',
            self::REFUND => 'Create payment refunds.',
            self::METHODS_VIEW => 'View payment methods.',
            self::METHODS_MANAGE => 'Manage payment methods.',
            self::CHEQUES_VIEW => 'View cheque payment details.',
            self::CHEQUES_PRINT => 'Print cheque payments.',
            self::TEMPLATES_VIEW => 'View cheque templates.',
            self::TEMPLATES_MANAGE => 'Manage cheque templates.',
        ];
    }
}
