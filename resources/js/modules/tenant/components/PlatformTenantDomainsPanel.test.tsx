import { fireEvent, render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    changePlatformTenantDomain,
    createPlatformTenantDomain,
    deletePlatformTenantDomain,
    getTenantOnboardingReadiness,
    listPlatformTenantDomains,
    requestPlatformDomainVerification,
    verifyPlatformTenantDomain,
} from '../tenantApi';
import type { TenantOnboardingReadiness, TenantRecord } from '../tenantTypes';
import { PlatformTenantDomainsPanel } from './PlatformTenantDomainsPanel';

vi.mock('../tenantApi', () => ({
    changePlatformTenantDomain: vi.fn(),
    createPlatformTenantDomain: vi.fn(),
    deletePlatformTenantDomain: vi.fn(),
    getTenantOnboardingReadiness: vi.fn(),
    listPlatformTenantDomains: vi.fn(),
    requestPlatformDomainVerification: vi.fn(),
    verifyPlatformTenantDomain: vi.fn(),
}));

const tenant: TenantRecord = {
    id: 2,
    uuid: 'tenant-2',
    code: 'ACME',
    name: 'Acme',
    slug: 'acme',
    has_logo: false,
    base_currency_id: 1,
    status: 'draft',
    status_reason: null,
    activated_at: null,
    suspended_at: null,
    archived_at: null,
    row_version: 3,
    base_currency: { id: 1, code: 'USD', name: 'US Dollar' },
    current_subscription: null,
    onboarding: null,
    primary_domain: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
};

const readiness: TenantOnboardingReadiness = {
    ready: false,
    tenant_id: tenant.id,
    onboarding_status: 'awaiting_domain',
    checks: { primary_domain_ready: true },
    blockers: [],
    routing: {
        ready: true,
        mode: 'local_fallback',
        message: 'Local/testing tenant routing is explicitly configured for this tenant.',
        local_fallback: {
            supported: true,
            enabled: true,
            configured_tenant_code: tenant.code,
            matches_tenant: true,
        },
    },
    infrastructure: {
        database: { strategy: 'shared_schema', tenant_specific_profiles_supported: false },
        storage: { strategy: 'shared_private_storage', isolation: 'tenant_object_key_prefix', disk: 'tenant_private', tenant_specific_profiles_supported: false },
        mail: { strategy: 'platform_mailer', tenant_specific_profiles_supported: false },
        configuration: { precedence: ['organization_unit', 'tenant', 'global', 'definition_default'], arbitrary_laravel_config_overrides_supported: false },
    },
};

describe('PlatformTenantDomainsPanel', () => {
    beforeEach(() => {
        vi.mocked(changePlatformTenantDomain).mockReset();
        vi.mocked(createPlatformTenantDomain).mockReset();
        vi.mocked(deletePlatformTenantDomain).mockReset();
        vi.mocked(getTenantOnboardingReadiness).mockReset().mockResolvedValue(readiness);
        vi.mocked(listPlatformTenantDomains).mockReset().mockResolvedValue({
            data: [],
            meta: { current_page: 1, from: null, last_page: 1, per_page: 20, to: null, total: 0 },
        });
        vi.mocked(requestPlatformDomainVerification).mockReset();
        vi.mocked(verifyPlatformTenantDomain).mockReset();
    });

    it('shows the active local fallback and rejects IP addresses before an API request', async () => {
        render(
            <MemoryRouter>
                <PlatformTenantDomainsPanel
                    tenant={tenant}
                    canManage
                    canAudit={false}
                    onChanged={vi.fn()}
                />
            </MemoryRouter>,
        );

        expect(await screen.findByText('Local/testing routing is ready')).toBeInTheDocument();
        fireEvent.change(screen.getByLabelText('Public tenant hostname'), { target: { value: '127.0.0.1' } });

        expect(screen.getByText(/Localhost and IP addresses are not public tenant domains/)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Add domain' })).toBeDisabled();
        expect(createPlatformTenantDomain).not.toHaveBeenCalled();
    });
});
