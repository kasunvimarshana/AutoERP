import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import GrnPayablesReportPage from './GrnPayablesReportPage';

const apiMocks = vi.hoisted(() => ({
    runGrnPayablesReport: vi.fn(),
}));

vi.mock('../reportingApi', () => apiMocks);
vi.mock('../components/ExportActions', () => ({
    ExportActions: ({ reportKey }: { reportKey: string }) => <div data-testid="export-actions">{reportKey}</div>,
}));
vi.mock('@/modules/purchase/components/PurchaseLookups', () => ({
    SupplierLookupSelect: () => <div data-testid="supplier-lookup" />,
}));

describe('GrnPayablesReportPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.runGrnPayablesReport.mockResolvedValue({
            report: {
                key: 'purchase/grn-payables',
                title: 'GRN Payables & GRNI Report',
                group: 'Purchase',
                description: 'Posted GRNs reconciled to supplier exposure.',
                columns: [
                    { key: 'grn_number', label: 'GRN', sortable: true, format: 'text' },
                    { key: 'supplier', label: 'Supplier', sortable: true, format: 'text' },
                    { key: 'projected_exposure', label: 'Projected exposure', sortable: true, format: 'money' },
                ],
                filters: [],
                supports_date_range: true,
                default_sort: 'received_date',
                default_direction: 'desc',
            },
            data: [{ id: 1, grn_number: 'GRN-001', supplier: 'SUP-001 Parts Supplier', projected_exposure: '550' }],
            summary: {
                grn_count: 3,
                not_invoiced_count: 1,
                partially_invoiced_count: 1,
                invoiced_count: 1,
                open_exposure_count: 3,
                open_return_credit_count: 1,
                not_invoiced_amount: '500',
                partially_invoiced_amount: '500',
                invoiced_ap_outstanding: '75',
                receipt_total: '1800',
                linked_invoice_amount: '800',
                finalized_invoice_amount: '700',
                pending_invoice_amount: '100',
                uninvoiced_amount: '1000',
                settled_invoice_amount: '525',
                ap_outstanding: '175',
                return_amount: '50',
                open_return_credit: '50',
                pending_return_credit: '0',
                projected_exposure: '1125',
                grni_balance: '1040',
                accounting_liability: '1215',
            },
            suppliers: [{
                supplier: 'SUP-001 Parts Supplier',
                grn_count: 3,
                uninvoiced_amount: '1000',
                ap_outstanding: '175',
                open_return_credit: '50',
                projected_exposure: '1125',
                grni_balance: '1040',
            }],
            currency_code: 'LKR',
            period: { date_from: null, date_to: null },
            basis: {
                projected_exposure: 'Expected uninvoiced value plus AP outstanding.',
                accounting_liability: 'GRNI plus AP outstanding.',
                invoice_allocation: 'Shared invoices are allocated proportionally.',
                scope: 'Date filters apply to GRN receipt dates.',
            },
            meta: { current_page: 1, from: 1, last_page: 1, per_page: 25, to: 1, total: 1 },
        });
    });

    it('shows the complete exposure summary and applies controlled filters', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter>
                <GrnPayablesReportPage />
            </TestRouter>,
        );

        expect(await screen.findByRole('heading', { name: 'GRN Payables & GRNI' })).toBeInTheDocument();
        expect(screen.getAllByText(/1,125\.00/).length).toBeGreaterThan(0);
        expect(screen.getByText(/1,040\.00/)).toBeInTheDocument();
        expect(screen.getAllByText('SUP-001 Parts Supplier').length).toBeGreaterThan(0);
        expect(screen.getAllByText('GRN-001').length).toBeGreaterThan(0);
        expect(screen.getByTestId('export-actions')).toHaveTextContent('purchase/grn-payables');

        await user.type(screen.getByLabelText('Search GRN or supplier'), 'parts');
        await user.selectOptions(screen.getByLabelText('Invoice progress'), 'not_invoiced');
        await user.click(screen.getByRole('button', { name: 'Apply filters' }));

        await waitFor(() => {
            expect(apiMocks.runGrnPayablesReport).toHaveBeenLastCalledWith(
                expect.objectContaining({
                    page: 1,
                    per_page: 25,
                    search: 'parts',
                    invoice_progress: 'not_invoiced',
                }),
                expect.any(AbortSignal),
            );
        });
    });
});
