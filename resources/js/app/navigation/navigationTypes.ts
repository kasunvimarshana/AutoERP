export type NavigationModule =
    | 'sales'
    | 'purchase'
    | 'inventory'
    | 'vehicle-service'
    | 'vehicle-rental'
    | 'hr'
    | 'finance'
    | 'master-data'
    | 'reports'
    | 'administration';

export interface NavigationRouteMatcher {
    path: string;
    exact?: boolean;
    query?: Record<string, string | undefined>;
}

export interface NavigationRequirement {
    requiredPermissions?: string[];
    permissionMode?: 'all' | 'any';
    requiredModule?: NavigationModule;
    requiredFeature?: string;
}

export interface NavigationItem extends NavigationRequirement {
    id: string;
    label: string;
    route?: string;
    icon?: string;
    children?: NavigationItem[];
    activeRoutes?: NavigationRouteMatcher[];
    badgeKey?: string;
}

export interface NavigationSection {
    id: string;
    label?: string;
    items: NavigationItem[];
}

export interface NavigationVisibilityContext {
    permissions: readonly string[];
    roles: readonly string[];
    enabledModules?: readonly string[];
    features?: Readonly<Record<string, boolean>> | readonly string[];
}

export interface NavigationLocation {
    pathname: string;
    search: string;
}

