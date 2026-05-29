export interface AppUser {
    id: number;
    name: string;
    email?: string | null;
}

export interface TenantInfo {
    id: number;
    name: string;
    code?: string | null;
}

export interface OrganizationUnitInfo {
    id: number;
    name: string;
    code?: string | null;
}

export interface AppBootstrap {
    appName: string;
    apiBaseUrl: string;
    user: AppUser | null;
    tenant: TenantInfo | null;
    organizationUnit: OrganizationUnitInfo | null;
}
