import { matchPath } from 'react-router-dom';
import { administrationRouteEntitlements } from './administrationRouteEntitlements';
import { commerceRouteEntitlements } from './commerceRouteEntitlements';
import { financeRouteEntitlements } from './financeRouteEntitlements';
import { hrRouteEntitlements } from './hrRouteEntitlements';
import { inventoryRouteEntitlements } from './inventoryRouteEntitlements';
import { invoiceRouteEntitlements } from './invoiceRouteEntitlements';
import type { EntitlementRule, TenantRouteEntitlement } from './routeEntitlementPolicy';
import { uomRouteEntitlements } from './uomRouteEntitlements';
import { vehicleRentalRouteEntitlements } from './vehicleRentalRouteEntitlements';
import { voucherRouteEntitlements } from './voucherRouteEntitlements';

export type { TenantRouteEntitlement } from './routeEntitlementPolicy';

const featureOwnedRules: readonly EntitlementRule[] = [
    ...administrationRouteEntitlements,
    ...commerceRouteEntitlements,
    ...financeRouteEntitlements,
    ...hrRouteEntitlements,
    ...inventoryRouteEntitlements,
    ...invoiceRouteEntitlements,
    ...uomRouteEntitlements,
    ...vehicleRentalRouteEntitlements,
    ...voucherRouteEntitlements,
];

export function resolveTenantRouteEntitlement(pathname: string): TenantRouteEntitlement | null {
    return featureOwnedRules.find((candidate) => matchPath({ path: candidate.path, end: true }, pathname)) ?? null;
}
