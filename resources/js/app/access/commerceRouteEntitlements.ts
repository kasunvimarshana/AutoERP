import { USE_REGISTER_PATH, USE_PERMISSION } from '@/modules/vehicle-rental/vehicleUse';
import { CHART_REGISTER_PATH, CHART_PERMISSION } from '@/modules/vehicle-rental/runningCharts';
import { INCIDENT_PERMISSION, INCIDENT_REGISTER_PATH } from '@/modules/vehicle-rental/incidentEvidence';
import { RESERVATION_PERMISSION, RESERVATION_REGISTER_PATH } from '@/modules/vehicle-rental/reservationApi';
import { FINANCE_PERMISSION, FINANCE_REGISTER_PATH } from '@/modules/vehicle-rental/vehicleFinanceApi';
import { EXPENSE_PERMISSION, EXPENSE_REGISTER_PATH } from '@/modules/vehicle-rental/rentalExpenseApi';
import { DASHBOARD_PATH as RENTAL_DASHBOARD_PATH, DASHBOARD_PERMISSION as RENTAL_DASHBOARD_PERMISSION } from '@/modules/vehicle-rental/rentalDashboardApi';
import { agreementPermissions, AgreementKind, agreementPath } from '@/modules/vehicle-rental/agreements';
import { TENANT_MODULE_CODE } from './tenantModules';
import { purchasePermissions } from '@/modules/purchase/purchasePermissions';
import { vehicleServicePermissions } from '@/modules/vehicle-service/vehicleServicePermissions';
import { operational, type EntitlementRule } from './routeEntitlementPolicy';

