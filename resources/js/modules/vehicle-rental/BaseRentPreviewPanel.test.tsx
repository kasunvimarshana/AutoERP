import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { BaseRentPreviewPanel } from './BaseRentPreviewPanel';
import { BaseRentPolicy, previewBaseRent, type BaseRentPreview } from './baseRentPreviewApi';
import { AgreementKind, AgreementStatus, DriverMode, RentalBasis, TERM_LABELS, type Agreement, type TermKey } from './agreements';

vi.mock('./baseRentPreviewApi', async importOriginal => ({ ...await importOriginal<typeof import('./baseRentPreviewApi')>(), previewBaseRent: vi.fn() }));
const agreement: Agreement = {
    id: 7, reference: 'CUSTOMER-A', row_version: 2, status: AgreementStatus.Active, basis: RentalBasis.Monthly, driver_mode: DriverMode.SelfDrive,
    party: { id: 2, name: 'Customer A' }, currency: { id: 1, name: 'Rupee', code: 'LKR' }, agreed_on: '2026-01-01', executing_on: null,
    starts_on: '2026-01-31', ends_on: null, notes: null,
    terms: { ...Object.fromEntries(Object.keys(TERM_LABELS).map(key => [key, null])) as Record<TermKey, string | null>, base_rate: '3100.000000' },
};
const result: BaseRentPreview = {
    agreement: { id: 7, reference: 'CUSTOMER-A', version: 2, kind: AgreementKind.Customer }, currency: 'LKR', policy: BaseRentPolicy.ActualCalendarDays,
    from: '2026-01-31', until: '2026-02-27', base_rent: '3100.000000', rate: '3100.000000',
    segments: [{ from: '2026-01-31', until: '2026-02-27', cycle_from: '2026-01-31', cycle_until: '2026-02-27', days: 28, denominator_days: 28, amount: '3100.000000' }],
};
beforeEach(() => { vi.clearAllMocks(); vi.mocked(previewBaseRent).mockResolvedValue(result); });
function setup(record = agreement) {
    const view = render(<BaseRentPreviewPanel kind={AgreementKind.Customer} agreement={record} />);
    fireEvent.click(screen.getByText('Estimate base rent'));
    fireEvent.change(screen.getByLabelText('Estimate through'), { target: { value: '2026-02-27' } });
    return view;
}
it('requests the selected period and agreement revision and shows the denominator', async () => {
    setup(); fireEvent.click(screen.getByRole('button', { name: 'Calculate base rent' }));
    expect(await screen.findByText('Base rent: LKR 3100.000000')).toBeInTheDocument();
    expect(previewBaseRent).toHaveBeenCalledWith(AgreementKind.Customer, agreement, '2026-01-31', '2026-02-27', expect.any(AbortSignal));
    expect(screen.getByText('28 (2026-01-31 – 2026-02-27)')).toBeInTheDocument();
    expect(screen.getByText(/does not include mileage/)).toBeInTheDocument();
    fireEvent.change(screen.getByLabelText('Estimate through'), { target: { value: '2026-02-26' } });
    expect(screen.queryByRole('region', { name: 'Base rent estimate' })).not.toBeInTheDocument();
});
it('shows stale-version errors without a result', async () => {
    vi.mocked(previewBaseRent).mockRejectedValue(new ApiError('Reload the agreement.', 409));
    setup(); fireEvent.click(screen.getByRole('button', { name: 'Calculate base rent' }));
    expect(await screen.findByText('Reload the agreement.')).toBeInTheDocument();
    expect(screen.queryByRole('region', { name: 'Base rent estimate' })).not.toBeInTheDocument();
});
it('distinguishes unknown and explicit zero rates', () => {
    const view = setup({ ...agreement, terms: { ...agreement.terms, base_rate: null } });
    expect(screen.getByRole('button', { name: 'Calculate base rent' })).toBeDisabled();
    view.rerender(<BaseRentPreviewPanel kind={AgreementKind.Customer} agreement={{ ...agreement, terms: { ...agreement.terms, base_rate: '0.000000' } }} />);
    expect(screen.getByRole('button', { name: 'Calculate base rent' })).not.toBeDisabled();
});
it('cancels an in-flight preview when the agreement panel unmounts', async () => {
    let resolve!: (value: BaseRentPreview) => void;
    vi.mocked(previewBaseRent).mockReturnValue(new Promise(done => { resolve = done; }));
    const view = setup(); fireEvent.click(screen.getByRole('button', { name: 'Calculate base rent' }));
    await waitFor(() => expect(previewBaseRent).toHaveBeenCalled());
    const signal = vi.mocked(previewBaseRent).mock.calls[0][4];
    view.unmount(); expect(signal?.aborted).toBe(true); resolve(result);
});
