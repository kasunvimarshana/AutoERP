import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { BaseRentBillingPanel } from './BaseRentBillingPanel';
import { billBaseRent, loadBaseCharges, reissueBaseRent, voidBaseCharge } from './baseRentBillingApi';
import { AgreementKind, AgreementStatus, DriverMode, RentalBasis, TERM_LABELS, type Agreement, type TermKey } from './agreements';
vi.mock('./baseRentBillingApi', () => ({ billBaseRent: vi.fn(), loadBaseCharges: vi.fn(), reissueBaseRent: vi.fn(), voidBaseCharge: vi.fn() }));
const agreement: Agreement = {
    id: 7, reference: 'CUSTOMER-A', row_version: 2, status: AgreementStatus.Active, basis: RentalBasis.Monthly, driver_mode: DriverMode.SelfDrive,
    party: { id: 2, name: 'Customer A' }, currency: { id: 1, name: 'Rupee', code: 'LKR' }, agreed_on: '2026-01-01', executing_on: null,
    starts_on: '2026-01-31', ends_on: null, notes: null,
    terms: { ...Object.fromEntries(Object.keys(TERM_LABELS).map(key => [key, null])) as Record<TermKey, string | null>, base_rate: '3100.000000' },
};

const charge = { id: 12, row_version: 1, voided_at: null, void_reason: null, from: '2026-01-31', until: '2026-02-27', amount: '3100.000000', currency: 'LKR', invoices: [{ id: 20, number: 'INV-20', status: 'cancelled' }] };
beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(loadBaseCharges).mockResolvedValue({ data: [], current_page: 1, last_page: 1 });
    vi.mocked(billBaseRent).mockResolvedValue({ id: 21, invoice_number: 'INV-21', grand_total: '3100.000000' });
});
function setup() {
    render(<MemoryRouter><BaseRentBillingPanel kind={AgreementKind.Customer} agreement={agreement} /></MemoryRouter>);
    fireEvent.click(screen.getByText('Bill base rent'));
}
function documentFields() {
    fireEvent.change(screen.getByLabelText('Invoice date'), { target: { value: '2026-02-27' } });
    fireEvent.change(screen.getByLabelText('Exchange rate to base currency'), { target: { value: '1' } });
}
it('requires explicit policy acceptance and sends dates with verified document inputs', async () => {
    setup();
    fireEvent.change(screen.getByLabelText('Charge through'), { target: { value: '2026-02-27' } });
    documentFields();
    expect(screen.getByRole('button', { name: 'Create invoice draft' })).toBeDisabled();
    fireEvent.click(screen.getByRole('checkbox'));
    fireEvent.click(screen.getByRole('button', { name: 'Create invoice draft' }));
    expect(await screen.findByRole('link', { name: 'INV-21' })).toHaveAttribute('href', '/invoices/21');
    expect(billBaseRent).toHaveBeenCalledWith(AgreementKind.Customer, agreement, { from: '2026-01-31', until: '2026-02-27' }, { invoice_date: '2026-02-27', due_date: null, exchange_rate: '1' });
});
it('surfaces conflicting billing without a success message', async () => {
    vi.mocked(billBaseRent).mockRejectedValue(new ApiError('Period already charged.', 409));
    setup(); documentFields();
    fireEvent.change(screen.getByLabelText('Charge through'), { target: { value: '2026-02-27' } });
    fireEvent.click(screen.getByRole('checkbox')); fireEvent.click(screen.getByRole('button', { name: 'Create invoice draft' }));
    expect(await screen.findByText('Period already charged.')).toBeInTheDocument();
    expect(screen.queryByRole('status')).not.toBeInTheDocument();
});
it('reissues a released charge without sending replacement amounts or periods', async () => {
    vi.mocked(loadBaseCharges).mockResolvedValue({ data: [charge], current_page: 1, last_page: 1 });
    vi.mocked(reissueBaseRent).mockResolvedValue({ id: 22, invoice_number: 'INV-22', grand_total: '3100.000000' });
    setup(); fireEvent.click(await screen.findByRole('button', { name: 'Reissue charge' })); documentFields();
    fireEvent.click(screen.getByRole('checkbox')); fireEvent.click(screen.getByRole('button', { name: 'Create invoice draft' }));
    await waitFor(() => expect(reissueBaseRent).toHaveBeenCalledWith(AgreementKind.Customer, agreement, charge, { invoice_date: '2026-02-27', due_date: null, exchange_rate: '1' }));
    expect(billBaseRent).not.toHaveBeenCalled();
});
it('voids with a reason and charge revision without requiring new invoice fields', async () => {
    vi.mocked(loadBaseCharges).mockResolvedValue({ data: [charge], current_page: 1, last_page: 1 });
    vi.mocked(voidBaseCharge).mockResolvedValue({} as never);
    setup(); fireEvent.click(await screen.findByRole('button', { name: 'Void charge' }));
    fireEvent.change(screen.getByLabelText('Void reason'), { target: { value: 'Correct period' } });
    fireEvent.click(screen.getByRole('checkbox'));
    const buttons = screen.getAllByRole('button', { name: 'Void charge' }); fireEvent.click(buttons[0]);
    await waitFor(() => expect(voidBaseCharge).toHaveBeenCalledWith(AgreementKind.Customer, agreement, charge, 'Correct period'));
    expect(billBaseRent).not.toHaveBeenCalled();
});
it('does not offer reissue or void for a live invoice', async () => {
    vi.mocked(loadBaseCharges).mockResolvedValue({ data: [{ ...charge, invoices: [{ id: 20, number: 'INV-20', status: 'draft' }] }], current_page: 1, last_page: 1 });
    setup(); await screen.findByRole('link', { name: 'INV-20' });
    expect(screen.queryByRole('button', { name: 'Reissue charge' })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Void charge' })).not.toBeInTheDocument();
});
