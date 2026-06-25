import axios from 'axios';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { errorDetail, toApiError } from './apiError';

describe('toApiError', () => {
    afterEach(() => vi.restoreAllMocks());

    it('preserves structured backend details such as tenant readiness blockers', () => {
        vi.spyOn(axios, 'isAxiosError').mockReturnValue(true);
        vi.spyOn(console, 'error').mockImplementation(() => undefined);
        const readiness = {
            ready: false,
            checks: { primary_domain_ready: false },
            blockers: [{
                code: 'PRIMARY_DOMAIN_READY',
                stage: 'domain',
                owner: 'Tenant domain',
                action: 'Verify a public primary domain or configure the explicit local/testing fallback.',
                message: 'Verify tenant routing.',
            }],
        };
        const error = toApiError({
            code: 'ERR_BAD_REQUEST',
            message: 'Request failed',
            config: { method: 'patch', url: '/tenants/1/activate' },
            response: {
                status: 422,
                data: {
                    message: 'Tenant activation is blocked.',
                    error: {
                        code: 'TENANT_INVALID_VALUE',
                        type: 'validation',
                        message: 'Tenant activation is blocked.',
                        details: { readiness },
                    },
                },
            },
        });

        expect(error.message).toBe('Tenant activation is blocked.');
        expect(errorDetail<typeof readiness>(error, 'readiness')).toEqual(readiness);
    });
});
