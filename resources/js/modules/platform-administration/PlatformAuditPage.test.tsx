import { render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import PlatformAuditPage from './PlatformAuditPage';

const apiMocks = vi.hoisted(() => ({
    listAudit: vi.fn(),
    getPlatformTenantTarget: vi.fn(),
    listPlatformTenantTargets: vi.fn(),
}));

vi.mock('./platformAdministrationApi', () => ({
    platformAdministrationApi: {
        listAudit: apiMocks.listAudit,
    },
}));

vi.mock('@/modules/tenant/tenantApi', () => ({
    getPlatformTenantTarget: apiMocks.getPlatformTenantTarget,
    listPlatformTenantTargets: apiMocks.listPlatformTenantTargets,
}));

describe('PlatformAuditPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getPlatformTenantTarget.mockResolvedValue({ id: 7, name: 'Acme Mobility', code: 'ACME', status: 'active' });
        apiMocks.listPlatformTenantTargets.mockResolvedValue({
            data: [],
            meta: { current_page: 1, from: null, last_page: 1, per_page: 20, to: null, total: 0 },
        });
        apiMocks.listAudit.mockResolvedValue({
            data: [],
            meta: { next_cursor: null, has_more: false, per_page: 25 },
        });
    });

    it('honours tenant and related-record deep-link filters without exposing raw identifiers', async () => {
        render(
            <TestRouter initialEntries={['/administration/platform-audit?tenant_id=7&subject_type=tenant_domain&subject_id=44']}>
                <PlatformAuditPage />
            </TestRouter>,
        );

        await waitFor(() => expect(apiMocks.getPlatformTenantTarget).toHaveBeenCalledWith('audit', 7, expect.any(AbortSignal)));
        await waitFor(() => expect(apiMocks.listAudit).toHaveBeenCalledWith(expect.objectContaining({
            tenant_id: 7,
            subject_type: 'tenant_domain',
            subject_id: '44',
            per_page: 25,
        }), expect.any(AbortSignal)));

        expect(await screen.findByText(/scoped to the related tenant domain/i)).toBeInTheDocument();
        expect(await screen.findByDisplayValue('Acme Mobility · ACME')).toBeInTheDocument();
        expect(screen.queryByText(/^44$/)).not.toBeInTheDocument();
    });
});
