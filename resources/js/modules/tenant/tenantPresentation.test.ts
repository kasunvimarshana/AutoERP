import { describe, expect, it } from 'vitest';
import {
    hostnameError,
    normalizeHostname,
    readinessLabel,
    readinessStepId,
    humanize,
} from './tenantPresentation';

describe('tenant presentation rules', () => {
    it('accepts a fully-qualified hostname and normalizes a trailing dot', () => {
        expect(hostnameError('ERP.Example.com.')).toBeNull();
        expect(normalizeHostname(' ERP.Example.com. ')).toBe('erp.example.com');
    });

    it.each(['https://erp.example.com', 'erp.example.com/path', 'erp.example.com:443', 'localhost', '-erp.example.com'])(
        'rejects invalid hostname input %s',
        (value) => expect(hostnameError(value)).not.toBeNull(),
    );

    it('uses a safe fallback for missing enum values', () => {
        expect(humanize(undefined)).toBe('Not available');
        expect(humanize(null)).toBe('Not available');
        expect(humanize('awaiting_domain')).toBe('Awaiting Domain');
    });

    it('maps readiness failures to meaningful setup steps', () => {
        expect(readinessLabel('PRIMARY_DOMAIN_READY')).toBe('Tenant routing');
        expect(readinessStepId('PRIMARY_DOMAIN_READY')).toBe('tenant-domain-step');
        expect(readinessStepId('SUBSCRIPTION_VALID')).toBe('tenant-subscription-step');
        expect(readinessStepId('SUBSCRIPTION_DATA_INVALID')).toBe('tenant-subscription-step');
        expect(readinessStepId('SCHEMA_INCOMPATIBLE')).toBe('tenant-activation-step');
    });
});
