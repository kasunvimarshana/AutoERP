import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';
import { DASHBOARD_PATH } from '@/app/routePaths';
import { resolveTenantRouteEntitlement } from './routeEntitlements';

function concretePath(routePath: string): string {
    return routePath
        .replace(/:[^/]+/g, '1')
        .replace(/\/\*$/, '');
}

function configuredTenantRoutes(): string[] {
    const routerSource = readFileSync(resolve(process.cwd(), 'resources/js/app/router.tsx'), 'utf8');
    const tenantStart = routerSource.indexOf('<Route element={<TenantRoute />}>');
    const notFoundStart = routerSource.indexOf('<Route path="*" element={<NotFoundPage />} />', tenantStart);

    if (tenantStart < 0 || notFoundStart < 0) {
        throw new Error('Unable to locate the tenant route tree.');
    }

    const tenantRoutes = routerSource.slice(tenantStart, notFoundStart);
    return [...tenantRoutes.matchAll(/<Route\b[\s\S]*?\bpath="([^"]+)"/g)]
        .map((match) => match[1])
        .filter((path) => path !== '*');
}

describe('tenant route entitlements', () => {
    it('has an explicit access policy for every configured tenant route', () => {
        const missing = configuredTenantRoutes()
            .map(concretePath)
            .filter((path) => resolveTenantRouteEntitlement(path) === null);

        expect(missing).toEqual([]);
        expect(resolveTenantRouteEntitlement(DASHBOARD_PATH)).not.toBeNull();
    });

    it('aligns organization-unit requirements with operational backend routes', () => {
        for (const path of [
            '/access/users',
            '/administration/audit-logs',
            '/uoms',
            '/uom-conversions/1/edit',
            '/uom-convert',
            '/suppliers',
            '/customers',
            '/vehicles',
            '/items',
            '/payments',
            '/vouchers',
            '/invoices/1',
            '/hr/employees',
        ]) {
            expect(resolveTenantRouteEntitlement(path)?.requiresOrganizationUnit, path).toBe(true);
        }
    });

    it('fails closed for routes without an access policy', () => {
        expect(resolveTenantRouteEntitlement('/unconfigured-feature')).toBeNull();
    });
});
