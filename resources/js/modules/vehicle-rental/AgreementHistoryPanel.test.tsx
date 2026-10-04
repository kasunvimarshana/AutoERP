import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { formatMoney } from '@/shared/utils/formatMoney';
import { agreementHistory } from './agreementApi';
import { AgreementHistoryPanel } from './AgreementHistoryPanel';
import { AgreementKind, AgreementStatus, DriverMode, RentalBasis, TERM_LABELS, type TermKey } from './agreements';

vi.mock('./agreementApi', () => ({ agreementHistory: vi.fn() }));

const terms = {
    ...Object.fromEntries(Object.keys(TERM_LABELS).map(key => [key, null])) as Record<TermKey, string | null>,
    base_rate: '3100.000000',
    included_km: '100.000000',
    deposit_requirement: '999.000000',
};

beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(agreementHistory).mockResolvedValue({
        data: [{
            version: 2,
            action: 'activate',
            reason: 'Approved revision',
            recorded_at: '2026-10-01T10:00:00+05:30',
            actor: { name: 'Rental Admin' },
            reference: 'OWNER-A',
            party_name: 'Example Owner',
            currency_code: 'LKR',
            status: AgreementStatus.Active,
            agreed_on: '2026-09-01',
            executing_on: null,
            starts_on: '2026-09-07',
            ends_on: null,
            basis: RentalBasis.Monthly,
            driver_mode: DriverMode.WithDriver,
            terms,
            notes: 'Signed owner agreement',
        }],
    });
});

it('shows complete owner history with owner labels and hides customer-only deposit terms', async () => {
    render(<AgreementHistoryPanel kind={AgreementKind.Owner} id={7} />);
    expect(await screen.findByText(/Revision 2 · Activate · Rental Admin/)).toBeInTheDocument();
    expect(screen.getByText('Active')).toBeInTheDocument();
    expect(screen.getByText('Owner base rental payable')).toBeInTheDocument();
    expect(screen.queryByText('Agreed security deposit')).not.toBeInTheDocument();
    expect(screen.getByText(formatMoney('3100.000000', 'LKR'))).toBeInTheDocument();
    expect(screen.getByText('100 km')).toBeInTheDocument();
    expect(screen.getByText(/Signed owner agreement/)).toBeInTheDocument();
    expect(screen.queryByText(/3100\.000000/)).not.toBeInTheDocument();
});

it('shows an explicit loading state', () => {
    vi.mocked(agreementHistory).mockReturnValue(new Promise<never>(() => undefined));
    render(<AgreementHistoryPanel kind={AgreementKind.Customer} id={7} />);
    expect(screen.getByRole('status')).toHaveTextContent('Loading agreement history…');
});

it('lets the operator retry a failed agreement-history request', async () => {
    vi.mocked(agreementHistory).mockRejectedValueOnce(new ApiError('Agreement history unavailable.', 500));
    render(<AgreementHistoryPanel kind={AgreementKind.Owner} id={7} />);
    expect(await screen.findByText('Agreement history unavailable.')).toBeInTheDocument();
    fireEvent.click(screen.getByRole('button', { name: 'Retry agreement history' }));
    expect(await screen.findByText(/Revision 2 · Activate · Rental Admin/)).toBeInTheDocument();
});