export const commerceRouteEntitlements: readonly EntitlementRule[] = [
    operational(RENTAL_DASHBOARD_PATH, [TENANT_MODULE_CODE.VEHICLE_RENTAL], [RENTAL_DASHBOARD_PERMISSION]),
    operational(RESERVATION_REGISTER_PATH, [TENANT_MODULE_CODE.VEHICLE_RENTAL], [RESERVATION_PERMISSION.view]),
    operational(USE_REGISTER_PATH, [TENANT_MODULE_CODE.VEHICLE_RENTAL], [USE_PERMISSION.view]),
    operational(CHART_REGISTER_PATH, [TENANT_MODULE_CODE.VEHICLE_RENTAL], [CHART_PERMISSION.view]),
    operational(EXPENSE_REGISTER_PATH, [TENANT_MODULE_CODE.VEHICLE_RENTAL], [EXPENSE_PERMISSION.view]),
    operational(FINANCE_REGISTER_PATH, [TENANT_MODULE_CODE.VEHICLE_RENTAL], [FINANCE_PERMISSION.view]),
    operational(INCIDENT_REGISTER_PATH, [TENANT_MODULE_CODE.VEHICLE_RENTAL], [INCIDENT_PERMISSION.view]),
    operational(agreementPath(AgreementKind.Customer), [TENANT_MODULE_CODE.VEHICLE_RENTAL], [agreementPermissions.customer.view]),
    operational(agreementPath(AgreementKind.Owner), [TENANT_MODULE_CODE.VEHICLE_RENTAL], [agreementPermissions.owner.view]),
    operational('/vehicle-rental/vehicles/available', [TENANT_MODULE_CODE.VEHICLE_RENTAL], [USE_PERMISSION.view]),
    operational('/vehicle-rental/running-chart/report', [TENANT_MODULE_CODE.VEHICLE_RENTAL], [CHART_PERMISSION.view]),
    operational('/vehicle-rental/reports', [TENANT_MODULE_CODE.VEHICLE_RENTAL], [RENTAL_DASHBOARD_PERMISSION]),
    operational('/vehicle-rental/reports/fleet-utilization', [TENANT_MODULE_CODE.VEHICLE_RENTAL], [RENTAL_DASHBOARD_PERMISSION]),
    operational('/vehicle-rental/reports/revenue-licenses', [TENANT_MODULE_CODE.VEHICLE_RENTAL], [RENTAL_DASHBOARD_PERMISSION]),
    operational('/vehicle-rental/reports/rental-revenue', [TENANT_MODULE_CODE.VEHICLE_RENTAL], [RENTAL_DASHBOARD_PERMISSION]),
    operational('/vehicle-rental/reports/lessee-outstanding', [TENANT_MODULE_CODE.VEHICLE_RENTAL], [RENTAL_DASHBOARD_PERMISSION]),
    operational('/vehicle-rental/reports/owner-disbursements', [TENANT_MODULE_CODE.VEHICLE_RENTAL], [RENTAL_DASHBOARD_PERMISSION]),
    operational('/vehicle-rental/reports/vehicle-profitability', [TENANT_MODULE_CODE.VEHICLE_RENTAL], [RENTAL_DASHBOARD_PERMISSION]),
    operational('/vehicle-rental/reports/incidents-summary', [TENANT_MODULE_CODE.VEHICLE_RENTAL], [RENTAL_DASHBOARD_PERMISSION]),
    operational('/purchase/fast-purchase', ['purchase'], [
        purchasePermissions.fastPurchasesView,
        purchasePermissions.fastPurchasesExecute,
    ]),
    operational('/purchase/orders/create', ['purchase'], [purchasePermissions.ordersCreate]),
    operational('/purchase/orders/:id/edit', ['purchase'], [purchasePermissions.ordersUpdate]),
    operational('/purchase/orders/:id', ['purchase'], [purchasePermissions.ordersView]),
    operational('/purchase/orders', ['purchase'], [purchasePermissions.ordersView]),
    operational('/purchase/goods-receipts/create', ['purchase'], [purchasePermissions.goodsReceiptsCreate]),
    operational('/purchase/goods-receipts/:id', ['purchase'], [purchasePermissions.goodsReceiptsView]),
    operational('/purchase/goods-receipts', ['purchase'], [purchasePermissions.goodsReceiptsView]),
    operational('/purchase/manual-supplier-returns/create', ['purchase'], [purchasePermissions.returnsCreateManual]),
    operational('/purchase/returns/create', ['purchase'], [purchasePermissions.returnsCreate]),
    operational('/purchase/returns/:id', ['purchase'], [purchasePermissions.returnsView]),
    operational('/purchase/returns', ['purchase'], [purchasePermissions.returnsView]),
    operational('/purchase/invoices/create', ['purchase'], [purchasePermissions.supplierInvoicesCreate]),
    operational('/purchase/invoices/:id', ['purchase'], [purchasePermissions.supplierInvoicesView]),
    operational('/purchase/invoices', ['purchase'], [purchasePermissions.supplierInvoicesView]),
    operational('/purchase/payments/create', ['purchase', 'payment'], [purchasePermissions.paymentsExecute]),
    operational('/purchase/payments/prepare', ['purchase', 'payment'], [purchasePermissions.paymentsExecute]),
    operational('/purchase/payments/:id', ['purchase', 'payment'], [purchasePermissions.paymentsView]),
    operational('/purchase/payments', ['purchase', 'payment'], [purchasePermissions.paymentsView]),
    operational('/purchase/debit-notes/create', ['purchase'], [purchasePermissions.debitNotesCreate]),
    operational('/purchase/debit-notes/:id', ['purchase'], [purchasePermissions.debitNotesView]),
    operational('/purchase/debit-notes', ['purchase'], [purchasePermissions.debitNotesView]),

    operational('/vehicle-service/jobs/create', ['vehicle-service'], [vehicleServicePermissions.jobsCreate]),
    operational('/vehicle-service/jobs/:id/edit', ['vehicle-service'], [vehicleServicePermissions.jobsUpdate]),
    operational('/vehicle-service/jobs/:id/invoice', ['vehicle-service'], [vehicleServicePermissions.invoicesCreate]),
    operational('/vehicle-service/jobs/:id/payment', ['vehicle-service'], [vehicleServicePermissions.paymentsCreate]),
    operational('/vehicle-service/jobs/:id', ['vehicle-service'], [vehicleServicePermissions.jobsView]),
    operational('/vehicle-service/jobs', ['vehicle-service'], [vehicleServicePermissions.jobsView]),
    operational('/vehicle-service/promotions', ['vehicle-service'], ['promotion.view']),
];
