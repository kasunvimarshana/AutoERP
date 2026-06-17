export const purchasePermissions = {
    ordersView: 'purchase.orders.view',
    ordersCreate: 'purchase.orders.create',
    ordersUpdate: 'purchase.orders.update',
    ordersSubmit: 'purchase.orders.submit',
    ordersApprove: 'purchase.orders.approve',
    ordersCancel: 'purchase.orders.cancel',
    ordersClose: 'purchase.orders.close',
    ordersDelete: 'purchase.orders.delete',
    goodsReceiptsView: 'purchase.goods_receipts.view',
    goodsReceiptsCreate: 'purchase.goods_receipts.create',
    goodsReceiptsPost: 'purchase.goods_receipts.post',
    goodsReceiptsReverse: 'purchase.goods_receipts.reverse',
    supplierInvoicesView: 'purchase.supplier_invoices.view',
    supplierInvoicesCreate: 'purchase.supplier_invoices.create',
    returnsView: 'purchase.returns.view',
    returnsCreate: 'purchase.returns.create',
    returnsApprove: 'purchase.returns.approve',
    returnsPost: 'purchase.returns.post',
    returnsCancel: 'purchase.returns.cancel',
    debitNotesView: 'purchase.debit_notes.view',
    debitNotesCreate: 'purchase.debit_notes.create',
    debitNotesApprove: 'purchase.debit_notes.approve',
    debitNotesPost: 'purchase.debit_notes.post',
    debitNotesAllocate: 'purchase.debit_notes.allocate',
    paymentsView: 'purchase.payments.view',
    paymentsExecute: 'purchase.payments.execute',
    fastPurchasesView: 'purchase.fast_purchases.view',
    fastPurchasesExecute: 'purchase.fast_purchases.execute',
} as const;

export function hasPurchasePermission(permissions: string[], permission: string): boolean {
    return permissions.length === 0 || permissions.includes(permission);
}
