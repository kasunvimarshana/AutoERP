import { financePermissions } from '@/modules/finance/financePermissions';
import { paymentPermissions } from '@/modules/payment/paymentPermissions';
import { reportingPermissions } from '@/modules/reporting/reportingPermissions';
import { taxPermissions } from '@/modules/tax/taxPermissions';
import { operational, type EntitlementRule } from './routeEntitlementPolicy';

export const financeRouteEntitlements: readonly EntitlementRule[] = [
    operational('/payments/methods/create', ['payment'], [paymentPermissions.methodsCreate]),
    operational('/payments/methods/:id/edit', ['payment'], [paymentPermissions.methodsUpdate]),
    operational('/payments/methods', ['payment'], [paymentPermissions.methodsView]),
    operational('/payments/cheque-templates/create', ['payment'], [paymentPermissions.templatesCreate]),
    operational('/payments/cheque-templates/:id/edit', ['payment'], [paymentPermissions.templatesUpdate]),
    operational('/payments/cheque-templates', ['payment'], [paymentPermissions.templatesView]),
    operational('/payments/:paymentId/lines/:lineId/cheque-print', ['payment'], [paymentPermissions.chequesPreview]),
    operational('/payments/create', ['payment'], [paymentPermissions.create]),
    operational('/payments/:id', ['payment'], [paymentPermissions.view]),
    operational('/payments', ['payment'], [paymentPermissions.view]),

    operational('/finance/accounts/create', ['finance'], [financePermissions.accountsManage]),
    operational('/finance/accounts/:id/edit', ['finance'], [financePermissions.accountsManage]),
    operational('/finance/accounts/:id', ['finance'], [financePermissions.accountsView]),
    operational('/finance/accounts', ['finance'], [financePermissions.accountsView]),
    operational('/finance/journals/create', ['finance'], [financePermissions.journalsCreate]),
    operational('/finance/journals/:id/edit', ['finance'], [financePermissions.journalsUpdate]),
    operational('/finance/journals/:id', ['finance'], [financePermissions.journalsView]),
    operational('/finance/journals', ['finance'], [financePermissions.journalsView]),
    operational('/finance/ledger', ['finance'], [financePermissions.reportsView]),
    operational('/finance/trial-balance', ['finance'], [financePermissions.reportsView]),
    operational('/finance/account-balances', ['finance'], [financePermissions.reportsView]),
    operational('/finance/posting-profiles', ['finance'], [financePermissions.postingProfilesView]),
    operational('/finance/reversals', ['finance'], [financePermissions.journalsView]),
    operational('/finance/reports', ['finance'], [financePermissions.reportsView]),
    operational('/finance/bank-reconciliations', ['finance'], [financePermissions.bankReconciliationsView]),
    operational('/finance/budgets', ['finance'], [financePermissions.budgetsView]),

    operational('/tax/taxes/create', ['finance'], [taxPermissions.taxesManage]),
    operational('/tax/taxes/:id/edit', ['finance'], [taxPermissions.taxesManage]),
    operational('/tax/taxes', ['finance'], [taxPermissions.taxesView]),
    operational('/tax/groups', ['finance'], [taxPermissions.groupsView]),
    operational('/tax/customer-profiles', ['finance'], [taxPermissions.profilesView]),
    operational('/tax/supplier-profiles', ['finance'], [taxPermissions.profilesView]),
    operational('/tax/posting-profiles', ['finance'], [taxPermissions.postingProfilesView]),
    operational('/tax/reports', ['finance'], [taxPermissions.reportsView]),

    operational('/reports/*', ['reporting'], [reportingPermissions.view]),
];
