import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { MileageAssessmentPanel } from './MileageAssessmentPanel';
import { AgreementKind } from './agreements';
import { RunningChartStatus, type RunningChart } from './runningCharts';
import { assessMileage, quoteMileage, MileagePolicy } from './mileageApi';
vi.mock('./mileageApi', async importOriginal => ({ ...await importOriginal<object>(), assessMileage: vi.fn(), quoteMileage: vi.fn() }));
const chart = { id: 9, row_version: 2, reference: 'CHART-A', status: RunningChartStatus.Finalized } as RunningChart;
const quote = { policy: MileagePolicy.CommercialCalendarCycles, timezone: 'Asia/Colombo', cycle_from: '2026-09-07', cycle_until: '2026-10-06', allowance: '100', distance: '40', included_applied: '40', excess_km: '0', rate: '90', amount: '0.000000', currency: 'LKR', pool_head: 0, agreement: { reference: 'AG-A', version: 2 } };
beforeEach(() => { vi.clearAllMocks(); vi.mocked(quoteMileage).mockResolvedValue(quote); vi.mocked(assessMileage).mockResolvedValue({ assessment: { id: 1, row_version: 1 }, invoice: null }); });
async function setup() {
    const saved = vi.fn(); render(<MemoryRouter><MileageAssessmentPanel kind={AgreementKind.Customer} chart={chart} onSaved={saved} /></MemoryRouter>);
    await screen.findByText('LKR 0.000000');
    fireEvent.change(screen.getByLabelText('Invoice date'), { target: { value: '2026-09-15' } });
    fireEvent.change(screen.getByLabelText('Exchange rate to base currency'), { target: { value: '1' } });
    return saved;
}
it('preserves a zero assessment after explicit policy acceptance without claiming an invoice exists', async () => {
    const saved = await setup(); expect(screen.getByRole('button', { name: 'Record mileage assessment' })).toBeDisabled();
    fireEvent.click(screen.getByRole('checkbox')); fireEvent.click(screen.getByRole('button', { name: 'Record mileage assessment' }));
    expect(await screen.findByText(/No invoice is due/)).toBeInTheDocument(); expect(saved).toHaveBeenCalledOnce();
    expect(assessMileage).toHaveBeenCalledWith(AgreementKind.Customer, chart, quote, { invoice_date: '2026-09-15', due_date: null, exchange_rate: '1' });
});
it('clears stale pricing after a conflicting allowance change', async () => {
    vi.mocked(assessMileage).mockRejectedValue(new ApiError('Allowance changed. Review a new quote.', 409));
    await setup(); fireEvent.click(screen.getByRole('checkbox')); fireEvent.click(screen.getByRole('button', { name: 'Record mileage assessment' }));
    expect(await screen.findByText('Allowance changed. Review a new quote.')).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Record mileage assessment' })).not.toBeInTheDocument();
    fireEvent.click(screen.getByRole('button', { name: 'Reload mileage quote' }));
    await waitFor(() => expect(quoteMileage).toHaveBeenCalledTimes(2));
});
