import { uomPermissions } from '@/modules/uom/uomPermissions';
import { operational, type EntitlementRule } from './routeEntitlementPolicy';

export const uomRouteEntitlements: readonly EntitlementRule[] = [
    operational('/uoms/create', undefined, [uomPermissions.create]),
    operational('/uoms/:id/edit', undefined, [uomPermissions.update]),
    operational('/uoms/:id', undefined, [uomPermissions.view]),
    operational('/uoms', undefined, [uomPermissions.view]),
    operational('/uom-conversions/create', undefined, [uomPermissions.conversionsCreate]),
    operational('/uom-conversions/:id/edit', undefined, [uomPermissions.conversionsUpdate]),
    operational('/uom-conversions', undefined, [uomPermissions.conversionsView]),
    operational('/uom-convert', undefined, [uomPermissions.conversionsRun]),
];
