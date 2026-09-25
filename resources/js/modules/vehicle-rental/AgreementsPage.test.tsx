import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import AgreementsPage from './AgreementsPage';
import { AgreementKind, AgreementStatus, RentalBasis, DriverMode, TERM_LABELS, type Agreement, type TermKey } from './agreements';
import { createAgreementSuccessor, listAgreements, transitionAgreement, saveAgreement } from './agreementApi';

const session = vi.hoisted(() => ({ roles: [] as string[], permissions: [] as string[], permissionsLoaded: true }));
vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => session }));
vi.mock('./agreementApi', () => ({ listAgreements: vi.fn(), transitionAgreement: vi.fn(), saveAgreement: vi.fn(), createAgreementSuccessor: vi.fn() }));
const record: Agreement = {
    id: 103, reference: 'LESSEE-AGREEMENT', row_version: 7, status: AgreementStatus.Draft,
    basis: RentalBasis.Monthly, driver_mode: DriverMode.SelfDrive, supersedes_agreement: null,
    party: { id: 90, name: 'Example customer' }, currency: { id: 2, name: 'Rupee', code: 'LKR' },
    agreed_on: '2026-09-01', executing_on: null, starts_on: '2026-09-07', ends_on: null, effective_coverage_ends_on: null, notes: null,
    terms: Object.fromEntries(Object.keys(TERM_LABELS).map(key => [key, null])) as Record<TermKey, string | null>,
};
beforeEach(() => {
    vi.clearAllMocks();
    session.permissions = ['vehicle-rental.customer-agreements.view'];
    vi.mocked(listAgreements).mockResolvedValue({ data: [record] });
});
describe('Rental agreement review', () => {
    it('shows business labels and hides writes for a view-only user', async () => {
        render(<AgreementsPage kind={AgreementKind.Customer} />);
        fireEvent.click(await screen.findByRole('button', { name: 'Review LESSEE-AGREEMENT' }));
        expect(screen.getByRole('heading', { name: /LESSEE-AGREEMENT · Example customer/ })).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'New agreement' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Edit draft' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Activate' })).not.toBeInTheDocument();
        expect(screen.getAllByText('Not specified')).toHaveLength(Object.keys(TERM_LABELS).length);
    });
    it('shows lifecycle coverage instead of calling a closed open-ended agreement open-ended', async () => {
        vi.mocked(listAgreements).mockResolvedValue({ data: [{ ...record, status: AgreementStatus.Closed, effective_coverage_ends_on: '2026-09-20' }] });
        render(<AgreementsPage kind={AgreementKind.Customer} />);
        expect(await screen.findByText('2026-09-07 – 2026-09-20')).toBeInTheDocument();
        expect(screen.queryByText('2026-09-07 – Open-ended')).not.toBeInTheDocument();
    });
    it('keeps review visible after a stale version is rejected and allows explicit reload', async () => {
        session.permissions.push('vehicle-rental.customer-agreements.manage');
        vi.mocked(transitionAgreement).mockRejectedValue(new ApiError('This agreement changed. Reload it before continuing.', 409));
        render(<AgreementsPage kind={AgreementKind.Customer} />);
        fireEvent.click(await screen.findByRole('button', { name: 'Review LESSEE-AGREEMENT' }));
        fireEvent.click(screen.getByRole('button', { name: 'Activate' }));
        fireEvent.click(screen.getByRole('button', { name: 'Confirm activation' }));
        expect(await screen.findByText('This agreement changed. Reload it before continuing.')).toBeInTheDocument();
        expect(screen.getByRole('region', { name: 'Agreement review' })).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Reload' }));
        await waitFor(() => expect(listAgreements).toHaveBeenCalledTimes(2));
        expect(screen.queryByRole('region', { name: 'Agreement review' })).not.toBeInTheDocument();
    });
    it('keeps the executing date independent in draft input and submission', async () => {
        session.permissions.push('vehicle-rental.customer-agreements.manage');
        render(<AgreementsPage kind={AgreementKind.Customer} />);
        fireEvent.click(await screen.findByRole('button', { name: 'Review LESSEE-AGREEMENT' }));
        expect(screen.getByText('Agreement date: 2026-09-01 · Executing date: Not recorded')).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Edit draft' }));
        fireEvent.change(screen.getByLabelText('Agreement executing date'), { target: { value: '2026-09-03' } });
        fireEvent.click(screen.getByRole('button', { name: 'Save draft' }));
        await waitFor(() => expect(saveAgreement).toHaveBeenCalledWith(AgreementKind.Customer, expect.objectContaining({ executing_on: '2026-09-03', agreed_on: '2026-09-01', starts_on: '2026-09-07' }), record.id));
    });
    it('requires a closure reason before allowing confirmation', async () => {
        session.permissions.push('vehicle-rental.customer-agreements.manage');
        vi.mocked(listAgreements).mockResolvedValue({ data: [{ ...record, status: AgreementStatus.Active }] });
        render(<AgreementsPage kind={AgreementKind.Customer} />);
        fireEvent.click(await screen.findByRole('button', { name: 'Review LESSEE-AGREEMENT' }));
        fireEvent.click(screen.getByRole('button', { name: 'Close agreement' }));
        expect(screen.getByRole('button', { name: 'Confirm closure' })).toBeDisabled();
        fireEvent.change(screen.getByLabelText('Closure reason'), { target: { value: 'Contract ended' } });
        expect(screen.getByRole('button', { name: 'Confirm closure' })).toBeEnabled();
        expect(screen.queryByRole('button', { name: 'Edit draft' })).not.toBeInTheDocument();
    });
    it('creates a future successor from an active agreement without editing active terms', async () => {
        session.permissions.push('vehicle-rental.customer-agreements.manage');
        vi.mocked(listAgreements).mockResolvedValue({ data: [{ ...record, status: AgreementStatus.Active }] });
        vi.mocked(createAgreementSuccessor).mockResolvedValue({ ...record, id: 104, reference: 'LESSEE-R2', row_version: 1, supersedes_agreement: { id: record.id, reference: record.reference } });
        render(<AgreementsPage kind={AgreementKind.Customer} />);
        fireEvent.click(await screen.findByRole('button', { name: 'Review LESSEE-AGREEMENT' }));
        fireEvent.click(screen.getByRole('button', { name: 'Create successor' }));
        fireEvent.change(screen.getByLabelText('New agreement reference'), { target: { value: 'LESSEE-R2' } });
        fireEvent.change(screen.getByLabelText('Agreement date'), { target: { value: '2026-09-20' } });
        fireEvent.change(screen.getByLabelText('Effective start date'), { target: { value: '2026-10-01' } });
        fireEvent.change(screen.getByLabelText('Revision reason'), { target: { value: 'Future rate revision' } });
        fireEvent.click(screen.getByRole('button', { name: 'Create successor draft' }));
        await waitFor(() => expect(createAgreementSuccessor).toHaveBeenCalledWith(AgreementKind.Customer, expect.objectContaining({ id: record.id }), expect.objectContaining({ reference: 'LESSEE-R2', starts_on: '2026-10-01', reason: 'Future rate revision' })));
    });
});
