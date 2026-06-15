import type {
    NavigationItem,
    NavigationLocation,
    NavigationRouteMatcher,
    NavigationSection,
    NavigationVisibilityContext,
} from './navigationTypes';

const ADMIN_ROLES = new Set(['admin', 'administrator', 'super admin']);

export function routeMatches(location: NavigationLocation, matcher: NavigationRouteMatcher): boolean {
    const currentSegments = segments(location.pathname);
    const matcherSegments = segments(matcher.path);

    if (matcher.exact && currentSegments.length !== matcherSegments.length) {
        return false;
    }
    if (!matcher.exact && currentSegments.length < matcherSegments.length) {
        return false;
    }
    if (!matcherSegments.every((segment, index) =>
        segment === '*' || segment.startsWith(':') || segment === currentSegments[index])) {
        return false;
    }

    const search = new URLSearchParams(location.search);
    return Object.entries(matcher.query ?? {}).every(([key, expected]) =>
        expected === undefined ? !search.has(key) : search.get(key) === expected);
}

export function itemIsActive(item: NavigationItem, location: NavigationLocation): boolean {
    if (item.children?.some((child) => itemIsActive(child, location))) {
        return true;
    }

    return itemMatchers(item).some((matcher) => routeMatches(location, matcher));
}

export function filterNavigation(
    sections: readonly NavigationSection[],
    context: NavigationVisibilityContext,
): NavigationSection[] {
    return sections.flatMap((section) => {
        const items = section.items.flatMap((item) => {
            const filtered = filterItem(item, context);
            return filtered ? [filtered] : [];
        });
        return items.length > 0 ? [{ ...section, items }] : [];
    });
}

export function initialExpandedIds(
    sections: readonly NavigationSection[],
    location: NavigationLocation,
    stored: readonly string[] = [],
): string[] {
    const expandable = flattenItems(sections).filter((item) => item.children?.length);
    const validStored = stored.filter((id) => expandable.some((item) => item.id === id));
    const active = expandable.find((item) => itemIsActive(item, location));

    if (active) {
        return Array.from(new Set([...validStored, active.id]));
    }
    if (validStored.length > 0) {
        return validStored;
    }

    return expandable.length > 0 ? [expandable[0].id] : [];
}

export function flattenDestinations(sections: readonly NavigationSection[]): NavigationItem[] {
    return flattenItems(sections).filter((item) => Boolean(item.route));
}

export function resolveEnabledModules(
    tenantModules?: readonly string[],
    organizationModules?: readonly string[],
): string[] | undefined {
    const tenant = normalizeModules(tenantModules);
    const organization = normalizeModules(organizationModules);
    if (tenant.length > 0 && organization.length > 0) {
        return tenant.filter((module) => organization.includes(module));
    }
    return tenant.length > 0 ? tenant : organization.length > 0 ? organization : undefined;
}

export function activeNavigationTrail(
    sections: readonly NavigationSection[],
    location: NavigationLocation,
): NavigationItem[] {
    for (const section of sections) {
        for (const item of section.items) {
            if (!itemIsActive(item, location)) {
                continue;
            }
            const child = item.children?.find((candidate) => itemIsActive(candidate, location));
            return child ? [item, child] : [item];
        }
    }

    return [];
}

export function itemMatchers(item: NavigationItem): NavigationRouteMatcher[] {
    if (item.activeRoutes?.length) {
        return item.activeRoutes;
    }
    if (!item.route) {
        return [];
    }

    const url = new URL(item.route, 'https://autoerp.local');
    const query = Object.fromEntries(url.searchParams.entries());
    return [{ path: url.pathname, query: Object.keys(query).length > 0 ? query : undefined }];
}

function filterItem(item: NavigationItem, context: NavigationVisibilityContext): NavigationItem | null {
    if (!meetsRequirements(item, context)) {
        return null;
    }

    const children = item.children?.flatMap((child) => {
        const filtered = filterItem(child, context);
        return filtered ? [filtered] : [];
    });
    if (item.children && !children?.length && !item.route) {
        return null;
    }

    return { ...item, children };
}

function meetsRequirements(item: NavigationItem, context: NavigationVisibilityContext): boolean {
    const admin = context.roles.some((role) => ADMIN_ROLES.has(role.toLowerCase()));
    if (!admin && item.requiredPermissions?.length) {
        const matches = item.requiredPermissions.map((permission) => context.permissions.includes(permission));
        if (item.permissionMode === 'all' ? !matches.every(Boolean) : !matches.some(Boolean)) {
            return false;
        }
    }

    if (item.requiredModule && context.enabledModules?.length) {
        if (!context.enabledModules.map((module) => module.toLowerCase()).includes(item.requiredModule)) {
            return false;
        }
    }

    if (item.requiredFeature && context.features) {
        if (Array.isArray(context.features)) {
            if (context.features.length > 0 && !context.features.includes(item.requiredFeature)) {
                return false;
            }
        } else if ((context.features as Readonly<Record<string, boolean>>)[item.requiredFeature] === false) {
            return false;
        }
    }

    return true;
}

function flattenItems(sections: readonly NavigationSection[]): NavigationItem[] {
    return sections.flatMap((section) =>
        section.items.flatMap((item): NavigationItem[] => [item, ...flattenChildren(item)]));
}

function flattenChildren(item: NavigationItem): NavigationItem[] {
    return item.children?.flatMap((child): NavigationItem[] => [child, ...flattenChildren(child)]) ?? [];
}

function segments(pathname: string): string[] {
    return pathname.replace(/^\/+|\/+$/g, '').split('/').filter(Boolean);
}

function normalizeModules(modules?: readonly string[]): string[] {
    return modules?.map((module) => module.trim().toLowerCase().replaceAll('_', '-').replaceAll(' ', '-')).filter(Boolean) ?? [];
}
