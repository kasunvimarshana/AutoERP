import { describe, expect, it } from 'vitest';
import {
    hostnameError,
    normalizeHostname,
    readinessLabel,
    readinessStepId,
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

    it('maps readiness failures to meaningful setup steps', () => {
        expect(readinessLabel('verified_primary_domain')).toBe('Verified primary domain');
        expect(readinessStepId('verified_primary_domain')).toBe('tenant-domain-step');
        expect(readinessStepId('subscription')).toBe('tenant-subscription-step');
    });
});
