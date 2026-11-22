/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { inventoryPermissions, inventoryRoutePermissions } from '@/modules/inventory/inventoryPermissions';
import { operational, type EntitlementRule } from './routeEntitlementPolicy';

export const inventoryRouteEntitlements: readonly EntitlementRule[] = [
    operational('/inventory', ['inventory'], inventoryRoutePermissions),
    operational('/inventory/stock-card', ['inventory'], [inventoryPermissions.stockView]),
    operational('/inventory/*', ['inventory'], inventoryRoutePermissions),
];
