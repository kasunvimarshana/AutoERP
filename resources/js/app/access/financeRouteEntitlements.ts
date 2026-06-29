import { financePermissions } from '@/modules/finance/financePermissions';
import { paymentPermissions } from '@/modules/payment/paymentPermissions';
import { reportingPermissions } from '@/modules/reporting/reportingPermissions';
import { vehicleRentalPermissions } from '@/modules/vehicle-rental/vehicleRentalPermissions';
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
    operational('/finance/fiscal-periods', ['finance'], [financePermissions.fiscalCalendarView]),
    operational('/finance/reversals', ['finance'], [financePermissions.journalsView]),
    operational('/finance/reports', ['finance'], [financePermissions.reportsView]),
    operational('/finance/bank-reconciliations', ['finance'], [financePermissions.bankReconciliationsView]),
    operational('/finance/budgets', ['finance'], [financePermissions.budgetsView]),

    operational('/reports/*', ['reporting'], [reportingPermissions.view]),

    operational('/vehicle-rental/reservations/create', ['vehicle-rental'], [vehicleRentalPermissions.reservationsManage]),
    operational('/vehicle-rental/reservations/:id', ['vehicle-rental'], [vehicleRentalPermissions.view]),
    operational('/vehicle-rental/reservations', ['vehicle-rental'], [vehicleRentalPermissions.view]),
    operational('/vehicle-rental/agreements/create', ['vehicle-rental'], [vehicleRentalPermissions.agreementsManage]),
    operational('/vehicle-rental/agreements/:id', ['vehicle-rental'], [vehicleRentalPermissions.view]),
    operational('/vehicle-rental/agreements', ['vehicle-rental'], [vehicleRentalPermissions.view]),
    operational('/vehicle-rental/allocations/:id/replacement', ['vehicle-rental'], [vehicleRentalPermissions.replacementsManage]),
    operational('/vehicle-rental/allocations/:id', ['vehicle-rental'], [vehicleRentalPermissions.view]),
    operational('/vehicle-rental/allocations', ['vehicle-rental'], [vehicleRentalPermissions.view]),
    operational('/vehicle-rental/expenses', ['vehicle-rental'], [vehicleRentalPermissions.financialView]),
    operational('/vehicle-rental/billing', ['vehicle-rental'], [vehicleRentalPermissions.financialView]),
    operational('/vehicle-rental/deposits', ['vehicle-rental'], [vehicleRentalPermissions.financialView]),
    operational('/vehicle-rental/finance-agreements', ['vehicle-rental'], [vehicleRentalPermissions.financialView]),
    operational('/vehicle-rental/*', ['vehicle-rental'], [vehicleRentalPermissions.view]),

    // These modules still require module-owned granular permission catalogues.
    operational('/vehicle-service/*', ['vehicle-service']),
    operational('/tax/*', ['finance']),
    operational('/invoices/*', ['invoice']),
];
