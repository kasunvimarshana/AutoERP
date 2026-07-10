import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { TENANT_MODULE_CODE, TENANT_MODULES } from '@/app/access/tenantModules';
import { TestRouter } from '@/test/TestRouter';
import type { TenantPlan, TenantPlanRevision } from '../tenantTypes';
import { TenantPlanEditor } from './TenantPlanEditor';

function createPlan(): TenantPlan {
    const revision: TenantPlanRevision = {
        id: 12,
        tenant_plan_id: 7,
        revision_number: 2,
        features_schema_version: 1,
        limits_schema_version: 1,
        features: { enabled_modules: [TENANT_MODULE_CODE.INVENTORY] },
        limits: { max_users: 10 },
        price: '100.000000',
        currency_id: 9,
        currency: { id: 9, code: 'OLD', name: 'Retired Currency', is_active: false },
        billing_interval: 'month' as const,
        effective_at: '2026-01-01T00:00:00Z',
        change_note: 'Initial professional plan terms.',
        created_at: '2026-01-01T00:00:00Z',
    };

    return {
        id: 7,
        name: 'Professional',
        slug: 'professional',
        is_active: true,
        row_version: 4,
        revisions_count: 2,
        total_subscription_count: 3,
        assigned_subscription_count: 1,
        current_subscription_count: 1,
        historical_subscription_count: 2,
        current_revision: revision,
        latest_revision: revision,
        features: { enabled_modules: [TENANT_MODULE_CODE.INVENTORY] },
        limits: { max_users: 10 },
        price: '100.000000',
        currency_id: 9,
        currency: { id: 9, code: 'OLD', name: 'Retired Currency', is_active: false },
        billing_interval: 'month',
        created_at: '2026-01-01T00:00:00Z',
        updated_at: '2026-01-01T00:00:00Z',
    };
}

function renderNewPlan(onSubmit = vi.fn().mockResolvedValue(undefined)) {
    render(
        <TestRouter>
            <TenantPlanEditor
                plan={null}
                currencies={[{ id: 1, code: 'USD', name: 'US Dollar', is_active: true, row_version: 1, updated_at: null }]}
                saving={false}
                error={null}
                onCancel={vi.fn()}
                onSubmit={onSubmit}
            />
        </TestRouter>,
    );

    return onSubmit;
}

describe('TenantPlanEditor', () => {
    it('renders every supported tenant module in the commercial module selector', () => {
        renderNewPlan();

        expect(screen.getByText('Enabled commercial modules')).toBeInTheDocument();
        for (const module of TENANT_MODULES) {
            expect(screen.getByLabelText(module.label)).toBeInTheDocument();
        }
    });

    it('submits human resources when the HR commercial module is enabled', async () => {
        const onSubmit = renderNewPlan();

        fireEvent.change(screen.getByLabelText('Plan name'), { target: { value: 'People Plan' } });
        fireEvent.change(screen.getByLabelText('Plan slug'), { target: { value: 'people-plan' } });
        fireEvent.click(screen.getByLabelText('Human resources'));
        fireEvent.change(screen.getByLabelText('Revision reason'), { target: { value: 'Enable HR for the people operations plan.' } });
        fireEvent.click(screen.getByRole('button', { name: 'Review new plan' }));
        fireEvent.click(await screen.findByRole('button', { name: 'Create plan' }));

        await waitFor(() => expect(onSubmit).toHaveBeenCalledTimes(1));
        const payload = onSubmit.mock.calls[0][0] as Record<string, { enabled_modules?: string[] }>;
        expect(payload.features.enabled_modules).toEqual([TENANT_MODULE_CODE.HR]);
    });

    it('omits a blank effective date instead of sending null', async () => {
        const onSubmit = renderNewPlan();

        fireEvent.change(screen.getByLabelText('Plan name'), { target: { value: 'Professional' } });
        fireEvent.change(screen.getByLabelText('Plan slug'), { target: { value: 'professional' } });
        fireEvent.change(screen.getByLabelText('Price'), { target: { value: '100.00' } });
        fireEvent.change(screen.getByLabelText('Billing currency'), { target: { value: '1' } });
        fireEvent.change(screen.getByLabelText('Revision reason'), { target: { value: 'Initial professional plan terms.' } });
        fireEvent.click(screen.getByRole('button', { name: 'Review new plan' }));
        fireEvent.click(await screen.findByRole('button', { name: 'Create plan' }));

        await waitFor(() => expect(onSubmit).toHaveBeenCalledTimes(1));
        const payload = onSubmit.mock.calls[0][0] as Record<string, unknown>;
        expect(payload).not.toHaveProperty('effective_at');
        expect(payload).not.toHaveProperty('is_active');
    });

    it('sends only changed fields when revising a plan with an inactive current currency', async () => {
        const onSubmit = vi.fn().mockResolvedValue(undefined);
        const plan = createPlan();

        render(
            <TestRouter>
                <TenantPlanEditor
                    plan={plan}
                    currencies={[]}
                    saving={false}
                    error={null}
                    onCancel={vi.fn()}
                    onSubmit={onSubmit}
                />
            </TestRouter>,
        );

        fireEvent.change(screen.getByLabelText('Plan name'), { target: { value: 'Professional Plus' } });
        fireEvent.click(screen.getByRole('button', { name: 'Review plan changes' }));
        fireEvent.click(await screen.findByRole('button', { name: 'Save plan changes' }));

        await waitFor(() => expect(onSubmit).toHaveBeenCalledTimes(1));
        expect(onSubmit.mock.calls[0][0]).toEqual({ name: 'Professional Plus' });
    });

    it('requires an active currency before creating a new revision from an inactive currency', async () => {
        const onSubmit = vi.fn().mockResolvedValue(undefined);
        const plan = createPlan();

        render(
            <TestRouter>
                <TenantPlanEditor
                    plan={plan}
                    currencies={[]}
                    saving={false}
                    error={null}
                    onCancel={vi.fn()}
                    onSubmit={onSubmit}
                />
            </TestRouter>,
        );

        fireEvent.click(screen.getByLabelText('Purchasing'));
        fireEvent.change(screen.getByLabelText('Revision reason'), { target: { value: 'Enable purchasing for the next contract cycle.' } });
        fireEvent.click(screen.getByRole('button', { name: 'Review plan changes' }));

        expect(await screen.findByText('Select an active billing currency for the new revision.')).toBeInTheDocument();
        expect(onSubmit).not.toHaveBeenCalled();
    });
});
