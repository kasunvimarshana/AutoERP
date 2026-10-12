import { act, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { getCustomerCreditProfile, updateCustomerCreditProfile } from '../customerApi';
import type { CustomerCreditProfile } from '../customerTypes';
import CustomerCreditProfileTab from './CustomerCreditProfileTab';

vi.mock('../customerApi', () => ({
    getCustomerCreditProfile: vi.fn(),
    updateCustomerCreditProfile: vi.fn(),
}));

function profile(creditLimit: string): CustomerCreditProfile {
    return {
        credit_limit: creditLimit,
        warning_threshold_percent: '80.000000',
        credit_allowed: true,
        advance_allowed: true,
        allow_over_credit: false,
        allow_partial_payment: true,
        is_active: true,
    };
}

beforeEach(() => vi.clearAllMocks());

describe('Customer credit profile ownership', () => {
    it('does not apply a previous customer save response to the current customer', async () => {
        vi.mocked(getCustomerCreditProfile).mockImplementation(async id => profile(id === 1 ? '100' : '200'));
        let completeSave!: (value: CustomerCreditProfile) => void;
        vi.mocked(updateCustomerCreditProfile).mockReturnValue(new Promise(resolve => { completeSave = resolve; }));
        const { rerender } = render(<CustomerCreditProfileTab customerId={1} canManage />);
        expect(await screen.findByLabelText('Credit limit')).toHaveValue('100.00');
        fireEvent.click(screen.getByRole('button', { name: 'Save credit profile' }));
        await waitFor(() => expect(updateCustomerCreditProfile).toHaveBeenCalledWith(1, profile('100')));

        rerender(<CustomerCreditProfileTab customerId={2} canManage />);
        await waitFor(() => expect(screen.getByLabelText('Credit limit')).toHaveValue('200.00'));
        await act(async () => completeSave(profile('150')));

        expect(screen.getByLabelText('Credit limit')).toHaveValue('200.00');
        expect(screen.getByRole('button', { name: 'Save credit profile' })).toBeEnabled();
    });
});
