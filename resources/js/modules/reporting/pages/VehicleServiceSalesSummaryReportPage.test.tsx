import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import VehicleServiceSalesSummaryReportPage from './VehicleServiceSalesSummaryReportPage';

const mocks = vi.hoisted(() => ({ run: vi.fn(), exports: vi.fn() }));
vi.mock('../reportingApi', () => ({ runVehicleServiceSalesSummaryReport: mocks.run }));
vi.mock('../components/ExportActions', () => ({ ExportActions: (props: { params: { export_section?: string } }) => { mocks.exports(props); return <div>Export {props.params.export_section ?? 'full'}</div>; } }));
vi.mock('@/modules/item/components/ItemLookupSelect', () => ({
    ItemLookupSelect: ({ onChange }: { onChange: (item: { id: number; name: string }) => void }) => <button type="button" onClick={() => onChange({ id: 42, name: 'Engine oil' })}>Choose oil</button>,
}));

describe('VehicleServiceSalesSummaryReportPage exports', () => {
    it('exports only applied date and item filters and hides export while loading', async () => {
        const empty = { item_count: 0, quantity: '0', revenue: '0', cost: '0', profit: '0', items: [] };
        mocks.run.mockResolvedValue({ jobs: [], summary: {
            job_count: 0, revenue: '0', stock_cost: '0', commission: '0', profit: '0', margin: '0', collected: '0',
            stock: empty, combo: { ...empty, component_stock_quantity: '0', component_cost: '0' }, other_service_revenue: '0',
        } });
        const user = userEvent.setup();
        render(<TestRouter><VehicleServiceSalesSummaryReportPage /></TestRouter>);
        await screen.findByText('Export full');
        expect(screen.getByText('Export stock')).toBeInTheDocument();
        expect(screen.getByText('Export combo')).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Choose oil' }));
        await user.clear(screen.getByLabelText('From'));
        await user.type(screen.getByLabelText('From'), '2026-10-01');
        expect(mocks.exports.mock.lastCall?.[0].params.item_id).toBeUndefined();
        await user.click(screen.getByRole('button', { name: 'Apply filters' }));
        await waitFor(() => expect(mocks.exports.mock.lastCall?.[0]).toMatchObject({
            reportKey: 'vehicle-service/sales-summary', params: { date_from: '2026-10-01', item_id: 42 },
        }));
        expect(mocks.exports.mock.calls.slice(-3).map(([props]) => props)).toMatchObject([
            { params: { date_from: '2026-10-01', item_id: 42 } },
            { params: { date_from: '2026-10-01', item_id: 42, export_section: 'stock' }, formats: ['xlsx', 'pdf', 'csv'], compact: true },
            { params: { date_from: '2026-10-01', item_id: 42, export_section: 'combo' }, formats: ['xlsx', 'pdf', 'csv'], compact: true },
        ]);
        mocks.run.mockReturnValue(new Promise(() => {}));
        await user.click(screen.getByRole('button', { name: 'Reset' }));
        expect(screen.queryByText('Export full')).not.toBeInTheDocument();
        expect(screen.queryByText('Export stock')).not.toBeInTheDocument();
        expect(screen.queryByText('Export combo')).not.toBeInTheDocument();
    });
});
