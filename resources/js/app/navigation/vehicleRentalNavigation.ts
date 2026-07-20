import type { AppNavigationItem } from './types';
import {
    vehicleRentalPermissions,
    vehicleRentalViewPermissions,
} from '@/modules/vehicle-rental/vehicleRentalPermissions';

export const vehicleRentalNavigation: AppNavigationItem[] = [
    {
        label: 'Vehicle Rental',
        icon: 'truck',
        to: '/vehicle-rental',
        module: 'vehicle-rental',
        anyPermissions: [...vehicleRentalViewPermissions],
        children: [
            { label: 'Overview', to: '/vehicle-rental', permissions: [...vehicleRentalViewPermissions] },
            { label: 'Owner / Supplier Agreements', to: '/vehicle-rental/owner-agreements', permissions: [vehicleRentalPermissions.agreementsView] },
            { label: 'Customer Agreements', to: '/vehicle-rental/customer-agreements', permissions: [vehicleRentalPermissions.agreementsView] },
            { label: 'Daily Running Charts', to: '/vehicle-rental/running-charts', permissions: [vehicleRentalPermissions.runningChartsView] },
            { label: 'Customer Invoices', to: '/vehicle-rental/customer-invoices', permissions: [vehicleRentalPermissions.calculationsView] },
            { label: 'Owner Settlements', to: '/vehicle-rental/owner-settlements', permissions: [vehicleRentalPermissions.calculationsView] },
            { label: 'Customer Receipts', to: '/vehicle-rental/customer-receipts', permissions: [vehicleRentalPermissions.calculationsView] },
            { label: 'Owner Payments', to: '/vehicle-rental/owner-payments', permissions: [vehicleRentalPermissions.calculationsView] },
            { label: 'Reports', to: '/vehicle-rental/reports', permissions: [vehicleRentalPermissions.calculationsView] },
        ],
    },
];
