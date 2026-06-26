import { describe, expect, it } from 'vitest';
import { shouldAttachTenantHeader } from './apiClient';

describe('shouldAttachTenantHeader', () => {
    it('never sends tenant context to platform or public authentication routes', () => {
        expect(shouldAttachTenantHeader('/api/v1/platform/auth/login', 'tenant', 7)).toBe(false);
        expect(shouldAttachTenantHeader('/api/v1/platform/tenants', 'tenant', 7)).toBe(false);
        expect(shouldAttachTenantHeader('/api/v1/auth/login', 'tenant', 7)).toBe(false);
        expect(shouldAttachTenantHeader('/api/v1/auth/initial-administrator/inspect', 'tenant', 7)).toBe(false);
    });

    it('adds tenant context only for authenticated tenant routes', () => {
        expect(shouldAttachTenantHeader('/api/v1/items', 'tenant', 7)).toBe(true);
        expect(shouldAttachTenantHeader('/api/v1/items', 'platform', 7)).toBe(false);
        expect(shouldAttachTenantHeader('/api/v1/items', 'tenant', null)).toBe(false);
    });
});
