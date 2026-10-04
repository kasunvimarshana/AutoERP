import { fireEvent, render, screen } from '@testing-library/react';
import { expect, it, vi } from 'vitest';
import { AgreementEditor } from './AgreementEditor';
import { AgreementKind } from './agreements';

vi.mock('./agreementApi', () => ({ saveAgreement: vi.fn() }));

it('mirrors the backend agreement period rule in the date controls', () => {
    render(<AgreementEditor kind={AgreementKind.Customer} onSaved={() => undefined} onCancel={() => undefined} />);

    const save = screen.getByRole('button', { name: 'Save draft' });
    expect(save).toBeDisabled();

    fireEvent.change(screen.getByLabelText('Start date'), { target: { value: '2026-09-07' } });
    expect(screen.getByLabelText('End date')).toHaveAttribute('min', '2026-09-07');
});
