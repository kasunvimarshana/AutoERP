import { sellingPermissions } from '@/modules/selling/sellingPermissions';
import { operational, type EntitlementRule } from './routeEntitlementPolicy';

const sellingModules = ['selling', 'customer', 'item', 'inventory', 'invoice', 'warehouse'] as const;
const sellingWorkspacePermissions = [
    sellingPermissions.salesView,
    sellingPermissions.salesCreate,
    sellingPermissions.returnsView,
    sellingPermissions.returnsCreate,
];

export const sellingRouteEntitlements: readonly EntitlementRule[] = [
    operational('/selling', sellingModules, sellingWorkspacePermissions),
    operational('/selling/sales/:id', sellingModules, sellingWorkspacePermissions),
];
