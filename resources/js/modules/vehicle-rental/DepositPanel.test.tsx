import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { listPaymentMethods } from '@/modules/payment/paymentApi';
import { DepositPanel } from './DepositPanel';
import { getDepositSummary, receiveDeposit } from './depositApi';
import { AgreementStatus, DriverMode, RentalBasis, TERM_LABELS, type Agreement, type TermKey } from './agreements';
vi.mock('./depositApi', () => ({ getDepositSummary: vi.fn(), receiveDeposit: vi.fn() }));
vi.mock('@/modules/payment/paymentApi', () => ({ listPaymentMethods: vi.fn() }));
const agreement: Agreement = {
    id: 7, reference: 'CUSTOMER-A', row_version: 2, status: AgreementStatus.Active, basis: RentalBasis.Monthly, driver_mode: DriverMode.SelfDrive,
    party: { id: 2, name: 'Customer A' }, currency: { id: 1, name: 'Rupee', code: 'LKR' }, agreed_on: '2026-01-01', executing_on: null,
    starts_on: '2026-01-31', ends_on: null, effective_coverage_ends_on: null, notes: null,
    terms: Object.fromEntries(Object.keys(TERM_LABELS).map(key => [key, null])) as Record<TermKey, string | null>,
};
beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(getDepositSummary).mockResolvedValue({ requirement: '1000', net_receipts: '0', remaining_to_receive: '1000', payments: [] });
    vi.mocked(listPaymentMethods).mockResolvedValue({ data: [{ id: 4, name: 'Cash', method_type: 'cash' }] } as never);
});
async function fill() {
    render(<MemoryRouter><DepositPanel agreement={agreement} canCreate /></MemoryRouter>);
    await screen.findByRole('option', { name: 'Cash / cash' });
    fireEvent.change(screen.getByLabelText('Method'), { target: { value: '4' } });
    fireEvent.change(screen.getByLabelText('Amount'), { target: { value: '600' } });
    fireEvent.change(screen.getByLabelText('Deposit exchange rate'), { target: { value: '1' } });
}
it('retries the same receipt with its same idempotency key and links the created payment', async () => {
    vi.mocked(receiveDeposit).mockRejectedValueOnce(new ApiError('Connection interrupted.', 503)).mockResolvedValue({ id: 21 } as never);
    await fill();
    fireEvent.click(screen.getByRole('button', { name: 'Create deposit receipt' }));
    await screen.findByText('Connection interrupted.');
    vi.mocked(getDepositSummary).mockResolvedValue({ requirement: '1000', net_receipts: '600', remaining_to_receive: '400', payments: [{ id: 21, row_version: 1, payment_number: 'DEP-21' }] });
    fireEvent.click(screen.getByRole('button', { name: 'Create deposit receipt' }));
    expect(await screen.findByRole('link', { name: 'DEP-21' })).toHaveAttribute('href', '/payments/21');
    expect(vi.mocked(receiveDeposit).mock.calls[0][2]).toBe(vi.mocked(receiveDeposit).mock.calls[1][2]);
    expect(vi.mocked(receiveDeposit).mock.calls[0][1]).toMatchObject({ expected_version: 2, exchange_rate: '1', lines: [{ payment_method_id: 4, amount: '600' }] });
});
it('prevents excess and empty receipts and keeps read-only access free of creation controls', async () => {
    await fill();
    fireEvent.change(screen.getByLabelText('Amount'), { target: { value: '1001' } });
    expect(screen.getByRole('button', { name: 'Create deposit receipt' })).toBeDisabled();
    fireEvent.change(screen.getByLabelText('Amount'), { target: { value: '0' } });
    expect(screen.getByRole('button', { name: 'Create deposit receipt' })).toBeDisabled();
    expect(receiveDeposit).not.toHaveBeenCalled();
});
it('shows receipt history without loading payment methods for a viewer', async () => {
    render(<MemoryRouter><DepositPanel agreement={agreement} canCreate={false} /></MemoryRouter>);
    await waitFor(() => expect(getDepositSummary).toHaveBeenCalled());
    expect(screen.queryByRole('button', { name: 'Create deposit receipt' })).not.toBeInTheDocument();
    expect(listPaymentMethods).not.toHaveBeenCalled();
});
