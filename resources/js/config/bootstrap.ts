import type { AppBootstrap } from '../types/app';

const fallbackBootstrap: AppBootstrap = {
    appName: 'AutoERP',
    apiBaseUrl: '/api',
    user: null,
    tenant: null,
    organizationUnit: null,
};

export function getBootstrap(): AppBootstrap {
    if (typeof window === 'undefined') {
        return fallbackBootstrap;
    }

    return window.__ERP_BOOTSTRAP__ ?? fallbackBootstrap;
}
