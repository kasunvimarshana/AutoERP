import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { UsageChargeForm } from './UsageChargePanel';
import { AgreementKind } from './agreements';
import { RunningChartStatus, type RunningChart } from './runningCharts';
import { billUsage, loadUsageCharges, reissueUsage, voidUsage, UsageChargeComponent } from './usageChargeApi';
vi.mock('./usageChargeApi', async importOriginal => ({ ...await importOriginal<object>(), billUsage: vi.fn(), loadUsageCharges: vi.fn(), reissueUsage: vi.fn(), voidUsage: vi.fn() }));
const chart: RunningChart = { id: 9, row_version: 2, reference: 'CHART-A', status: RunningChartStatus.Finalized, starts_at: '2026-09-07T09:00:00+05:30', ends_at: '2026-09-07T17:00:00+05:30', start_odometer: null, end_odometer: null, total_km: null, garage_km: null, commercial_km: null, normal_ot_minutes: 90, double_ot_minutes: null, triple_ot_minutes: null, night_outs: null, ac_mode: null, notes: null, driver_observation: null, corrects_chart: null };
const charge = { id: 3, row_version: 1, component: UsageChargeComponent.NormalOvertime, amount: '750.000000', voided_at: null, void_reason: null, calculation: { description: 'Normal OT · CHART-A · 90 minutes at 500/hour', currency: 'LKR' }, invoices: [{ id: 7, number: 'INV-7', status: 'cancelled' }] };
const list = { currency: 'LKR', components: [{ component: UsageChargeComponent.NormalOvertime, label: 'Normal OT', quantity: 90, rate: '500.000000', denominator: 60, amount: '750.000000', error: null }], agreement: { reference: 'AGREEMENT-A', version: 2 }, charges: { data: [] as typeof charge[], current_page: 1, last_page: 1 } };
beforeEach(() => { vi.clearAllMocks(); vi.mocked(loadUsageCharges).mockResolvedValue(list); vi.mocked(billUsage).mockResolvedValue({ id: 8, invoice_number: 'INV-8', grand_total: '750.000000' }); });
async function setup() { render(<MemoryRouter><UsageChargeForm kind={AgreementKind.Customer} chart={chart} /></MemoryRouter>); await screen.findByLabelText('Component'); }
function documentFields() { fireEvent.change(screen.getByLabelText('Invoice date'), { target: { value: '2026-09-12' } }); fireEvent.change(screen.getByLabelText('Exchange rate to base currency'), { target: { value: '1' } }); }
it('requires policy acceptance and submits only component, revisions and document inputs', async () => {
    await setup(); documentFields(); fireEvent.change(screen.getByLabelText('Component'), { target: { value: UsageChargeComponent.NormalOvertime } });
    expect(screen.getByRole('button', { name: 'Create invoice draft' })).toBeDisabled();
    fireEvent.click(screen.getByRole('checkbox')); fireEvent.click(screen.getByRole('button', { name: 'Create invoice draft' }));
    expect(await screen.findByRole('link', { name: 'INV-8' })).toHaveAttribute('href', '/invoices/8');
    expect(billUsage).toHaveBeenCalledWith(AgreementKind.Customer, chart, 2, UsageChargeComponent.NormalOvertime, { invoice_date: '2026-09-12', due_date: null, exchange_rate: '1' });
});
it('shows conflicts without a success message', async () => {
    vi.mocked(billUsage).mockRejectedValue(new ApiError('Chart changed. Reload.', 409));
    await setup(); documentFields(); fireEvent.change(screen.getByLabelText('Component'), { target: { value: UsageChargeComponent.NormalOvertime } });
    fireEvent.click(screen.getByRole('checkbox')); fireEvent.click(screen.getByRole('button', { name: 'Create invoice draft' }));
    expect(await screen.findByText('Chart changed. Reload.')).toBeInTheDocument(); expect(screen.queryByRole('link', { name: 'INV-8' })).not.toBeInTheDocument();
});
it('voids a released charge with a reason and no invoice form requirements', async () => {
    vi.mocked(loadUsageCharges).mockResolvedValue({ ...list, charges: { ...list.charges, data: [charge] } });
    await setup(); fireEvent.click(screen.getByRole('button', { name: 'Void charge' }));
    fireEvent.change(screen.getByLabelText('Void reason'), { target: { value: 'Correct signed evidence' } }); fireEvent.click(screen.getByRole('checkbox'));
    expect(screen.queryByLabelText('Invoice date')).not.toBeInTheDocument();
    fireEvent.click(screen.getAllByRole('button', { name: 'Void charge' })[0]);
    await waitFor(() => expect(voidUsage).toHaveBeenCalledWith(AgreementKind.Customer, chart, 2, charge, 'Correct signed evidence'));
});
it('reissues the stored charge rather than calculating another component', async () => {
    vi.mocked(loadUsageCharges).mockResolvedValue({ ...list, charges: { ...list.charges, data: [charge] } });
    vi.mocked(reissueUsage).mockResolvedValue({ id: 8, invoice_number: 'INV-8', grand_total: '750.000000' });
    await setup(); fireEvent.click(screen.getByRole('button', { name: 'Reissue charge' })); documentFields();
    fireEvent.click(screen.getByRole('checkbox')); fireEvent.click(screen.getByRole('button', { name: 'Create invoice draft' }));
    await waitFor(() => expect(reissueUsage).toHaveBeenCalledWith(AgreementKind.Customer, chart, 2, charge, { invoice_date: '2026-09-12', due_date: null, exchange_rate: '1' }));
    expect(billUsage).not.toHaveBeenCalled();
});
it('hides correction actions while the invoice is live', async () => {
    vi.mocked(loadUsageCharges).mockResolvedValue({ ...list, charges: { ...list.charges, data: [{ ...charge, invoices: [{ ...charge.invoices[0], status: 'draft' }] }] } });
    await setup(); expect(screen.queryByRole('button', { name: 'Void charge' })).not.toBeInTheDocument(); expect(screen.queryByRole('button', { name: 'Reissue charge' })).not.toBeInTheDocument();
});
it('shows server pricing and prevents billing an unknown component', async () => {
    vi.mocked(loadUsageCharges).mockResolvedValue({ ...list, components: [{ ...list.components[0], amount: null, rate: null, error: 'The agreed rate is not known.' }] });
    await setup(); documentFields(); fireEvent.change(screen.getByLabelText('Component'), { target: { value: UsageChargeComponent.NormalOvertime } });
    fireEvent.click(screen.getByRole('checkbox'));
    expect(screen.getByText(/The agreed rate is not known/)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Create invoice draft' })).toBeDisabled(); expect(billUsage).not.toHaveBeenCalled();
});
