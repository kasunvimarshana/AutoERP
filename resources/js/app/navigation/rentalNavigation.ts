import { CHART_REGISTER_PATH, CHART_PERMISSION } from '@/modules/vehicle-rental/runningCharts';
import { TENANT_MODULE_CODE } from '@/app/access/tenantModules';
import { AgreementKind, agreementPath, agreementPermissions } from '@/modules/vehicle-rental/agreements';
import type { NavigationModuleItem } from './navigationTypes';

const access = (permissions: readonly string[]) => ({ requiresTenant: true, requiresOrganizationUnit: true, modules: [TENANT_MODULE_CODE.VEHICLE_RENTAL], permissions });
export const rentalNavigationItem: NavigationModuleItem = {
    id: 'vehicle-rental', type: 'module', label: 'Vehicle Rental', icon: 'vehicle',
    access: access([agreementPermissions.customer.view, agreementPermissions.owner.view, CHART_PERMISSION.view]),
    children: [
        { id: 'rental-running-charts', type: 'link', label: 'Running Chart Register', to: CHART_REGISTER_PATH, access: access([CHART_PERMISSION.view]) },
        { id: 'rental-customer-agreements', type: 'link', label: 'Customer Agreements', to: agreementPath(AgreementKind.Customer), access: access([agreementPermissions.customer.view]) },
        { id: 'rental-owner-agreements', type: 'link', label: 'Owner Agreements', to: agreementPath(AgreementKind.Owner), access: access([agreementPermissions.owner.view]) },
    ],
};
