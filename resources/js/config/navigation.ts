import { moduleCatalog, type ModuleDefinition } from '../modules/moduleCatalog';

export interface NavigationItem {
    label: string;
    path: string;
    description: string;
}

export const dashboardItem: NavigationItem = {
    label: 'Dashboard',
    path: '/dashboard',
    description: 'Operational overview and system health',
};

export const modulesItem: NavigationItem = {
    label: 'Modules',
    path: '/modules',
    description: 'Enterprise module index',
};

export const moduleNavigation: ModuleDefinition[] = moduleCatalog;
