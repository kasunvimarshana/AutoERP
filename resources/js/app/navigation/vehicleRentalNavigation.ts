import {
    vehicleRentalPermissions,
    vehicleRentalViewPermissions,
} from '@/modules/vehicle-rental/vehicleRentalPermissions';
import type {
    NavigationAccessRule,
    NavigationLinkItem,
    NavigationModuleItem,
} from './navigationTypes';

const VEHICLE_RENTAL_MODULE = 'vehicle-rental' as const;
const VEHICLE_RENTAL_BASE_PATH = '/vehicle-rental';
const VEHICLE_RENTAL_ROUTES = {
    overview: VEHICLE_RENTAL_BASE_PATH,
    ownerAgreements: `${VEHICLE_RENTAL_BASE_PATH}/owner-agreements`,
    customerAgreements: `${VEHICLE_RENTAL_BASE_PATH}/customer-agreements`,
    vehicleOperations: `${VEHICLE_RENTAL_BASE_PATH}/assignments`,
    runningCharts: `${VEHICLE_RENTAL_BASE_PATH}/running-charts`,
    customerInvoices: `${VEHICLE_RENTAL_BASE_PATH}/customer-invoices`,
    ownerSettlements: `${VEHICLE_RENTAL_BASE_PATH}/owner-settlements`,
    customerReceipts: `${VEHICLE_RENTAL_BASE_PATH}/customer-receipts`,
    ownerPayments: `${VEHICLE_RENTAL_BASE_PATH}/owner-payments`,
    reports: `${VEHICLE_RENTAL_BASE_PATH}/reports`,
} as const;
const VEHICLE_RENTAL_INTERNAL_ROUTES = [
    `${VEHICLE_RENTAL_BASE_PATH}/agreements`,
    `${VEHICLE_RENTAL_BASE_PATH}/calculations`,
] as const;
const VEHICLE_RENTAL_OVERVIEW_EXCLUSIONS = [
    ...Object.values(VEHICLE_RENTAL_ROUTES).filter((path) => path !== VEHICLE_RENTAL_ROUTES.overview),
    ...VEHICLE_RENTAL_INTERNAL_ROUTES,
];

function operationalAccess(permissions: readonly string[]): NavigationAccessRule {
    return {
        requiresTenant: true,
        requiresOrganizationUnit: true,
        modules: [VEHICLE_RENTAL_MODULE],
        permissions,
    };
}

function workspaceLink(
    id: string,
    label: string,
    to: string,
    permissions: readonly string[],
): NavigationLinkItem {
    return {
        id,
        type: 'link',
        label,
        to,
        match: [to],
        access: operationalAccess(permissions),
    };
}

export const vehicleRentalNavigationItem = {
    id: VEHICLE_RENTAL_MODULE,
    type: 'module',
    label: 'Vehicle Rental',
    icon: 'vehicle',
    access: operationalAccess(vehicleRentalViewPermissions),
    children: [
        {
            ...workspaceLink(
                'vehicle-rental-overview',
                'Overview',
                VEHICLE_RENTAL_ROUTES.overview,
                vehicleRentalViewPermissions,
            ),
            exclude: VEHICLE_RENTAL_OVERVIEW_EXCLUSIONS,
        },
        workspaceLink(
            'vehicle-rental-owner-agreements',
            'Owner Agreements',
            VEHICLE_RENTAL_ROUTES.ownerAgreements,
            [vehicleRentalPermissions.agreementsView],
        ),
        workspaceLink(
            'vehicle-rental-customer-agreements',
            'Customer Agreements',
            VEHICLE_RENTAL_ROUTES.customerAgreements,
            [vehicleRentalPermissions.agreementsView],
        ),
        workspaceLink(
            'vehicle-rental-vehicle-operations',
            'Vehicle Operations',
            VEHICLE_RENTAL_ROUTES.vehicleOperations,
            [vehicleRentalPermissions.assignmentsView],
        ),
        workspaceLink(
            'vehicle-rental-running-charts',
            'Daily Running Charts',
            VEHICLE_RENTAL_ROUTES.runningCharts,
            [vehicleRentalPermissions.runningChartsView],
        ),
        workspaceLink(
            'vehicle-rental-customer-invoices',
            'Customer Invoices',
            VEHICLE_RENTAL_ROUTES.customerInvoices,
            [vehicleRentalPermissions.calculationsView],
        ),
        workspaceLink(
            'vehicle-rental-owner-settlements',
            'Owner Payable Vouchers',
            VEHICLE_RENTAL_ROUTES.ownerSettlements,
            [vehicleRentalPermissions.calculationsView],
        ),
        workspaceLink(
            'vehicle-rental-customer-receipts',
            'Customer Receipts',
            VEHICLE_RENTAL_ROUTES.customerReceipts,
            [vehicleRentalPermissions.calculationsView],
        ),
        workspaceLink(
            'vehicle-rental-owner-payments',
            'Owner Payments',
            VEHICLE_RENTAL_ROUTES.ownerPayments,
            [vehicleRentalPermissions.calculationsView],
        ),
        workspaceLink(
            'vehicle-rental-reports',
            'Reports',
            VEHICLE_RENTAL_ROUTES.reports,
            [vehicleRentalPermissions.calculationsView],
        ),
    ],
} satisfies NavigationModuleItem;
