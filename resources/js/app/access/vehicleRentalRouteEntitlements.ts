import type { EntitlementRule } from './routeEntitlementPolicy';
import { operational } from './routeEntitlementPolicy';
import { vehicleRentalPermissions, vehicleRentalViewPermissions } from '@/modules/vehicle-rental/vehicleRentalPermissions';

export const vehicleRentalRouteEntitlements: readonly EntitlementRule[] = [
    operational('/vehicle-rental', ['vehicle-rental'], vehicleRentalViewPermissions),
    operational('/vehicle-rental/owner-agreements', ['vehicle-rental'], [vehicleRentalPermissions.agreementsView]),
    operational('/vehicle-rental/customer-agreements', ['vehicle-rental'], [vehicleRentalPermissions.agreementsView]),
    operational('/vehicle-rental/agreements', ['vehicle-rental'], [vehicleRentalPermissions.agreementsView]),
    operational('/vehicle-rental/assignments', ['vehicle-rental'], [vehicleRentalPermissions.assignmentsView]),
    operational('/vehicle-rental/running-charts', ['vehicle-rental'], [vehicleRentalPermissions.runningChartsView]),
    operational('/vehicle-rental/customer-invoices', ['vehicle-rental'], [vehicleRentalPermissions.calculationsView]),
    operational('/vehicle-rental/owner-settlements', ['vehicle-rental'], [vehicleRentalPermissions.calculationsView]),
    operational('/vehicle-rental/customer-receipts', ['vehicle-rental'], [vehicleRentalPermissions.calculationsView]),
    operational('/vehicle-rental/owner-payments', ['vehicle-rental'], [vehicleRentalPermissions.calculationsView]),
    operational('/vehicle-rental/reports', ['vehicle-rental'], [vehicleRentalPermissions.reportsView]),
    operational('/vehicle-rental/calculations', ['vehicle-rental'], [vehicleRentalPermissions.calculationsView]),
];
