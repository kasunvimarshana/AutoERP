<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\User\Services\UserAccessResolver;

final class PurchaseAuthorizationService
{
    public const ORDERS_VIEW = 'purchase.orders.view';
    public const ORDERS_CREATE = 'purchase.orders.create';
    public const ORDERS_UPDATE = 'purchase.orders.update';
    public const ORDERS_SUBMIT = 'purchase.orders.submit';
    public const ORDERS_APPROVE = 'purchase.orders.approve';
    public const ORDERS_CANCEL = 'purchase.orders.cancel';
    public const ORDERS_CLOSE = 'purchase.orders.close';
    public const ORDERS_DELETE = 'purchase.orders.delete';

    public const GOODS_RECEIPTS_VIEW = 'purchase.goods_receipts.view';
    public const GOODS_RECEIPTS_CREATE = 'purchase.goods_receipts.create';
    public const GOODS_RECEIPTS_UPDATE = 'purchase.goods_receipts.update';
    public const GOODS_RECEIPTS_POST = 'purchase.goods_receipts.post';
    public const GOODS_RECEIPTS_REVERSE = 'purchase.goods_receipts.reverse';
    public const GOODS_RECEIPTS_CANCEL = 'purchase.goods_receipts.cancel';

    public const SUPPLIER_INVOICES_VIEW = 'purchase.supplier_invoices.view';
    public const SUPPLIER_INVOICES_CREATE = 'purchase.supplier_invoices.create';
    public const SUPPLIER_INVOICES_UPDATE = 'purchase.supplier_invoices.update';
    public const SUPPLIER_INVOICES_POST = 'purchase.supplier_invoices.post';
    public const SUPPLIER_INVOICES_CANCEL = 'purchase.supplier_invoices.cancel';

    public const RETURNS_VIEW = 'purchase.returns.view';
    public const RETURNS_CREATE = 'purchase.returns.create';
    public const RETURNS_CREATE_MANUAL = 'purchase.returns.create_manual';
    public const RETURNS_UPDATE = 'purchase.returns.update';
    public const RETURNS_APPROVE = 'purchase.returns.approve';
    public const RETURNS_POST = 'purchase.returns.post';
    public const RETURNS_REVERSE = 'purchase.returns.reverse';
    public const RETURNS_CANCEL = 'purchase.returns.cancel';

    public const DEBIT_NOTES_VIEW = 'purchase.debit_notes.view';
    public const DEBIT_NOTES_CREATE = 'purchase.debit_notes.create';
    public const DEBIT_NOTES_UPDATE = 'purchase.debit_notes.update';
    public const DEBIT_NOTES_APPROVE = 'purchase.debit_notes.approve';
    public const DEBIT_NOTES_POST = 'purchase.debit_notes.post';
    public const DEBIT_NOTES_ALLOCATE = 'purchase.debit_notes.allocate';
    public const DEBIT_NOTES_REVERSE = 'purchase.debit_notes.reverse';
    public const DEBIT_NOTES_CANCEL = 'purchase.debit_notes.cancel';

    public const PAYMENTS_VIEW = 'purchase.payments.view';
    public const PAYMENTS_EXECUTE = 'purchase.payments.execute';

    public const FAST_PURCHASE_VIEW = 'purchase.fast_purchases.view';
    public const FAST_PURCHASE_EXECUTE = 'purchase.fast_purchases.execute';

    public function __construct(private readonly UserAccessResolver $access) {}

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            self::ORDERS_VIEW => 'View purchase orders and purchase order lookups.',
            self::ORDERS_CREATE => 'Create draft purchase orders.',
            self::ORDERS_UPDATE => 'Update draft purchase orders.',
            self::ORDERS_SUBMIT => 'Submit purchase orders for approval.',
            self::ORDERS_APPROVE => 'Approve submitted purchase orders.',
            self::ORDERS_CANCEL => 'Cancel eligible purchase orders.',
            self::ORDERS_CLOSE => 'Close fulfilled purchase orders.',
            self::ORDERS_DELETE => 'Delete unused draft purchase orders.',
            self::GOODS_RECEIPTS_VIEW => 'View goods receipts and receivable or returnable line lookups.',
            self::GOODS_RECEIPTS_CREATE => 'Create draft goods receipts.',
            self::GOODS_RECEIPTS_UPDATE => 'Update draft goods receipts.',
            self::GOODS_RECEIPTS_POST => 'Post goods receipts to inventory and tax.',
            self::GOODS_RECEIPTS_REVERSE => 'Reverse eligible posted goods receipts.',
            self::GOODS_RECEIPTS_CANCEL => 'Cancel unused draft goods receipts.',
            self::SUPPLIER_INVOICES_VIEW => 'Preview and view supplier invoice source data.',
            self::SUPPLIER_INVOICES_CREATE => 'Create supplier invoices from eligible purchase sources.',
            self::SUPPLIER_INVOICES_UPDATE => 'Update draft supplier invoices owned by Purchase.',
            self::SUPPLIER_INVOICES_POST => 'Post supplier invoices owned by Purchase.',
            self::SUPPLIER_INVOICES_CANCEL => 'Cancel supplier invoices owned by Purchase.',
            self::RETURNS_VIEW => 'View purchase returns.',
            self::RETURNS_CREATE => 'Create draft purchase returns.',
            self::RETURNS_CREATE_MANUAL => 'Create restricted manual supplier returns.',
            self::RETURNS_UPDATE => 'Update draft purchase returns.',
            self::RETURNS_APPROVE => 'Approve purchase returns.',
            self::RETURNS_POST => 'Post purchase returns.',
            self::RETURNS_REVERSE => 'Reverse eligible purchase returns.',
            self::RETURNS_CANCEL => 'Cancel draft or approved purchase returns.',
            self::DEBIT_NOTES_VIEW => 'View purchase debit notes.',
            self::DEBIT_NOTES_CREATE => 'Create purchase debit notes.',
            self::DEBIT_NOTES_UPDATE => 'Update draft purchase debit notes.',
            self::DEBIT_NOTES_APPROVE => 'Approve purchase debit notes.',
            self::DEBIT_NOTES_POST => 'Post purchase debit notes.',
            self::DEBIT_NOTES_ALLOCATE => 'Allocate purchase debit notes to supplier invoices.',
            self::DEBIT_NOTES_REVERSE => 'Reverse purchase debit notes.',
            self::DEBIT_NOTES_CANCEL => 'Cancel purchase debit notes.',
            self::PAYMENTS_VIEW => 'View supplier payment workspace data.',
            self::PAYMENTS_EXECUTE => 'Create supplier payments through the Payment module.',
            self::FAST_PURCHASE_VIEW => 'View Fast Purchase context and previews.',
            self::FAST_PURCHASE_EXECUTE => 'Execute Fast Purchase workflows.',
        ];
    }

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This Purchase action requires permission: '.$permission);
        }
    }

    public function can(int $userId, int $tenantId, string $permission): bool
    {
        return $this->access->can($userId, $tenantId, $permission);
    }
}
