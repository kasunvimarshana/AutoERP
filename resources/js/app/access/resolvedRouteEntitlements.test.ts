import { describe, expect, it } from 'vitest';
import { financePermissions } from '@/modules/finance/financePermissions';
import { purchasePermissions } from '@/modules/purchase/purchasePermissions';
import { vehicleServicePermissions } from '@/modules/vehicle-service/vehicleServicePermissions';
import { resolveTenantRouteEntitlement } from './resolvedRouteEntitlements';

const expectedFinanceRoutes = [
    ['/finance/accounts', financePermissions.accountsView],
    ['/finance/accounts/create', financePermissions.accountsManage],
    ['/finance/accounts/1/edit', financePermissions.accountsManage],
    ['/finance/journals', financePermissions.journalsView],
    ['/finance/journals/create', financePermissions.journalsCreate],
    ['/finance/journals/1/edit', financePermissions.journalsUpdate],
    ['/finance/ledger', financePermissions.reportsView],
    ['/finance/trial-balance', financePermissions.reportsView],
    ['/finance/account-balances', financePermissions.reportsView],
    ['/finance/posting-profiles', financePermissions.postingProfilesView],
    ['/finance/reversals', financePermissions.journalsView],
    ['/finance/reports', financePermissions.reportsView],
    ['/finance/bank-reconciliations', financePermissions.bankReconciliationsView],
    ['/finance/budgets', financePermissions.budgetsView],
] as const;

describe('resolved tenant route entitlements', () => {
    it.each(expectedFinanceRoutes)('protects %s with %s', (path, permission) => {
        const entitlement = resolveTenantRouteEntitlement(path);

        expect(entitlement?.modules).toContain('finance');
        expect(entitlement?.requiresOrganizationUnit).toBe(true);
        expect(entitlement?.permissions).toContain(permission);
    });

    it('requires the HR tenant module for HR routes', () => {
        const entitlement = resolveTenantRouteEntitlement('/hr/employees');

        expect(entitlement?.modules).toEqual(['hr']);
        expect(entitlement?.requiresOrganizationUnit).toBe(true);
    });

    it('keeps Tax routes under the Finance tenant module', () => {
        const entitlement = resolveTenantRouteEntitlement('/tax/taxes');

        expect(entitlement?.modules).toContain('finance');
        expect(entitlement?.requiresOrganizationUnit).toBe(true);
    });

    it('resolves non-Finance policies from their feature-owned registries', () => {
        expect(resolveTenantRouteEntitlement('/payments')?.permissions).toContain('payments.view');
        expect(resolveTenantRouteEntitlement('/customers')?.permissions).toContain('customers.view');
        expect(resolveTenantRouteEntitlement('/purchase/orders')?.permissions).toContain('purchase.orders.view');
        expect(resolveTenantRouteEntitlement('/vehicle-service/jobs')?.permissions).toContain(vehicleServicePermissions.jobsView);
    });

    it('protects Purchase-owned invoice and payment detail routes with Purchase permissions', () => {
        const invoice = resolveTenantRouteEntitlement('/purchase/invoices/42');
        const payment = resolveTenantRouteEntitlement('/purchase/payments/84');

        expect(invoice?.modules).toContain('purchase');
        expect(invoice?.permissions).toContain(purchasePermissions.supplierInvoicesView);
        expect(payment?.modules).toEqual(expect.arrayContaining(['purchase', 'payment']));
        expect(payment?.permissions).toContain(purchasePermissions.paymentsView);
    });

    it('protects the Inventory workspace route at its exact path', () => {
        const entitlement = resolveTenantRouteEntitlement('/inventory');

        expect(entitlement?.modules).toContain('inventory');
        expect(entitlement?.requiresOrganizationUnit).toBe(true);
    });

    it('returns no entitlement for an unregistered route', () => {
        expect(resolveTenantRouteEntitlement('/unregistered-workspace')).toBeNull();
    });
});
