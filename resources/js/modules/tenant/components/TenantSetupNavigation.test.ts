import { describe, expect, it } from 'vitest';
import type { TenantRecord } from '../tenantTypes';
import { tenantFoundationProvisioned } from './TenantSetupNavigation';

const requiredSteps = [
    'root_organization',
    'permission_catalogue',
    'super_admin_role',
    'authentication_provider',
    'initial_admin_invitation',
];

function tenantWithCompletedSteps(completedSteps: string[]): TenantRecord {
    return {
        onboarding: {
            completed_steps: completedSteps,
        },
    } as TenantRecord;
}

describe('tenantFoundationProvisioned', () => {
    it('requires every foundation step', () => {
        expect(tenantFoundationProvisioned(tenantWithCompletedSteps(requiredSteps))).toBe(true);
    });

    it('does not unlock dependent steps for partial provisioning', () => {
        expect(tenantFoundationProvisioned(tenantWithCompletedSteps(requiredSteps.slice(0, -1)))).toBe(false);
    });
});
