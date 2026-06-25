import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import type { TenantRecord } from '../tenantTypes';
import { PlatformTenantForm } from './PlatformTenantForm';

const tenant: TenantRecord = {
    id: 5,
    uuid: 'tenant-5',
    code: 'ACME',
    name: 'Acme Ltd',
    slug: 'acme',
    has_logo: false,
    base_currency_id: 9,
    status: 'active',
    status_reason: null,
    activated_at: '2026-01-01T00:00:00Z',
    suspended_at: null,
    archived_at: null,
    row_version: 8,
    base_currency: { id: 9, code: 'OLD', name: 'Retired Currency', is_active: false },
    current_subscription: null,
    onboarding: null,
    primary_domain: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
};

describe('PlatformTenantForm', () => {
    it('omits locked and unchanged fields from an update payload', async () => {
        const onSubmit = vi.fn().mockResolvedValue(undefined);
        render(
            <TestRouter>
                <PlatformTenantForm
                    tenant={tenant}
                    currencies={[]}
                    saving={false}
                    error={null}
                    onCancel={vi.fn()}
                    onSubmit={onSubmit}
                />
            </TestRouter>,
        );

        fireEvent.change(screen.getByLabelText('Tenant name'), { target: { value: 'Acme Holdings' } });
        fireEvent.click(screen.getByRole('button', { name: 'Save tenant identity' }));

        await waitFor(() => expect(onSubmit).toHaveBeenCalledTimes(1));
        const payload = onSubmit.mock.calls[0][0] as FormData;
        expect(Object.fromEntries(payload.entries())).toEqual({
            expected_version: '8',
            name: 'Acme Holdings',
        });
    });
});
