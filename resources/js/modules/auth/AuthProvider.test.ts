import { describe, expect, it } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { isDefinitiveSessionFailure } from './AuthProvider';

describe('AuthProvider session failure classification', () => {
    it('clears terminal session, principal, and tenant failures', () => {
        expect(isDefinitiveSessionFailure(new ApiError('Unauthorized', 401, null))).toBe(true);
        expect(isDefinitiveSessionFailure(new ApiError('Session missing', 403, 'AUTH_SESSION_MISSING'))).toBe(true);
        expect(isDefinitiveSessionFailure(new ApiError('User inactive', 403, 'AUTH_USER_INACTIVE'))).toBe(true);
        expect(isDefinitiveSessionFailure(new ApiError('Tenant missing', 404, 'TENANT_NOT_FOUND'))).toBe(true);
        expect(isDefinitiveSessionFailure(new ApiError('Platform operator required', 403, 'PLATFORM_OPERATOR_REQUIRED'))).toBe(true);
    });

    it('preserves credentials for temporary transport and server failures', () => {
        expect(isDefinitiveSessionFailure(new ApiError('Unavailable', 503, 'SERVICE_UNAVAILABLE'))).toBe(false);
        expect(isDefinitiveSessionFailure(new ApiError('Network error', null, 'ERR_NETWORK'))).toBe(false);
        expect(isDefinitiveSessionFailure(new ApiError('Unexpected server error', 500, 'UNEXPECTED_ERROR'))).toBe(false);
    });
});
