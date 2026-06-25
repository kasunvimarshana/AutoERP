import { matchPath } from 'react-router-dom';
import { accessPermissions, protectedAccessRoles } from '@/modules/access/accessPermissions';
import { auditPermissions } from '@/modules/audit/auditPermissions';
import { customerPermissions } from '@/modules/customer/customerPermissions';
import { itemPermissions } from '@/modules/item/itemPermissions';
import { organizationUnitPermissions } from '@/modules/organization-unit/organizationUnitPermissions';
import { paymentPermissions } from '@/modules/payment/paymentPermissions';
import { purchasePermissions } from '@/modules/purchase/purchasePermissions';
import { referenceDataPermissions } from '@/modules/reference-data/referenceDataPermissions';
import { reportingPermissions } from '@/modules/reporting/reportingPermissions';
import { salesPermissions } from '@/modules/sales/salesPermissions';
import { settingsPermissions } from '@/modules/settings/settingsPermissions';
import { supplierPermissions } from '@/modules/supplier/supplierPermissions';
import { tenantPermissions } from '@/modules/tenant/tenantPermissions';
import { vehicleRentalPermissions } from '@/modules/vehicle-rental/vehicleRentalPermissions';
import { vehiclePermissions } from '@/modules/vehicle/vehiclePermissions';
import { warehousePermissions } from '@/modules/warehouse/warehousePermissions';
import { DASHBOARD_PATH } from '@/app/routePaths';
import type { TenantModuleCode } from './tenantModules';

export interface TenantRouteEntitlement {
    modules?: readonly TenantModuleCode[];
    requiresOrganizationUnit?: boolean;
    permissions?: readonly string[];
    roles?: readonly string[];
}

interface EntitlementRule extends TenantRouteEntitlement {
    path: string;
}

const accessControlPermissions = Object.values(accessPermissions);
const tenantWorkspacePermissions = [
    tenantPermissions.profileView,
    tenantPermissions.domainsView,
    tenantPermissions.documentsView,
] as const;

const rule = (path: string, entitlement: Omit<EntitlementRule, 'path'> = {}): EntitlementRule => ({
    path,
    ...entitlement,
});

const operational = (
    path: string,
    modules?: readonly TenantModuleCode[],
    permissions?: readonly string[],
): EntitlementRule => rule(path, { modules, permissions, requiresOrganizationUnit: true });

const tenant = (
    path: string,
    modules?: readonly TenantModuleCode[],
    permissions?: readonly string[],
): EntitlementRule => rule(path, { modules, permissions });

