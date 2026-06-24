import { MemoryRouter } from 'react-router-dom';
import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { changeTenantStatus, getTenantOnboardingReadiness } from '../tenantApi';
import type { TenantOnboardingReadiness, TenantRecord } from '../tenantTypes';
import { PlatformTenantActivationPanel } from './PlatformTenantActivationPanel';

vi.mock('../tenantApi', () => ({
    getTenantOnboardingReadiness: vi.fn(),
    changeTenantStatus: vi.fn(),
}));

const tenant: TenantRecord = {
    id: 1,
    uuid: 'tenant-uuid',
    code: 'ACME',
    name: 'Acme',
    slug: 'acme',
    has_logo: false,
    cross_org_transactions: false,
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

const blocked: TenantOnboardingReadiness = {
    ready: false,
    tenant_id: 1,
    onboarding_status: 'pending',
    checks: { base_currency: true, verified_primary_domain: false, subscription: false },
    blockers: [
        { code: 'verified_primary_domain', message: 'Verify a primary domain.' },
        { code: 'subscription', message: 'Assign a subscription.' },
    ],
};

const ready: TenantOnboardingReadiness = {
    ...blocked,
    ready: true,
    checks: { base_currency: true, verified_primary_domain: true, subscription: true },
    blockers: [],
};

describe('PlatformTenantActivationPanel', () => {
    beforeEach(() => {
        vi.mocked(changeTenantStatus).mockReset();
        vi.mocked(getTenantOnboardingReadiness).mockReset();
    });

    it('keeps activation disabled and renders actionable blockers until readiness passes', async () => {
        vi.mocked(getTenantOnboardingReadiness).mockResolvedValue(blocked);
        render(<MemoryRouter><PlatformTenantActivationPanel tenant={tenant} canActivate onChanged={vi.fn()} /></MemoryRouter>);

        expect(await screen.findByText('Verify a primary domain.')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Activate tenant' })).toBeDisabled();
        expect(screen.getAllByRole('button', { name: 'Resolve' })).toHaveLength(2);
    });

    it('activates only after readiness and a meaningful audit reason', async () => {
        vi.mocked(getTenantOnboardingReadiness).mockResolvedValue(ready);
        const updated = { ...tenant, status: 'active' as const, row_version: 4 };
        vi.mocked(changeTenantStatus).mockResolvedValue(updated);
        const onChanged = vi.fn();
        render(<MemoryRouter><PlatformTenantActivationPanel tenant={tenant} canActivate onChanged={onChanged} /></MemoryRouter>);

        await screen.findByText('Ready for activation');
        fireEvent.change(screen.getByPlaceholderText(/Onboarding, DNS verification/), { target: { value: 'All readiness checks were approved.' } });
        fireEvent.click(screen.getByRole('button', { name: 'Activate tenant' }));
        const dialog = await screen.findByRole('dialog', { name: 'Activate tenant workspace' });
        fireEvent.click(within(dialog).getByRole('button', { name: 'Activate tenant' }));

        await waitFor(() => expect(changeTenantStatus).toHaveBeenCalledWith(1, 'activate', 3, 'All readiness checks were approved.'));
        expect(onChanged).toHaveBeenCalledWith(updated);
    });
});
