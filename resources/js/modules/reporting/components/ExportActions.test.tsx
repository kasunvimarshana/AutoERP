import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ExportActions } from './ExportActions';

const mocks = vi.hoisted(() => ({ exportReport: vi.fn(), allowed: true }));
vi.mock('../reportingApi', () => ({ exportReport: mocks.exportReport }));
vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => ({}) }));
vi.mock('@/modules/auth/accessControl', () => ({ hasPermission: () => mocks.allowed }));

describe('Compact report export', () => {
    beforeEach(() => { mocks.allowed = true; mocks.exportReport.mockReset(); mocks.exportReport.mockResolvedValue(undefined); });

    it('offers the chosen formats and sends the selected report section and filters', async () => {
        const params = { date_from: '2026-10-01', item_id: 42, export_section: 'stock' as const };
        const user = userEvent.setup();
        render(<ExportActions reportKey="vehicle-service/sales-summary" params={params} formats={['xlsx', 'pdf', 'csv']} compact />);
        await user.click(screen.getByText('Export'));
        expect(screen.getByRole('button', { name: 'Excel' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'CSV' })).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Print' })).not.toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'PDF' }));
        await waitFor(() => expect(mocks.exportReport).toHaveBeenCalledWith('vehicle-service/sales-summary', 'pdf', params, null));
    });

    it('hides the export menu without export permission', () => {
        mocks.allowed = false;
        render(<ExportActions reportKey="vehicle-service/sales-summary" params={{}} compact />);
        expect(screen.queryByText('Export')).not.toBeInTheDocument();
    });
});
