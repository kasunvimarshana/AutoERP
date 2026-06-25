import type {
    NavigationAccessContext,
    NavigationAccessRule,
    NavigationLinkItem,
    NavigationMatch,
    NavigationModuleItem,
    NavigationSection,
} from './navigationTypes';
import { meetsAccessRequirement } from '@/modules/auth/accessControl';
import { parseEnabledTenantModules } from '@/app/access/tenantModules';
import { resolveTenantRouteEntitlement } from '@/app/access/routeEntitlements';

export { normalizeAccessValue } from '@/modules/auth/accessControl';

export function canAccessNavigation(rule: NavigationAccessRule | undefined, context: NavigationAccessContext): boolean {
    if (!rule) return true;
    if (rule.requiresTenant && !context.tenantId) return false;
    if (rule.requiresPlatformOperator && !context.isPlatformOperator) return false;
    if (rule.requiresOrganizationUnit && !context.organizationUnitId) return false;

    if (rule.modules && rule.modules.length > 0) {
        if (!context.enabledModulesLoaded) return false;
        const enabled = parseEnabledTenantModules(context.enabledModules);
        if (enabled === null || !rule.modules.every((module) => enabled.has(module))) return false;
    }

    return meetsAccessRequirement({
        roles: context.roles,
        permissions: context.permissions,
        permissionsLoaded: context.permissionsLoaded,
    }, {
        permissions: rule.permissions,
        roles: rule.roles,
    });
}


export function filterNavigation(
    sections: NavigationSection[],
    context: NavigationAccessContext,
): NavigationSection[] {
    const visibleSections: NavigationSection[] = [];

    for (const section of sections) {
        const items: NavigationSection['items'] = [];
        for (const item of section.items) {
            if (item.type === 'link') {
                const access = resolveLinkAccess(item);
                if (access !== null && canAccessNavigation(access, context)) items.push(item);
                continue;
            }

            if (!canAccessNavigation(modeAccessOnly(item.access), context)) continue;
            const children = item.children.filter((child) => {
                const access = resolveLinkAccess(child);
                return access !== null && canAccessNavigation(access, context);
            });
            if (children.length > 0) items.push({ ...item, children });
        }
        if (items.length > 0) visibleSections.push({ ...section, items });
    }

    return visibleSections;
}

function resolveLinkAccess(item: NavigationLinkItem): NavigationAccessRule | null | undefined {
    const configured = item.access;
    if (configured?.requiresPlatformOperator) return configured;

    const pathname = stripQuery(item.to);
    const entitlement = resolveTenantRouteEntitlement(pathname);
    if (entitlement === null) return null;

    const hasEntitlement = Boolean(
        entitlement.requiresOrganizationUnit
        || entitlement.modules?.length
        || entitlement.permissions?.length
        || entitlement.roles?.length
    );

    if (!hasEntitlement) return configured;

    return {
        requiresTenant: configured?.requiresTenant ?? true,
        requiresPlatformOperator: configured?.requiresPlatformOperator,
        requiresOrganizationUnit: entitlement.requiresOrganizationUnit,
        modules: entitlement.modules,
        permissions: entitlement.permissions,
        roles: entitlement.roles,
    };
}

function modeAccessOnly(rule: NavigationAccessRule | undefined): NavigationAccessRule | undefined {
    if (!rule) return undefined;
    return {
        requiresTenant: rule.requiresTenant,
        requiresPlatformOperator: rule.requiresPlatformOperator,
    };
}

export function isPathMatch(pathname: string, item: NavigationLinkItem): boolean {
    const candidates = item.match ?? [stripQuery(item.to)];
    const active = candidates.some((candidate) => (
        pathname === candidate || pathname.startsWith(`${candidate}/`)
    ));
    const excluded = item.exclude?.some((candidate) => (
        pathname === candidate || pathname.startsWith(`${candidate}/`)
    ));
    return active && !excluded;
}

export function findNavigationMatch(
    pathname: string,
    search: string,
    sections: NavigationSection[],
): NavigationMatch | null {
    const searchParams = new URLSearchParams(search);
    const matches: Array<NavigationMatch & { score: number }> = [];

    for (const section of sections) {
        for (const item of section.items) {
            if (item.type === 'link') {
                if (isPathMatch(pathname, item)) {
                    matches.push({ section, item, score: matchScore(item, searchParams) });
                }
                continue;
            }

            for (const child of item.children) {
                if (isPathMatch(pathname, child)) {
                    matches.push({ section, parent: item, item: child, score: matchScore(child, searchParams) + 1 });
                }
            }
        }
    }

    return matches.sort((left, right) => right.score - left.score)[0] ?? null;
}

export function findActiveModuleId(
    pathname: string,
    search: string,
    sections: NavigationSection[],
): string | null {
    return findNavigationMatch(pathname, search, sections)?.parent?.id ?? null;
}

export function navigationBreadcrumbs(match: NavigationMatch | null): string[] {
    if (!match) return [];
    return [match.section?.label, match.parent?.label, match.item.label].filter((value): value is string => Boolean(value));
}

function stripQuery(to: string): string {
    return to.split('?')[0];
}

function matchScore(item: NavigationLinkItem, currentParams: URLSearchParams): number {
    const target = new URL(item.to, 'https://autoerp.local');
    let score = stripQuery(item.to).length;
    let queryMatches = 0;
    target.searchParams.forEach((value, key) => {
        if (currentParams.get(key) === value) queryMatches += 1;
    });
    score += queryMatches * 1000;
    if (!target.search && currentParams.size === 0) score += 10;
    return score;
}

export function isModuleItem(item: NavigationLinkItem | NavigationModuleItem): item is NavigationModuleItem {
    return item.type === 'module';
}
