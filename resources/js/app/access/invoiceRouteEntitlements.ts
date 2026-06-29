import { invoicePermissions } from '@/modules/invoice/invoicePermissions';
import { operational, type EntitlementRule } from './routeEntitlementPolicy';

export const invoiceRouteEntitlements: readonly EntitlementRule[] = [
    operational('/invoices/create', ['invoice'], [invoicePermissions.create]),
    operational('/invoices/:id', ['invoice'], [invoicePermissions.view]),
    operational('/invoices', ['invoice'], [invoicePermissions.view]),
    operational('/invoices/*', ['invoice'], [invoicePermissions.view]),
];
