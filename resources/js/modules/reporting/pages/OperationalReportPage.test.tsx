import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import OperationalReportPage from './OperationalReportPage';

const apiMocks = vi.hoisted(() => ({
    runOperationalReport: vi.fn(),
}));

vi.mock('../reportingApi', () => apiMocks);

vi.mock('../components/ExportActions', () => ({
    ExportActions: ({ reportKey }: { reportKey: string }) => <div data-testid="export-actions">{reportKey}</div>,
}));

vi.mock('@/modules/purchase/components/PurchaseLookups', () => ({
    SupplierLookupSelect: () => <div data-testid="supplier-lookup" />,
    ItemLookupSelect: () => <div data-testid="item-lookup" />,
}));

vi.mock('@/shared/components/LookupSelect', () => ({ LookupSelect: () => null }));
vi.mock('@/shared/components/GenericLookupSelect', () => ({ GenericLookupSelect: () => null }));
vi.mock('@/modules/vehicle/components/VehicleLookupSelect', () => ({ VehicleLookupSelect: () => null }));
vi.mock('@/modules/hr/components/HrDepartmentSelect', () => ({ HrDepartmentSelect: () => null }));

describe('OperationalReportPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.runOperationalReport.mockResolvedValue({
            report: {
                key: 'purchase/detailed',
                title: 'Detailed Purchase Report',
                group: 'Purchase',
                description: 'Line-level purchase details.',
                columns: [
                    { key: 'purchase_order_number', label: 'PO number', sortable: true, format: 'text' },
                    { key: 'line_total', label: 'Line total', sortable: true, format: 'money' },
                ],
                filters: [],
                supports_date_range: true,
                default_sort: 'purchase_order_date',
                default_direction: 'desc',
            },
            data: [
                { id: 1, purchase_order_number: 'PO-1001', line_total: '123456789012345.500000' },
            ],
            summary: {
                total_orders: 1,
                grand_total: '123456789012345.500000',
            },
            meta: {
                current_page: 1,
                from: 1,
                last_page: 1,
                per_page: 25,
                to: 1,
                total: 1,
            },
        });
    });

    it('loads the detailed Purchase report and applies server-side filters', async () => {
        const user = userEvent.setup();
        render(
            <MemoryRouter>
                <OperationalReportPage reportKey="purchase/detailed" kind="purchase" />
            </MemoryRouter>,
        );

        expect(await screen.findByRole('heading', { name: 'Detailed Purchase Report' })).toBeInTheDocument();
        expect(screen.getAllByText('PO-1001').length).toBeGreaterThan(0);
        expect(screen.getAllByText('123,456,789,012,345.50').length).toBeGreaterThan(0);
        expect(screen.getByTestId('export-actions')).toHaveTextContent('purchase/detailed');
        expect(apiMocks.runOperationalReport).toHaveBeenCalledWith(
            'purchase/detailed',
            { page: 1, per_page: 25 },
            expect.any(AbortSignal),
        );

        await user.type(screen.getByLabelText('Search'), 'brake');
        await user.click(screen.getByRole('button', { name: 'Apply filters' }));

        await waitFor(() => {
            expect(apiMocks.runOperationalReport).toHaveBeenLastCalledWith(
                'purchase/detailed',
                expect.objectContaining({ page: 1, per_page: 25, search: 'brake' }),
                expect.any(AbortSignal),
            );
        });
    });
});
