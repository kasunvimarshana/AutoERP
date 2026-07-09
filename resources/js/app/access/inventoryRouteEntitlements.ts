import { inventoryRoutePermissions } from '@/modules/inventory/inventoryPermissions';
import { operational, type EntitlementRule } from './routeEntitlementPolicy';

export const inventoryRouteEntitlements: readonly EntitlementRule[] = [
    operational('/inventory', ['inventory'], inventoryRoutePermissions),
    operational('/inventory/*', ['inventory'], inventoryRoutePermissions),
];