const rules: readonly EntitlementRule[] = [
    // Tenant shell and plan-independent operational modules.
    rule(DASHBOARD_PATH),
    operational('/uoms/*'),
    operational('/uom-conversions/*'),
    operational('/uom-convert'),
    operational('/hr/*'),
    operational('/vouchers/*'),
    // Tenant administration and access control.
    operational('/access/users/create', undefined, [accessPermissions.usersCreate]),
    operational('/access/users/:id/edit', undefined, [accessPermissions.usersUpdate]),
    operational('/access/users/:id', undefined, [accessPermissions.usersView]),
    operational('/access/users', undefined, [accessPermissions.usersView]),
    operational('/access/roles/create', undefined, [accessPermissions.rolesCreate]),
    operational('/access/roles/:id/edit', undefined, [accessPermissions.rolesUpdate]),
    operational('/access/roles/:id', undefined, [accessPermissions.rolesView]),
    operational('/access/roles', undefined, [accessPermissions.rolesView]),
    operational('/access/permissions', undefined, [accessPermissions.permissionsView]),
    rule('/administration/access', {
        permissions: accessControlPermissions,
        roles: [protectedAccessRoles.superAdmin],
        requiresOrganizationUnit: true,
    }),
    operational('/administration/audit-logs/:id', undefined, [auditPermissions.view]),
    operational('/administration/audit-logs', undefined, [auditPermissions.view]),
    tenant('/administration/organization-units', undefined, [organizationUnitPermissions.view]),
    tenant('/administration/tenant', undefined, tenantWorkspacePermissions),
    tenant('/reference-data', undefined, [referenceDataPermissions.view]),
    tenant('/settings', undefined, [settingsPermissions.view]),

    // Supplier and customer master data.
    operational('/supplier-vehicles/create', ['supplier', 'vehicle'], [supplierPermissions.vehiclesCreate]),
    operational('/supplier-vehicles/:id/edit', ['supplier', 'vehicle'], [supplierPermissions.vehiclesUpdate]),
    operational('/supplier-vehicles', ['supplier', 'vehicle'], [supplierPermissions.vehiclesView]),
    operational('/suppliers/create', ['supplier'], [supplierPermissions.create]),
    operational('/suppliers/:id/edit', ['supplier'], [supplierPermissions.update]),
    operational('/suppliers/:id', ['supplier'], [supplierPermissions.view]),
    operational('/suppliers', ['supplier'], [supplierPermissions.view]),
    operational('/customer-vehicles/create', ['customer', 'vehicle'], [customerPermissions.vehiclesCreate]),
    operational('/customer-vehicles/:id/edit', ['customer', 'vehicle'], [customerPermissions.vehiclesUpdate]),
    operational('/customer-vehicles', ['customer', 'vehicle'], [customerPermissions.vehiclesView]),
    operational('/customers/create', ['customer'], [customerPermissions.create]),
    operational('/customers/:id/edit', ['customer'], [customerPermissions.update]),
    operational('/customers/:id', ['customer'], [customerPermissions.view]),
    operational('/customers', ['customer'], [customerPermissions.view]),

    // Vehicle and item master data.
    operational('/vehicles/create', ['vehicle'], [vehiclePermissions.create]),
    operational('/vehicles/:id/edit', ['vehicle'], [vehiclePermissions.update]),
    operational('/vehicles/:id', ['vehicle'], [vehiclePermissions.view]),
    operational('/vehicles/*', ['vehicle'], [vehiclePermissions.view]),
    operational('/items/create', ['item'], [itemPermissions.create]),
    operational('/items/:id/edit', ['item'], [itemPermissions.update]),
    operational('/items/:id', ['item'], [itemPermissions.view]),
    operational('/items', ['item'], [itemPermissions.view]),
    operational('/item-categories/*', ['item'], [itemPermissions.manageCategories]),
    operational('/item-brands/*', ['item'], [itemPermissions.manageBrands]),

    // Warehouse and inventory operations.
    operational('/warehouse-locations/create', ['warehouse'], [warehousePermissions.locationsCreate]),
    operational('/warehouse-locations/:id/edit', ['warehouse'], [warehousePermissions.locationsUpdate]),
    operational('/warehouse-locations/:id', ['warehouse'], [warehousePermissions.locationsView]),
    operational('/warehouse-locations', ['warehouse'], [warehousePermissions.locationsView]),
    operational('/warehouses/create', ['warehouse'], [warehousePermissions.warehousesCreate]),
    operational('/warehouses/:id/edit', ['warehouse'], [warehousePermissions.warehousesUpdate]),
    operational('/warehouses/:id', ['warehouse'], [warehousePermissions.warehousesView]),
    operational('/warehouses', ['warehouse'], [warehousePermissions.warehousesView]),
    operational('/inventory/*', ['inventory']),

    // Purchase workflows.
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
    operational('/purchase/invoices', ['purchase'], [purchasePermissions.supplierInvoicesView]),
    operational('/purchase/payments/create', ['purchase', 'payment'], [purchasePermissions.paymentsExecute]),
    operational('/purchase/payments/prepare', ['purchase', 'payment'], [purchasePermissions.paymentsExecute]),
    operational('/purchase/payments', ['purchase', 'payment'], [purchasePermissions.paymentsView]),
    operational('/purchase/debit-notes/create', ['purchase'], [purchasePermissions.debitNotesCreate]),
    operational('/purchase/debit-notes/:id', ['purchase'], [purchasePermissions.debitNotesView]),
    operational('/purchase/debit-notes', ['purchase'], [purchasePermissions.debitNotesView]),

    // Sales workflows.
    operational('/sales/quotations/create', ['sales'], [salesPermissions.quotationsCreate]),
    operational('/sales/quotations/:id/edit', ['sales'], [salesPermissions.quotationsUpdate]),
    operational('/sales/quotations/:id', ['sales'], [salesPermissions.quotationsView]),
    operational('/sales/quotations', ['sales'], [salesPermissions.quotationsView]),
    operational('/sales/fast-sales', ['sales'], [salesPermissions.fastSalesView, salesPermissions.fastSalesExecute]),
    operational('/sales/orders/create', ['sales'], [salesPermissions.ordersCreate]),
    operational('/sales/orders/:id/edit', ['sales'], [salesPermissions.ordersUpdate]),
    operational('/sales/orders/:id', ['sales'], [salesPermissions.ordersView]),
    operational('/sales/orders', ['sales'], [salesPermissions.ordersView]),
    operational('/sales/allocations/create', ['sales'], [salesPermissions.allocationsCreate]),
    operational('/sales/allocations/:id', ['sales'], [salesPermissions.allocationsView]),
    operational('/sales/allocations', ['sales'], [salesPermissions.allocationsView]),
    operational('/sales/deliveries/create', ['sales'], [salesPermissions.deliveriesCreate]),
    operational('/sales/deliveries', ['sales'], [salesPermissions.deliveriesView]),
    operational('/sales/invoices/create', ['sales', 'invoice'], [salesPermissions.customerInvoicesCreate]),
    operational('/sales/payments/prepare', ['sales', 'payment'], [salesPermissions.receiptsExecute]),
    operational('/sales/returns/create', ['sales'], [salesPermissions.returnsCreate]),
    operational('/sales/returns', ['sales'], [salesPermissions.returnsView]),
    operational('/sales/credit-notes', ['sales'], [salesPermissions.creditNotesView, salesPermissions.creditNotesCreate]),

    // Payments and reporting.
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
    operational('/reports/*', ['reporting'], [reportingPermissions.view]),

    // Vehicle rental permissions are authoritative in its backend module.
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

    // Modules whose backend currently has no granular permission contract are still
    // isolated by tenant, organization-unit, and plan entitlement boundaries.
    operational('/vehicle-service/*', ['vehicle-service']),
    operational('/finance/*', ['finance']),
    operational('/tax/*', ['finance']),
    operational('/invoices/*', ['invoice']),
];

export function resolveTenantRouteEntitlement(pathname: string): TenantRouteEntitlement | null {
    const matched = rules.find((candidate) => matchPath({ path: candidate.path, end: true }, pathname));
    return matched ?? null;
}
