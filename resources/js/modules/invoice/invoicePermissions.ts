import { hasPermission, type AccessSubject } from '@/modules/auth/accessControl';

export const invoicePermissions = {
    view: 'invoices.view',
    preview: 'invoices.preview',
    create: 'invoices.create',
    approve: 'invoices.approve',
    post: 'invoices.post',
    cancel: 'invoices.cancel',
    balanceView: 'invoices.balance.view',
    sourcesView: 'invoices.sources.view',
} as const;

export function hasInvoicePermission(subject: AccessSubject, permission: string): boolean {
    return hasPermission(subject, permission);
}
