export type NavigationIconName =
    | 'dashboard'
    | 'supplier'
    | 'customer'
    | 'item'
    | 'users'
    | 'purchase'
    | 'sales'
    | 'service'
    | 'rental'
    | 'invoice'
    | 'payment'
    | 'voucher'
    | 'settings'
    | 'list'
    | 'vehicle'
    | 'role'
    | 'permission';

export type NavigationModule =
    | 'dashboard'
    | 'supplier'
    | 'customer'
    | 'item'
    | 'vehicle'
    | 'user'
    | 'warehouse'
    | 'purchase'
    | 'sales'
    | 'vehicle-service'
    | 'vehicle-rental'
    | 'invoice'
    | 'payment'
    | 'voucher'
    | 'configuration';

export interface NavigationAccessRule {
    requiresTenant?: boolean;
    requiresPlatformOperator?: boolean;
    requiresOrganizationUnit?: boolean;
    modules?: NavigationModule[];
    permissions?: string[];
    roles?: string[];
}

export interface NavigationLinkItem {
    id: string;
    type: 'link';
    label: string;
    to: string;
    icon?: NavigationIconName;
    match?: string[];
    exclude?: string[];
    access?: NavigationAccessRule;
}

export interface NavigationModuleItem {
    id: string;
    type: 'module';
    label: string;
    icon: NavigationIconName;
    children: NavigationLinkItem[];
    access?: NavigationAccessRule;
}

export type NavigationItem = NavigationLinkItem | NavigationModuleItem;

export interface NavigationSection {
    id: string;
    label?: string;
    items: NavigationItem[];
}

export interface NavigationAccessContext {
    tenantId: number | string | null;
    isPlatformOperator: boolean;
    organizationUnitId?: number | string | null;
    roles: string[];
    permissions: string[];
    permissionsLoaded?: boolean;
    enabledModules: string[] | null;
}

export interface NavigationMatch {
    section?: NavigationSection;
    parent?: NavigationModuleItem;
    item: NavigationLinkItem;
}
