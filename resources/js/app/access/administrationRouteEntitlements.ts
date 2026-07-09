import { DASHBOARD_PATH } from '@/app/routePaths';
import { accessPermissions, protectedAccessRoles } from '@/modules/access/accessPermissions';
import { auditPermissions } from '@/modules/audit/auditPermissions';
import { customerPermissions } from '@/modules/customer/customerPermissions';
import { itemPermissions } from '@/modules/item/itemPermissions';
import { organizationUnitPermissions } from '@/modules/organization-unit/organizationUnitPermissions';
import { referenceDataPermissions } from '@/modules/reference-data/referenceDataPermissions';
import { settingsPermissions } from '@/modules/settings/settingsPermissions';
import { supplierPermissions } from '@/modules/supplier/supplierPermissions';
import { tenantPermissions } from '@/modules/tenant/tenantPermissions';
import { vehiclePermissions } from '@/modules/vehicle/vehiclePermissions';
import { warehousePermissions } from '@/modules/warehouse/warehousePermissions';
import { operational, rule, tenant, type EntitlementRule } from './routeEntitlementPolicy';

const accessControlPermissions = Object.values(accessPermissions);
const tenantWorkspacePermissions = [
    tenantPermissions.profileView,
    tenantPermissions.domainsView,
    tenantPermissions.documentsView,
] as const;

export const administrationRouteEntitlements: readonly EntitlementRule[] = [
    rule(DASHBOARD_PATH),
    operational('/uoms/*'),
    operational('/uom-conversions/*'),
    operational('/uom-convert'),
    operational('/vouchers/*'),

    operational('/access/users/create', undefined, [accessPermissions.usersCreate]),
    operational('/access/users/:id/edit', undefined, [
        accessPermissions.usersUpdate,
        accessPermissions.usersAssignRoles,
        accessPermissions.usersAssignPermissions,
        accessPermissions.usersManageOrganizationAccess,
    ]),
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

    operational('/supplier-vehicles/create', ['supplier', 'vehicle'], [vehiclePermissions.ownershipsManage]),
    operational('/supplier-vehicles/:id/edit', ['supplier', 'vehicle'], [vehiclePermissions.ownershipsManage]),
    operational('/supplier-vehicles', ['supplier', 'vehicle'], [vehiclePermissions.ownershipsView]),
    operational('/suppliers/create', ['supplier'], [supplierPermissions.create]),
    operational('/suppliers/:id/edit', ['supplier'], [supplierPermissions.update]),
    operational('/suppliers/:id', ['supplier'], [supplierPermissions.view]),
    operational('/suppliers', ['supplier'], [supplierPermissions.view]),
    operational('/customer-vehicles/create', ['customer', 'vehicle'], [vehiclePermissions.ownershipsManage]),
    operational('/customer-vehicles/:id/edit', ['customer', 'vehicle'], [vehiclePermissions.ownershipsManage]),
    operational('/customer-vehicles', ['customer', 'vehicle'], [vehiclePermissions.ownershipsView]),
    operational('/customers/create', ['customer'], [customerPermissions.create]),
    operational('/customers/:id/edit', ['customer'], [customerPermissions.update]),
    operational('/customers/:id', ['customer'], [customerPermissions.view]),
    operational('/customers', ['customer'], [customerPermissions.view]),

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

    operational('/warehouse-locations/create', ['warehouse'], [warehousePermissions.locationsCreate]),
    operational('/warehouse-locations/:id/edit', ['warehouse'], [warehousePermissions.locationsUpdate]),
    operational('/warehouse-locations/:id', ['warehouse'], [warehousePermissions.locationsView]),
    operational('/warehouse-locations', ['warehouse'], [warehousePermissions.locationsView]),
    operational('/warehouses/create', ['warehouse'], [warehousePermissions.warehousesCreate]),
    operational('/warehouses/:id/edit', ['warehouse'], [warehousePermissions.warehousesUpdate]),
    operational('/warehouses/:id', ['warehouse'], [warehousePermissions.warehousesView]),
    operational('/warehouses', ['warehouse'], [warehousePermissions.warehousesView]),
    operational('/inventory/*', ['inventory']),
    operational('/hr/*', ['hr']),
];
