import { describe, expect, it } from 'vitest';
import {
    hasPermission,
    isSuperAdmin,
    meetsAccessRequirement,
    normalizeAccessValue,
} from './accessControl';

describe('access control', () => {
    it('normalizes role and permission values consistently', () => {
        expect(normalizeAccessValue('  Purchase.Orders.View ')).toBe('purchase.orders.view');
        expect(isSuperAdmin(['Super Admin'])).toBe(true);
    });

    it('fails closed while permissions are not loaded', () => {
        const subject = {
            roles: [],
            permissions: ['purchase.orders.view'],
            permissionsLoaded: false,
        };

        expect(hasPermission(subject, 'purchase.orders.view')).toBe(false);
        expect(meetsAccessRequirement(subject, { permissions: ['purchase.orders.view'] })).toBe(false);
    });

    it('does not grant an unlisted permission from an empty permission collection', () => {
        expect(hasPermission({ roles: [], permissions: [], permissionsLoaded: true }, 'vehicle.view')).toBe(false);
    });

    it('grants super administrators without duplicating every permission in the frontend', () => {
        const subject = { roles: ['SUPER ADMIN'], permissions: [], permissionsLoaded: true };

        expect(hasPermission(subject, 'tenant.manage')).toBe(true);
        expect(meetsAccessRequirement(subject, { permissions: ['tenant.manage'] })).toBe(true);
    });

    it('uses any matching permission or role for a declared requirement', () => {
        const subject = {
            roles: ['Manager'],
            permissions: ['purchase.orders.view'],
            permissionsLoaded: true,
        };

        expect(meetsAccessRequirement(subject, { permissions: ['purchase.orders.edit', 'purchase.orders.view'] })).toBe(true);
        expect(meetsAccessRequirement(subject, { roles: ['manager'] })).toBe(true);
        expect(meetsAccessRequirement(subject, { permissions: ['vehicle.view'], roles: ['auditor'] })).toBe(false);
    });
});
