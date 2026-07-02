import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import PlatformHealthPage from './PlatformHealthPage';

const mocks = vi.hoisted(() => ({
    getHealth: vi.fn(),
    getTenantHealth: vi.fn(),
    retryFailedDomains: vi.fn(),
    retryOutbox: vi.fn(),
    retryStorageCleanup: vi.fn(),
    hasPermission: vi.fn(),
    listPlatformTenantTargets: vi.fn(),
}));

vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => ({}) }));
vi.mock('@/modules/auth/accessControl', () => ({ hasPermission: mocks.hasPermission }));
vi.mock('@/modules/tenant/tenantApi', () => ({ listPlatformTenantTargets: mocks.listPlatformTenantTargets }));
vi.mock('./platformAdministrationApi', () => ({
    platformAdministrationApi: {
        getHealth: mocks.getHealth,
        getTenantHealth: mocks.getTenantHealth,
        retryFailedDomains: mocks.retryFailedDomains,
        retryOutbox: mocks.retryOutbox,
        retryStorageCleanup: mocks.retryStorageCleanup,
    },
}));

describe('PlatformHealthPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mocks.hasPermission.mockReturnValue(true);
        mocks.retryFailedDomains.mockResolvedValue(2);
        mocks.listPlatformTenantTargets.mockResolvedValue({
            data: [],
            meta: { current_page: 1, from: null, last_page: 1, per_page: 20, to: null, total: 0 },
        });
        mocks.getHealth.mockResolvedValue(healthOverview());
    });

    it('requires an auditable reason before requeueing failed operational domain checks', async () => {
        const user = userEvent.setup();
        render(<TestRouter initialEntries={['/administration/platform-health']}><PlatformHealthPage /></TestRouter>);

        await user.click(await screen.findByRole('button', { name: 'Recheck failed operational domains' }));
        const dialog = await screen.findByRole('dialog', { name: 'Retry failed platform operation' });
        const submit = within(dialog).getByRole('button', { name: 'Queue domain rechecks' });
        expect(submit).toBeDisabled();

        await user.type(within(dialog).getByLabelText('Recovery reason'), 'Retry after correcting proxy routing.');
        expect(submit).toBeEnabled();
        await user.click(submit);

        await waitFor(() => expect(mocks.retryFailedDomains).toHaveBeenCalledWith(null, 'Retry after correcting proxy routing.'));
        expect(await screen.findByText('2 failed operations returned to the retry queue.')).toBeInTheDocument();
    });
});

function healthOverview() {
    return {
        generated_at: '2026-06-25T00:00:00Z',
        release: { release_id: 'release-1', commit: 'abc123', environment: 'testing', database_strategy: 'shared_schema' },
        tenants: { active: 1, draft: 0, suspended: 0 },
        onboarding: { ready: 1, completed: 1 },
        domains: { ownership: { verified: 1, pending: 0 }, operational: { ready: 0, failed: 1 } },
        subscriptions: { assigned: 1, expired: 0, cancelled: 0 },
        operations: { outbox: { pending: 0, dead: 0 }, storage_cleanup: { pending: 0, dead: 0 } },
        infrastructure: {
            ready: true,
            mail: { ready: true, mailer: 'smtp', from_address_configured: true, external_transport: true },
            queue: { ready: true, connection: 'database', requires_worker: true, pending_jobs: 0, failed_jobs: 0 },
            capabilities: {
                database: { strategy: 'shared_schema', tenant_specific_profiles_supported: false },
                storage: { strategy: 'shared_private_storage', isolation: 'tenant_object_key_prefix', disk: 'tenant_private', tenant_specific_profiles_supported: false },
                mail: { strategy: 'platform_mailer', tenant_specific_profiles_supported: false },
                configuration: { precedence: ['organization_unit', 'tenant', 'global', 'definition_default'], arbitrary_laravel_config_overrides_supported: false },
            },
        },
        storage: { tracked_document_bytes: 0, tracked_document_count: 0 },
        alerts: {
            onboarding_failures: 0,
            domain_failures: 1,
            dead_outbox_events: 0,
            dead_storage_cleanup_jobs: 0,
            requires_attention: true,
        },
        failures: {
            onboarding: [],
            domains: [{
                tenant_id: 7,
                tenant_code: 'ACME',
                tenant_name: 'Acme Mobility',
                domain: 'acme.example.test',
                ownership_status: 'verified',
                operational_status: 'failed',
                error_code: 'ROUTING_UNREACHABLE',
                error_message: 'Application route is not reachable.',
                updated_at: '2026-06-25T00:00:00Z',
            }],
            outbox: [],
            storage_cleanup: [],
        },
    };
}
