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
    it('shows item discounts and linked jobs inside both performance tables', async () => {
        const user = userEvent.setup();
        const stock = {
            item: { id: 4, code: 'OIL', name: 'Engine oil' }, quantity: '2', job_count: 1,
            discount_amount: '30', sales_amount: '170', cost: '40', profit: '130', margin: '76.47',
            jobs: [{ id: 19, number: 'VSJ-000019', quantity: '2', discount_amount: '30', sales_amount: '170' }],
        };
        const combo = {
            item: { id: 5, code: 'WASH', name: 'Wash package' }, quantity: '1', job_count: 1,
            discount_amount: '20', sales_amount: '280', cost: '10', stock_cost: '0', profit: '270', margin: '96.42',
            jobs: [{ id: 20, number: 'VSJ-000020', quantity: '1', discount_amount: '20', sales_amount: '280' }],
            components: [{ item: { id: 6, code: 'SOAP', name: 'Soap' }, kind: 'stock', quantity: '1', cost: '10' }],
        };
        mocks.run.mockResolvedValue({
            jobs: [], receipts: [],
            summary: {
                job_count: 2, revenue: '450', stock_cost: '40', commission: '0', profit: '400', margin: '88.88', collected: '0',
                by_job_type: [
                    { type: 'full_service', label: 'Full Service', job_count: 1, revenue: '300' },
                    { type: 'body_wash', label: 'Body Wash', job_count: 1, revenue: '150' },
                ],
                stock: { item_count: 1, quantity: '2', discount_amount: '30', revenue: '170', cost: '40', profit: '130', items: [stock] },
                combo: { combo_count: 1, item_count: 1, quantity: '1', discount_amount: '20', component_stock_quantity: '1', revenue: '280', stock_cost: '0', component_cost: '10', profit: '270', items: [combo] },
                other_service_revenue: '0',
            },
        });

        render(<TestRouter><VehicleServiceSalesSummaryReportPage /></TestRouter>);
        expect(await screen.findByRole('button', { name: 'Show jobs for Engine oil' })).toBeInTheDocument();
        expect(screen.getByRole('img', { name: /Full Service LKR 300.*Body Wash LKR 150/ })).toBeInTheDocument();
        expect(screen.getByText('66.7%')).toBeInTheDocument();
        expect(screen.getByText('33.3%')).toBeInTheDocument();
        expect(screen.getAllByRole('columnheader', { name: 'Discount' })).toHaveLength(2);
        await user.click(screen.getByRole('button', { name: 'Show jobs for Engine oil' }));
        expect(screen.getByRole('link', { name: 'VSJ-000019' })).toHaveAttribute('href', '/vehicle-service/jobs/19');
        await user.click(screen.getByRole('button', { name: 'Show jobs for Wash package' }));
        expect(screen.getByRole('link', { name: 'VSJ-000020' })).toHaveAttribute('href', '/vehicle-service/jobs/20');
        expect(screen.getByText('Soap')).toBeInTheDocument();
    });

    it('shows a receipt from an older invoice in the payment period', async () => {
        const empty = { item_count: 0, quantity: '0', discount_amount: '0', revenue: '0', cost: '0', profit: '0', items: [] };
        mocks.run.mockResolvedValue({
            jobs: [],
            receipts: [{
                date: '2026-11-05', payment_number: 'RV-2026-000004',
                job_number: 'VSJ-000009', invoice_number: 'SERVICE-2026-000007',
                methods: ['Cash'], amount: '1900',
            }],
            summary: {
                job_count: 0, revenue: '0', discount_amount: '0', stock_cost: '0', commission: '0', profit: '0', margin: '0', collected: '1900',
                by_job_type: [],
                stock: empty, combo: { ...empty, component_stock_quantity: '0', component_cost: '0' }, other_service_revenue: '0',
            },
        });

        render(<TestRouter><VehicleServiceSalesSummaryReportPage /></TestRouter>);

        expect(await screen.findByText('RV-2026-000004')).toBeInTheDocument();
        expect(screen.getByText('VSJ-000009')).toBeInTheDocument();
        expect(screen.getByText('SERVICE-2026-000007')).toBeInTheDocument();
        expect(screen.getByText(/Payments received in period:/)).toBeInTheDocument();
        expect(screen.getByText('No invoiced service sales in this period.')).toBeInTheDocument();
    });

    it('exports only applied date and item filters and hides export while loading', async () => {
        const empty = { item_count: 0, quantity: '0', discount_amount: '0', revenue: '0', cost: '0', profit: '0', items: [] };
        mocks.run.mockResolvedValue({ jobs: [], receipts: [], summary: {
            job_count: 0, revenue: '0', discount_amount: '0', stock_cost: '0', commission: '0', profit: '0', margin: '0', collected: '0',
            by_job_type: [],
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
