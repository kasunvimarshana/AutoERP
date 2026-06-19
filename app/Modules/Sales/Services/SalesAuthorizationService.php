<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\User\Services\UserAccessResolver;

final class SalesAuthorizationService
{
    public const QUOTATIONS_VIEW = 'sales.quotations.view';

    public const QUOTATIONS_CREATE = 'sales.quotations.create';

    public const QUOTATIONS_UPDATE = 'sales.quotations.update';

    public const QUOTATIONS_SEND = 'sales.quotations.send';

    public const QUOTATIONS_ACCEPT = 'sales.quotations.accept';

    public const QUOTATIONS_REJECT = 'sales.quotations.reject';

    public const QUOTATIONS_CONVERT = 'sales.quotations.convert';

    public const QUOTATIONS_DELETE = 'sales.quotations.delete';

    public const ORDERS_VIEW = 'sales.orders.view';

    public const ORDERS_CREATE = 'sales.orders.create';

    public const ORDERS_UPDATE = 'sales.orders.update';

    public const ORDERS_SUBMIT = 'sales.orders.submit';

    public const ORDERS_APPROVE = 'sales.orders.approve';

    public const ORDERS_CANCEL = 'sales.orders.cancel';

    public const ORDERS_CLOSE = 'sales.orders.close';

    public const ORDERS_DELETE = 'sales.orders.delete';

    public const ALLOCATIONS_VIEW = 'sales.allocations.view';

    public const ALLOCATIONS_CREATE = 'sales.allocations.create';

    public const ALLOCATIONS_RELEASE = 'sales.allocations.release';

    public const DELIVERIES_VIEW = 'sales.deliveries.view';

    public const DELIVERIES_CREATE = 'sales.deliveries.create';

    public const DELIVERIES_POST = 'sales.deliveries.post';

    public const DELIVERIES_REVERSE = 'sales.deliveries.reverse';

    public const CUSTOMER_INVOICES_VIEW = 'sales.customer_invoices.view';

    public const CUSTOMER_INVOICES_CREATE = 'sales.customer_invoices.create';

    public const RECEIPTS_VIEW = 'sales.receipts.view';

    public const RECEIPTS_EXECUTE = 'sales.receipts.execute';

    public const RETURNS_VIEW = 'sales.returns.view';

    public const RETURNS_CREATE = 'sales.returns.create';

    public const RETURNS_CREATE_MANUAL = 'sales.returns.create_manual';

    public const RETURNS_UPDATE = 'sales.returns.update';

    public const RETURNS_SUBMIT = 'sales.returns.submit';

    public const RETURNS_APPROVE = 'sales.returns.approve';

    public const RETURNS_POST = 'sales.returns.post';

    public const RETURNS_REVERSE = 'sales.returns.reverse';

    public const RETURNS_CANCEL = 'sales.returns.cancel';

    public const CREDIT_NOTES_VIEW = 'sales.credit_notes.view';

    public const CREDIT_NOTES_CREATE = 'sales.credit_notes.create';

    public const CREDIT_NOTES_UPDATE = 'sales.credit_notes.update';

    public const CREDIT_NOTES_APPROVE = 'sales.credit_notes.approve';

    public const CREDIT_NOTES_POST = 'sales.credit_notes.post';

    public const CREDIT_NOTES_ALLOCATE = 'sales.credit_notes.allocate';

    public const CREDIT_NOTES_REVERSE = 'sales.credit_notes.reverse';

    public const FAST_SALES_VIEW = 'sales.fast_sales.view';

    public const FAST_SALES_EXECUTE = 'sales.fast_sales.execute';

    public const FAST_SALES_LOOKUPS = 'sales.fast_sales.lookups';

    public const ADJUSTMENT_ACCOUNTING_OVERRIDE = 'sales.adjustments.override_accounting';

