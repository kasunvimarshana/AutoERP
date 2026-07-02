import { describe, expect, it } from 'vitest';
import { shouldAttachAuthorizationHeader, shouldAttachTenantHeader } from './apiClient';
import { isPublicApiRequest, requestPath } from './requestClassification';

describe('public API request classification', () => {
    it('recognizes public authentication and invitation endpoints', () => {
        expect(isPublicApiRequest('/api/v1/auth/login')).toBe(true);
        expect(isPublicApiRequest('/api/v1/auth/initial-administrator/inspect')).toBe(true);
        expect(isPublicApiRequest('/api/v1/platform/operator-invitations/accept')).toBe(true);
        expect(isPublicApiRequest('/api/v1/items')).toBe(false);
        expect(isPublicApiRequest('/api/v1/platform/operators')).toBe(false);
    });

    it('normalizes absolute and query-bearing URLs before classification', () => {
        expect(requestPath('https://example.test/api/v1/auth/login?source=web')).toBe('/api/v1/auth/login');
        expect(isPublicApiRequest('https://example.test/api/v1/platform/operator-invitations/inspect?x=1')).toBe(true);
    });
});

describe('shouldAttachAuthorizationHeader', () => {
    it('never sends a stored bearer token to public onboarding or login endpoints', () => {
        expect(shouldAttachAuthorizationHeader('/api/v1/auth/login', 'tenant-token')).toBe(false);
        expect(shouldAttachAuthorizationHeader('/api/v1/auth/initial-administrator/inspect', 'tenant-token')).toBe(false);
        expect(shouldAttachAuthorizationHeader('/api/v1/platform/operator-invitations/accept', 'platform-token')).toBe(false);
    });

    it('sends a non-empty bearer token only to protected endpoints', () => {
        expect(shouldAttachAuthorizationHeader('/api/v1/items', 'tenant-token')).toBe(true);
        expect(shouldAttachAuthorizationHeader('/api/v1/platform/operators', 'platform-token')).toBe(true);
        expect(shouldAttachAuthorizationHeader('/api/v1/items', null)).toBe(false);
        expect(shouldAttachAuthorizationHeader('/api/v1/items', '   ')).toBe(false);
    });
});

describe('shouldAttachTenantHeader', () => {
    it('never sends tenant context to platform or public authentication routes', () => {
        expect(shouldAttachTenantHeader('/api/v1/platform/auth/login', 'tenant', 7)).toBe(false);
        expect(shouldAttachTenantHeader('/api/v1/platform/tenants', 'tenant', 7)).toBe(false);
        expect(shouldAttachTenantHeader('/api/v1/auth/login', 'tenant', 7)).toBe(false);
        expect(shouldAttachTenantHeader('/api/v1/auth/initial-administrator/inspect', 'tenant', 7)).toBe(false);
        expect(shouldAttachTenantHeader('/api/v1/platform/operator-invitations/inspect', 'tenant', 7)).toBe(false);
    });

    it('adds tenant context only for authenticated tenant routes', () => {
        expect(shouldAttachTenantHeader('/api/v1/items', 'tenant', 7)).toBe(true);
        expect(shouldAttachTenantHeader('/api/v1/items', 'platform', 7)).toBe(false);
        expect(shouldAttachTenantHeader('/api/v1/items', 'tenant', null)).toBe(false);
    });
});
