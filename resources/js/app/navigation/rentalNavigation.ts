import { USE_REGISTER_PATH, USE_PERMISSION } from '@/modules/vehicle-rental/vehicleUse';
import { CHART_REGISTER_PATH, CHART_PERMISSION } from '@/modules/vehicle-rental/runningCharts';
import { INCIDENT_PERMISSION, INCIDENT_REGISTER_PATH } from '@/modules/vehicle-rental/incidentEvidence';
import { RESERVATION_REGISTER_PATH, RESERVATION_PERMISSION } from '@/modules/vehicle-rental/reservationApi';
import { FINANCE_REGISTER_PATH, FINANCE_PERMISSION } from '@/modules/vehicle-rental/vehicleFinanceApi';
import { EXPENSE_REGISTER_PATH, EXPENSE_PERMISSION } from '@/modules/vehicle-rental/rentalExpenseApi';
import { DASHBOARD_PATH, DASHBOARD_PERMISSION } from '@/modules/vehicle-rental/rentalDashboardApi';
import { TENANT_MODULE_CODE } from '@/app/access/tenantModules';
import { AgreementKind, agreementPath, agreementPermissions } from '@/modules/vehicle-rental/agreements';
import type { NavigationModuleItem } from './navigationTypes';

const access = (permissions: readonly string[]) => ({ requiresTenant: true, requiresOrganizationUnit: true, modules: [TENANT_MODULE_CODE.VEHICLE_RENTAL], permissions });

export const rentalNavigationItem: NavigationModuleItem = {
    id: 'vehicle-rental',
    type: 'module',
    label: 'Vehicle Rental',
    icon: 'vehicle',
    access: access([
        agreementPermissions.customer.view,
        agreementPermissions.owner.view,
        CHART_PERMISSION.view,
        USE_PERMISSION.view,
        INCIDENT_PERMISSION.view,
        RESERVATION_PERMISSION.view,
        FINANCE_PERMISSION.view,
        EXPENSE_PERMISSION.view,
        DASHBOARD_PERMISSION,
    ]),
    children: [
        { id: 'rental-dashboard', type: 'link', label: 'Operations Dashboard', to: DASHBOARD_PATH, access: access([DASHBOARD_PERMISSION, agreementPermissions.customer.view, USE_PERMISSION.view]) },
        { id: 'rental-reservations', type: 'link', label: 'Reservations', to: RESERVATION_REGISTER_PATH, access: access([RESERVATION_PERMISSION.view]) },
        { id: 'rental-customer-agreements', type: 'link', label: 'Customer Agreements', to: agreementPath(AgreementKind.Customer), access: access([agreementPermissions.customer.view]) },
        { id: 'rental-owner-agreements', type: 'link', label: 'Owner Agreements', to: agreementPath(AgreementKind.Owner), access: access([agreementPermissions.owner.view]) },
        { id: 'rental-vehicle-uses', type: 'link', label: 'Vehicle Use Register', to: USE_REGISTER_PATH, access: access([USE_PERMISSION.view]) },
        { id: 'rental-running-charts', type: 'link', label: 'Running Chart Register', to: CHART_REGISTER_PATH, access: access([CHART_PERMISSION.view]) },
        { id: 'rental-expenses', type: 'link', label: 'Fleet Expenses', to: EXPENSE_REGISTER_PATH, access: access([EXPENSE_PERMISSION.view]) },
        { id: 'rental-finance', type: 'link', label: 'Vehicle Finance', to: FINANCE_REGISTER_PATH, access: access([FINANCE_PERMISSION.view]) },
        { id: 'rental-incidents', type: 'link', label: 'Incident Evidence', to: INCIDENT_REGISTER_PATH, access: access([INCIDENT_PERMISSION.view]) },
        { id: 'rental-availability', type: 'link', label: 'Vehicle Availability', to: '/vehicle-rental/vehicles/available', access: access([USE_PERMISSION.view]) },
        { id: 'rental-reports', type: 'link', label: 'Reports & Matrix Sheet', to: '/vehicle-rental/reports', access: access([DASHBOARD_PERMISSION, CHART_PERMISSION.view]) },
    ],
};
