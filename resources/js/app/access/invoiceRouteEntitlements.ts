/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { invoicePermissions } from '@/modules/invoice/invoicePermissions';
import { operational, tenant, type EntitlementRule } from './routeEntitlementPolicy';

export const invoiceRouteEntitlements: readonly EntitlementRule[] = [
    operational('/invoices/create', ['invoice'], [invoicePermissions.create]),
    operational('/invoices/:id', ['invoice'], [invoicePermissions.view]),
    operational('/invoices', ['invoice'], [invoicePermissions.view]),
    operational('/invoices/*', ['invoice'], [invoicePermissions.view]),
    tenant('/settings/invoice-layouts', ['invoice'], [invoicePermissions.layoutsView]),
];

