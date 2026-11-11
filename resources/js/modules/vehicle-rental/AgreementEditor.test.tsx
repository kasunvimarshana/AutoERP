import { fireEvent, render, screen } from '@testing-library/react';
import { expect, it, vi } from 'vitest';
import { AgreementEditor } from './AgreementEditor';
import { AgreementKind, AgreementStatus, DriverMode, RentalBasis, type Agreement } from './agreements';

vi.mock('./agreementApi', () => ({ saveAgreement: vi.fn() }));

it('avoids browser-native date clamping on agreement period controls', () => {
    render(<AgreementEditor kind={AgreementKind.Customer} onSaved={() => undefined} onCancel={() => undefined} />);

    const save = screen.getByRole('button', { name: 'Save draft' });
    expect(save).toBeDisabled();

    fireEvent.change(screen.getByLabelText('Start date'), { target: { value: '2026-09-07' } });
    expect(screen.getByLabelText('End date')).not.toHaveAttribute('min');
});

it('locks the counterparty lookup when editing a successor draft', () => {
    const successorRecord = {
        id: 104,
        reference: 'AGR-SUCC',
        row_version: 1,
        status: AgreementStatus.Draft,
        basis: RentalBasis.Monthly,
        driver_mode: DriverMode.SelfDrive,
        supersedes_agreement: { id: 103, reference: 'AGR-PREV' },
        party: { id: 90, name: 'Original Customer' },
        currency: { id: 2, name: 'Rupee', code: 'LKR' },
        agreed_on: '2026-09-01',
        executing_on: null,
        starts_on: '2026-10-01',
        ends_on: null,
        terms: {},
        notes: null,
    } as unknown as Agreement;

    render(<AgreementEditor kind={AgreementKind.Customer} record={successorRecord} onSaved={() => undefined} onCancel={() => undefined} />);
    expect(screen.getByRole('combobox', { name: 'Customer' })).toBeDisabled();
    expect(screen.getByText('Successor agreements inherit the counterparty of the predecessor agreement.')).toBeInTheDocument();
});

it('provides guidance that agreement reference can be left blank for automatic generation', () => {
    render(<AgreementEditor kind={AgreementKind.Customer} onSaved={() => undefined} onCancel={() => undefined} />);

    const referenceInput = screen.getByLabelText('Agreement reference');
    expect(referenceInput).not.toHaveAttribute('required');
    expect(screen.getByText('Leave blank to automatically generate a unique contract reference')).toBeInTheDocument();
});

it('disables save when reference is cleared while editing an existing agreement', () => {
    const existing = {
        id: 1,
        reference: 'AGR-INIT',
        row_version: 1,
        status: AgreementStatus.Draft,
        basis: RentalBasis.Monthly,
        driver_mode: DriverMode.SelfDrive,
        supersedes_agreement: null,
        party: { id: 90, name: 'Original Customer' },
        currency: { id: 2, name: 'Rupee', code: 'LKR' },
        agreed_on: '2026-09-01',
        executing_on: null,
        starts_on: '2026-10-01',
        ends_on: null,
        terms: {},
        notes: null,
    } as unknown as Agreement;

    render(<AgreementEditor kind={AgreementKind.Customer} record={existing} onSaved={() => undefined} onCancel={() => undefined} />);
    expect(screen.getByRole('button', { name: 'Save draft' })).toBeEnabled();
    fireEvent.change(screen.getByLabelText('Agreement reference'), { target: { value: '' } });
    expect(screen.getByRole('button', { name: 'Save draft' })).toBeDisabled();
});
