import { describe, expect, it } from 'vitest';
import { financePermissions } from '@/modules/finance/financePermissions';
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

    it('preserves existing non-Finance route policies through the feature-owned resolver', () => {
        expect(resolveTenantRouteEntitlement('/payments')?.permissions).toContain('payments.view');
        expect(resolveTenantRouteEntitlement('/customers')?.permissions).toContain('customers.view');
    });

    it('protects the Inventory workspace route at its exact path', () => {
        const entitlement = resolveTenantRouteEntitlement('/inventory');

        expect(entitlement?.modules).toContain('inventory');
        expect(entitlement?.requiresOrganizationUnit).toBe(true);
    });
});
