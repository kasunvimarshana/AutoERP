/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { sellingPermissions } from '@/modules/selling/sellingPermissions';
import { operational, type EntitlementRule } from './routeEntitlementPolicy';

const sellingModules = ['selling', 'customer', 'item', 'inventory', 'invoice', 'warehouse'] as const;
export const sellingRouteEntitlements: readonly EntitlementRule[] = [
    operational('/selling', sellingModules, [sellingPermissions.salesView]),
    operational('/selling/sales', sellingModules, [sellingPermissions.salesView]),
    operational('/selling/create', sellingModules, [sellingPermissions.salesCreate]),
    operational('/selling/returns', sellingModules, [sellingPermissions.returnsView]),
    operational('/selling/sales/:id', sellingModules, [sellingPermissions.salesView]),
];
