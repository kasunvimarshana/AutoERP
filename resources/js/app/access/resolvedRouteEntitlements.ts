import { matchPath } from 'react-router-dom';
import { administrationRouteEntitlements } from './administrationRouteEntitlements';
import { commerceRouteEntitlements } from './commerceRouteEntitlements';
import { financeRouteEntitlements } from './financeRouteEntitlements';
import { invoiceRouteEntitlements } from './invoiceRouteEntitlements';
import {
    resolveTenantRouteEntitlement as resolveLegacyRouteEntitlement,
} from './routeEntitlements';
import type { EntitlementRule, TenantRouteEntitlement } from './routeEntitlementPolicy';

export type { TenantRouteEntitlement } from './routeEntitlementPolicy';

const featureOwnedRules: readonly EntitlementRule[] = [
    ...administrationRouteEntitlements,
    ...commerceRouteEntitlements,
    ...financeRouteEntitlements,
    ...invoiceRouteEntitlements,
];

export function resolveTenantRouteEntitlement(pathname: string): TenantRouteEntitlement | null {
    const matched = featureOwnedRules.find((candidate) => matchPath({ path: candidate.path, end: true }, pathname));
    return matched ?? resolveLegacyRouteEntitlement(pathname);
}
