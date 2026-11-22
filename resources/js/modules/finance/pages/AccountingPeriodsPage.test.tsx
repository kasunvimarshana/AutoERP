/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import AccountingPeriodsPage from './AccountingPeriodsPage';

const apiMocks = vi.hoisted(() => ({
    listAccountingPeriods: vi.fn(),
    changeAccountingPeriodStatus: vi.fn(),
}));

vi.mock('../financeApi', () => apiMocks);
vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ roles: ['super admin'], permissions: [], permissionsLoaded: true }),
}));

describe('AccountingPeriodsPage', () => {
    const period = {
        id: 7,
        row_version: 3,
        code: 'FY26-P01',
        name: 'January 2026',
        start_date: '2026-01-01',
        end_date: '2026-01-31',
        status: 'closed',
        scope: 'current_branch',
    };

    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.listAccountingPeriods.mockResolvedValue({ data: [period] });
        apiMocks.changeAccountingPeriodStatus.mockResolvedValue({ ...period, row_version: 4, status: 'open' });
    });

    it('reopens a selected period with its current version and audit reason', async () => {
        const user = userEvent.setup();
        render(<TestRouter initialEntries={['/finance/accounting-periods']}><AccountingPeriodsPage /></TestRouter>);

        expect((await screen.findAllByText('FY26-P01 · January 2026')).length).toBeGreaterThan(0);
        await user.click(screen.getAllByRole('button', { name: 'Reopen' })[0]);
        await user.type(screen.getByLabelText('Reason'), 'Missed service jobs');
        await user.click(screen.getByRole('button', { name: 'Reopen period' }));

        await waitFor(() => expect(apiMocks.changeAccountingPeriodStatus).toHaveBeenCalledWith(
            period,
            'reopen',
            'Missed service jobs',
        ));
        expect(apiMocks.listAccountingPeriods).toHaveBeenCalledWith(1, expect.any(AbortSignal));
    });
});
