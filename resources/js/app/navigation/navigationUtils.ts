import type {
    NavigationAccessContext,
    NavigationAccessRule,
    NavigationLinkItem,
    NavigationMatch,
    NavigationModuleItem,
    NavigationSection,
} from './navigationTypes';
import { protectedAccessRoles } from '@/modules/access/accessPermissions';

export function normalizeAccessValue(value: string): string {
    return value.trim().toLowerCase();
}

export function canAccessNavigation(rule: NavigationAccessRule | undefined, context: NavigationAccessContext): boolean {
    if (!rule) return true;
    if (rule.requiresTenant && !context.tenantId) return false;
    if (rule.requiresOrganizationUnit && !context.organizationUnitId) return false;

    if (rule.modules && context.enabledModules) {
        const enabled = new Set(context.enabledModules.map(normalizeModule));
        if (!rule.modules.every((module) => enabled.has(normalizeModule(module)))) {
            return false;
        }
    }

    const roles = context.roles.map(normalizeAccessValue);
    if (roles.includes(protectedAccessRoles.superAdmin)) return true;

    const permissions = context.permissions.map(normalizeAccessValue);
    const exactMatch = rule.permissions?.some((permission) => permissions.includes(normalizeAccessValue(permission)));
    const roleMatch = rule.roles?.some((role) => roles.includes(normalizeAccessValue(role)));
    const hasPermissionRule = Boolean(rule.permissions?.length);
    const hasRoleRule = Boolean(rule.roles?.length);

    if (!hasPermissionRule && !hasRoleRule) return true;
    if (hasPermissionRule && context.permissionsLoaded === false) return false;

    return Boolean(exactMatch || roleMatch);
}

function normalizeModule(value: string): string {
    return normalizeAccessValue(value).replace(/[^a-z0-9]/g, '');
}

export function filterNavigation(
    sections: NavigationSection[],
    context: NavigationAccessContext,
): NavigationSection[] {
    const visibleSections: NavigationSection[] = [];

    for (const section of sections) {
        const items: NavigationSection['items'] = [];
        for (const item of section.items) {
            if (!canAccessNavigation(item.access, context)) continue;
            if (item.type === 'link') {
                items.push(item);
                continue;
            }

            const children = item.children.filter((child) => canAccessNavigation(child.access, context));
            if (children.length > 0) items.push({ ...item, children });
        }
        if (items.length > 0) visibleSections.push({ ...section, items });
    }

    return visibleSections;
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
