import type { NavigationModuleItem } from './navigationTypes';
import { vehicleRentalPermissions, vehicleRentalViewPermissions } from '@/modules/vehicle-rental/vehicleRentalPermissions';

const operationalAccess = (permissions: readonly string[]) => ({
    requiresTenant: true,
    requiresOrganizationUnit: true,
    modules: ['vehicle-rental'] as const,
    permissions,
});

export const vehicleRentalNavigationItem: NavigationModuleItem = {
    id: 'vehicle-rental',
    type: 'module',
    label: 'Vehicle Rental',
    icon: 'vehicle',
    access: operationalAccess(vehicleRentalViewPermissions),
    children: [
        {
            id: 'vehicle-rental-overview',
            type: 'link',
            label: 'Overview',
            to: '/vehicle-rental',
            match: ['/vehicle-rental'],
            exclude: [
                '/vehicle-rental/agreements',
                '/vehicle-rental/assignments',
                '/vehicle-rental/running-charts',
                '/vehicle-rental/calculations',
            ],
            access: operationalAccess(vehicleRentalViewPermissions),
        },
        {
            id: 'vehicle-rental-agreements',
            type: 'link',
            label: 'Agreements',
            to: '/vehicle-rental/agreements',
            match: ['/vehicle-rental/agreements'],
            access: operationalAccess([vehicleRentalPermissions.agreementsView]),
        },
        {
            id: 'vehicle-rental-assignments',
            type: 'link',
            label: 'Vehicle Assignments',
            to: '/vehicle-rental/assignments',
            match: ['/vehicle-rental/assignments'],
            access: operationalAccess([vehicleRentalPermissions.assignmentsView]),
        },
        {
            id: 'vehicle-rental-running-charts',
            type: 'link',
            label: 'Running Charts',
            to: '/vehicle-rental/running-charts',
            match: ['/vehicle-rental/running-charts'],
            access: operationalAccess([vehicleRentalPermissions.runningChartsView]),
        },
        {
            id: 'vehicle-rental-calculations',
            type: 'link',
            label: 'Calculations',
            to: '/vehicle-rental/calculations',
            match: ['/vehicle-rental/calculations'],
            access: operationalAccess([vehicleRentalPermissions.calculationsView]),
        },
    ],
};
