import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { TENANT_MODULE_CODE, TENANT_MODULES } from '@/app/access/tenantModules';
import { TestRouter } from '@/test/TestRouter';
import type { TenantPlan, TenantPlanRevision } from '../tenantTypes';
import { TenantPlanEditor } from './TenantPlanEditor';

const RETIRED_TENANT_MODULE_CODE = TENANT_MODULE_CODE.VEHICLE_RENTAL;

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

function createFreePlan(): TenantPlan {
    const revision: TenantPlanRevision = {
        id: 13,
        tenant_plan_id: 8,
        revision_number: 1,
        features_schema_version: 1,
        limits_schema_version: 1,
        features: { enabled_modules: [TENANT_MODULE_CODE.CUSTOMER] },
        limits: {},
        price: '0.000000',
        currency_id: null,
        currency: null,
        billing_interval: 'month' as const,
        effective_at: '2026-01-01T00:00:00Z',
        change_note: 'Initial free plan terms.',
        created_at: '2026-01-01T00:00:00Z',
    };

    return {
        id: 8,
        name: 'Community Free',
        slug: 'community-free',
        is_active: true,
        row_version: 1,
        revisions_count: 1,
        total_subscription_count: 0,
        assigned_subscription_count: 0,
        current_subscription_count: 0,
        historical_subscription_count: 0,
        current_revision: revision,
        latest_revision: revision,
        features: { enabled_modules: [TENANT_MODULE_CODE.CUSTOMER] },
        limits: {},
        price: '0.000000',
        currency_id: null,
        currency: null,
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
        expect(screen.getByLabelText('Selling')).toBeInTheDocument();
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

    it('requires explicit selection before adding fresh Rental to a new plan', async () => {
        const onSubmit = renderNewPlan();
        expect(screen.getByLabelText('Vehicle rental')).not.toBeChecked();
        fireEvent.change(screen.getByLabelText('Plan name'), { target: { value: 'Rental Plan' } });
        fireEvent.change(screen.getByLabelText('Plan slug'), { target: { value: 'rental-plan' } });
        fireEvent.click(screen.getByLabelText('Vehicle rental'));
        fireEvent.change(screen.getByLabelText('Revision reason'), { target: { value: 'Enable the fresh Rental agreement module.' } });
        fireEvent.click(screen.getByRole('button', { name: 'Review new plan' }));
        fireEvent.click(await screen.findByRole('button', { name: 'Create plan' }));
        await waitFor(() => expect(onSubmit).toHaveBeenCalledTimes(1));
        expect(onSubmit.mock.calls[0][0].features.enabled_modules).toEqual([TENANT_MODULE_CODE.VEHICLE_RENTAL]);
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

    it('sends only changed fields when a historical revision contains a retired module', async () => {
        const onSubmit = vi.fn().mockResolvedValue(undefined);
        const plan = createPlan();
        if (plan.latest_revision) {
            plan.latest_revision.features.enabled_modules = [
                TENANT_MODULE_CODE.INVENTORY,
                RETIRED_TENANT_MODULE_CODE,
            ] as TenantPlanRevision['features']['enabled_modules'];
        }

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

        expect(screen.getByLabelText('Vehicle rental')).not.toBeChecked();
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

    it('automatically loads the default configured currency when creating a new plan', () => {
        render(
            <TestRouter>
                <TenantPlanEditor
                    plan={null}
                    currencies={[
                        { id: 10, code: 'EUR', name: 'Euro', is_active: true, row_version: 1, updated_at: null },
                        { id: 20, code: 'LKR', name: 'Sri Lankan Rupee', is_active: true, is_default: true, row_version: 1, updated_at: null },
                    ]}
                    saving={false}
                    error={null}
                    onCancel={vi.fn()}
                    onSubmit={vi.fn()}
                />
            </TestRouter>,
        );

        const select = screen.getByLabelText('Billing currency') as HTMLSelectElement;
        expect(select.value).toBe('20');
    });

    it('allows users to select a different currency when creating a plan and respects the selection', async () => {
        const onSubmit = vi.fn().mockResolvedValue(undefined);
        render(
            <TestRouter>
                <TenantPlanEditor
                    plan={null}
                    currencies={[
                        { id: 10, code: 'USD', name: 'US Dollar', is_active: true, is_default: true, row_version: 1, updated_at: null },
                        { id: 20, code: 'EUR', name: 'Euro', is_active: true, row_version: 1, updated_at: null },
                    ]}
                    saving={false}
                    error={null}
                    onCancel={vi.fn()}
                    onSubmit={onSubmit}
                />
            </TestRouter>,
        );

        const select = screen.getByLabelText('Billing currency') as HTMLSelectElement;
        expect(select.value).toBe('10');

        // User chooses EUR instead
        fireEvent.change(select, { target: { value: '20' } });
        expect(select.value).toBe('20');

        fireEvent.change(screen.getByLabelText('Plan name'), { target: { value: 'Euro Plan' } });
        fireEvent.change(screen.getByLabelText('Plan slug'), { target: { value: 'euro-plan' } });
        fireEvent.change(screen.getByLabelText('Price'), { target: { value: '50.00' } });
        fireEvent.change(screen.getByLabelText('Revision reason'), { target: { value: 'Euro commercial plan.' } });

        fireEvent.click(screen.getByRole('button', { name: 'Review new plan' }));
        fireEvent.click(await screen.findByRole('button', { name: 'Create plan' }));

        await waitFor(() => expect(onSubmit).toHaveBeenCalledTimes(1));
        const payload = onSubmit.mock.calls[0][0] as Record<string, unknown>;
        expect(payload.currency_id).toBe(20);
    });

    it('retains the existing configured currency when editing an existing plan', () => {
        const plan = createPlan();
        render(
            <TestRouter>
                <TenantPlanEditor
                    plan={plan}
                    currencies={[
                        { id: 1, code: 'USD', name: 'US Dollar', is_active: true, is_default: true, row_version: 1, updated_at: null },
                    ]}
                    saving={false}
                    error={null}
                    onCancel={vi.fn()}
                    onSubmit={vi.fn()}
                />
            </TestRouter>,
        );

        const select = screen.getByLabelText('Billing currency') as HTMLSelectElement;
        expect(select.value).toBe('9');
    });

    it('allows selecting a different currency when editing an existing plan and sends the update', async () => {
        const onSubmit = vi.fn().mockResolvedValue(undefined);
        const plan = createPlan();

        render(
            <TestRouter>
                <TenantPlanEditor
                    plan={plan}
                    currencies={[
                        { id: 1, code: 'USD', name: 'US Dollar', is_active: true, is_default: true, row_version: 1, updated_at: null },
                        { id: 2, code: 'EUR', name: 'Euro', is_active: true, row_version: 1, updated_at: null },
                    ]}
                    saving={false}
                    error={null}
                    onCancel={vi.fn()}
                    onSubmit={onSubmit}
                />
            </TestRouter>,
        );

        const select = screen.getByLabelText('Billing currency') as HTMLSelectElement;
        fireEvent.change(select, { target: { value: '2' } });
        fireEvent.change(screen.getByLabelText('Revision reason'), { target: { value: 'Switch billing currency to Euro.' } });

        fireEvent.click(screen.getByRole('button', { name: 'Review plan changes' }));
        fireEvent.click(await screen.findByRole('button', { name: 'Save plan changes' }));

        await waitFor(() => expect(onSubmit).toHaveBeenCalledTimes(1));
        const payload = onSubmit.mock.calls[0][0] as Record<string, unknown>;
        expect(payload.currency_id).toBe(2);
    });

    it('does not mark the form dirty or inject currency when editing an existing free plan', () => {
        const plan = createFreePlan();
        render(
            <TestRouter>
                <TenantPlanEditor
                    plan={plan}
                    currencies={[
                        { id: 1, code: 'USD', name: 'US Dollar', is_active: true, is_default: true, row_version: 1, updated_at: null },
                    ]}
                    saving={false}
                    error={null}
                    onCancel={vi.fn()}
                    onSubmit={vi.fn()}
                />
            </TestRouter>,
        );

        const select = screen.getByLabelText('Billing currency') as HTMLSelectElement;
        expect(select.value).toBe('');
        const submitButton = screen.getByRole('button', { name: 'Review plan changes' });
        expect(submitButton).toBeDisabled();
    });

    it('allows saving identity-only changes on a free plan without requiring revision notes', async () => {
        const onSubmit = vi.fn().mockResolvedValue(undefined);
        const plan = createFreePlan();

        render(
            <TestRouter>
                <TenantPlanEditor
                    plan={plan}
                    currencies={[
                        { id: 1, code: 'USD', name: 'US Dollar', is_active: true, is_default: true, row_version: 1, updated_at: null },
                    ]}
                    saving={false}
                    error={null}
                    onCancel={vi.fn()}
                    onSubmit={onSubmit}
                />
            </TestRouter>,
        );

        fireEvent.change(screen.getByLabelText('Plan name'), { target: { value: 'Community Free v2' } });
        const submitButton = screen.getByRole('button', { name: 'Review plan changes' });
        expect(submitButton).toBeEnabled();
        fireEvent.click(submitButton);

        fireEvent.click(await screen.findByRole('button', { name: 'Save plan changes' }));

        await waitFor(() => expect(onSubmit).toHaveBeenCalledTimes(1));
        const payload = onSubmit.mock.calls[0][0] as Record<string, unknown>;
        expect(payload).toEqual({ name: 'Community Free v2' });
        expect(payload).not.toHaveProperty('currency_id');
        expect(payload).not.toHaveProperty('change_note');
    });

    it('automatically populates the default currency when price is changed from free to positive on an existing plan', async () => {
        const plan = createFreePlan();

        render(
            <TestRouter>
                <TenantPlanEditor
                    plan={plan}
                    currencies={[
                        { id: 1, code: 'USD', name: 'US Dollar', is_active: true, is_default: true, row_version: 1, updated_at: null },
                        { id: 2, code: 'EUR', name: 'Euro', is_active: true, row_version: 1, updated_at: null },
                    ]}
                    saving={false}
                    error={null}
                    onCancel={vi.fn()}
                    onSubmit={vi.fn()}
                />
            </TestRouter>,
        );

        const select = screen.getByLabelText('Billing currency') as HTMLSelectElement;
        expect(select.value).toBe('');

        fireEvent.change(screen.getByLabelText('Price'), { target: { value: '29.990000' } });
        expect(select.value).toBe('1');
    });
});