    public function __construct(private readonly UserAccessResolver $access) {}

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            self::QUOTATIONS_VIEW => 'View sales quotations and quotation lookups.',
            self::QUOTATIONS_CREATE => 'Create draft sales quotations.',
            self::QUOTATIONS_UPDATE => 'Update draft sales quotations.',
            self::QUOTATIONS_SEND => 'Send sales quotations to customers.',
            self::QUOTATIONS_ACCEPT => 'Accept sales quotations.',
            self::QUOTATIONS_REJECT => 'Reject sales quotations.',
            self::QUOTATIONS_CONVERT => 'Convert accepted quotations to sales orders.',
            self::QUOTATIONS_DELETE => 'Delete unused draft sales quotations.',
            self::ORDERS_VIEW => 'View sales orders and sales order lookups.',
            self::ORDERS_CREATE => 'Create draft sales orders.',
            self::ORDERS_UPDATE => 'Update draft sales orders.',
            self::ORDERS_SUBMIT => 'Submit sales orders for approval.',
            self::ORDERS_APPROVE => 'Approve submitted sales orders.',
            self::ORDERS_CANCEL => 'Cancel eligible sales orders.',
            self::ORDERS_CLOSE => 'Close fulfilled sales orders.',
            self::ORDERS_DELETE => 'Delete unused draft sales orders.',
            self::ALLOCATIONS_VIEW => 'View stock allocations for sales orders.',
            self::ALLOCATIONS_CREATE => 'Reserve stock for sales orders.',
            self::ALLOCATIONS_RELEASE => 'Release reserved stock for sales orders.',
            self::DELIVERIES_VIEW => 'View sales deliveries and delivery source data.',
            self::DELIVERIES_CREATE => 'Create draft sales deliveries.',
            self::DELIVERIES_POST => 'Post sales deliveries to inventory and tax.',
            self::DELIVERIES_REVERSE => 'Reverse eligible posted sales deliveries.',
            self::CUSTOMER_INVOICES_VIEW => 'Preview and view customer invoice source data.',
            self::CUSTOMER_INVOICES_CREATE => 'Create customer invoices from eligible sales sources.',
            self::RECEIPTS_VIEW => 'View customer receipt workspace data.',
            self::RECEIPTS_EXECUTE => 'Create customer receipts through the Payment module.',
            self::RETURNS_VIEW => 'View sales returns.',
            self::RETURNS_CREATE => 'Create draft sales returns.',
            self::RETURNS_CREATE_MANUAL => 'Create restricted manual sales returns.',
            self::RETURNS_UPDATE => 'Update draft sales returns.',
            self::RETURNS_SUBMIT => 'Submit sales returns when approval workflow is enabled.',
            self::RETURNS_APPROVE => 'Approve sales returns.',
            self::RETURNS_POST => 'Post sales returns.',
            self::RETURNS_REVERSE => 'Reverse eligible posted sales returns.',
            self::RETURNS_CANCEL => 'Cancel draft or approved sales returns.',
            self::CREDIT_NOTES_VIEW => 'View sales credit notes.',
            self::CREDIT_NOTES_CREATE => 'Create sales credit notes.',
            self::CREDIT_NOTES_UPDATE => 'Update draft sales credit notes.',
            self::CREDIT_NOTES_APPROVE => 'Approve sales credit notes.',
            self::CREDIT_NOTES_POST => 'Post sales credit notes.',
            self::CREDIT_NOTES_ALLOCATE => 'Allocate sales credit notes to customer invoices.',
            self::CREDIT_NOTES_REVERSE => 'Reverse eligible sales credit notes.',
            self::FAST_SALES_VIEW => 'View Fast Sales context and previews.',
            self::FAST_SALES_EXECUTE => 'Execute Fast Sales workflows.',
            self::FAST_SALES_LOOKUPS => 'Access focused Fast Sales lookup and context data.',
            self::ADJUSTMENT_ACCOUNTING_OVERRIDE => 'Override sales adjustment accounting treatment and finance mapping.',
        ];
    }

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This Sales action requires permission: '.$permission);
        }
    }

    /**
     * @param  list<string>  $permissions
     */
    public function assertAny(?int $userId, int $tenantId, array $permissions): void
    {
        if ($userId !== null) {
            foreach ($permissions as $permission) {
                if ($this->can($userId, $tenantId, $permission)) {
                    return;
                }
            }
        }

        throw new AuthorizationException('This Sales action requires one of: '.implode(', ', $permissions));
    }

    public function can(int $userId, int $tenantId, string $permission): bool
    {
        return $this->access->can($userId, $tenantId, $permission);
    }
}
