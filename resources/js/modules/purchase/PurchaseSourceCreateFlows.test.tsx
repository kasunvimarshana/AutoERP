import { StrictMode, type ReactElement } from 'react';
import { act, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { Route, Routes, useLocation } from 'react-router-dom';
import { ApiError } from '@/shared/api/apiError';
import { TestRouter } from '@/test/TestRouter';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { NamedResource } from '@/shared/types/common';
import GoodsReceiptCreatePage from './pages/GoodsReceiptCreatePage';
import PurchaseInvoiceCreatePage from './pages/PurchaseInvoiceCreatePage';
import PurchaseReturnCreatePage from './pages/PurchaseReturnCreatePage';
import type { GoodsReceipt, GoodsReceiptLine, PurchaseOrder, PurchaseOrderLine, ReturnableLine } from './purchaseTypes';
const purchaseApiMocks = vi.hoisted(() => ({
    createGoodsReceipt: vi.fn(),
    createPurchaseInvoice: vi.fn(),
    createPurchaseReturn: vi.fn(),
    getGoodsReceipt: vi.fn(),
    getInvoiceableGoodsReceiptLines: vi.fn(),
    getInvoiceablePurchaseOrderLines: vi.fn(),
    getPurchaseOrder: vi.fn(),
    getReceivablePurchaseOrderLines: vi.fn(),
    getReturnableGoodsReceiptLines: vi.fn(),
    previewPurchaseInvoice: vi.fn(),
}));
vi.mock('./purchaseApi', () => purchaseApiMocks);
vi.mock('./components/PurchaseLookups', () => ({
    SupplierLookupSelect: ({ value, onChange }: LookupMockProps) => (
        <button type="button" onClick={() => onChange({ id: 12, name: 'Supplier B' })}>
            {value?.name ?? 'Select Supplier'}
        </button>
    ),
    CurrencyLookupSelect: ({ value, onChange }: LookupMockProps) => (
        <button type="button" onClick={() => onChange({ id: 6, code: 'EUR', name: 'EUR' })}>
            {value?.code ?? value?.name ?? 'Select Currency'}
        </button>
    ),
    GoodsReceiptLookupSelect: ({ value, onChange, excludeIds, eligibility }: LookupMockProps & { eligibility?: string }) => (
        <div>
            <span data-testid="grn-eligibility">{eligibility}</span>
            <span data-testid="grn-excluded">{excludeIds?.join(',') ?? ''}</span>
            <span>{value?.name ?? 'No GRN selected'}</span>
            <button type="button" onClick={() => onChange({ id: 77, code: 'GRN-77', name: 'GRN-77' })}>Select GRN 77</button>
            <button type="button" onClick={() => onChange({ id: 78, code: 'GRN-78', name: 'GRN-78' })}>Select GRN 78</button>
            <button type="button" onClick={() => onChange(null)}>Clear GRN</button>
        </div>
    ),
    PurchaseOrderLookupSelect: ({ value, onChange, excludeIds, eligibility }: LookupMockProps & { eligibility?: string }) => (
        <div>
            <span data-testid="po-eligibility">{eligibility}</span>
            <span data-testid="po-excluded">{excludeIds?.join(',') ?? ''}</span>
            <span>{value?.name ?? 'No PO selected'}</span>
            <button type="button" onClick={() => onChange({ id: 31, code: 'PO-31', name: 'PO-31' })}>Select PO 31</button>
            <button type="button" onClick={() => onChange({ id: 32, code: 'PO-32', name: 'PO-32' })}>Select PO 32</button>
            <button type="button" onClick={() => onChange(null)}>Clear PO</button>
        </div>
    ),
    WarehouseLocationLookupSelect: ({ value, onChange }: LookupMockProps) => (
        <button type="button" onClick={() => onChange({ id: 301, name: 'Alt Bin' })}>
            {value?.name ?? 'No location'}
        </button>
    ),
}));
interface LookupMockProps {
    value: NamedResource | null;
    onChange: (value: NamedResource | null) => void;
    excludeIds?: Array<number | string>;
}
describe('Purchase source create flows', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(window.confirm).mockReturnValue(true);
        purchaseApiMocks.getGoodsReceipt.mockResolvedValue(goodsReceipt(77));
        purchaseApiMocks.getInvoiceableGoodsReceiptLines.mockResolvedValue([goodsReceiptLine(501), goodsReceiptLine(501)]);
        purchaseApiMocks.getInvoiceablePurchaseOrderLines.mockResolvedValue([purchaseOrderLine(401)]);
        purchaseApiMocks.getPurchaseOrder.mockResolvedValue(purchaseOrder(31));
        purchaseApiMocks.getReceivablePurchaseOrderLines.mockResolvedValue([purchaseOrderLine(401), purchaseOrderLine(401)]);
        purchaseApiMocks.getReturnableGoodsReceiptLines.mockResolvedValue([returnableLine(601), returnableLine(601)]);
        purchaseApiMocks.createPurchaseReturn.mockResolvedValue({ id: 901 });
    });
    it('loads an invoice query GRN once in StrictMode and deduplicates source lines', async () => {
        renderInvoice('/purchase/invoices/create?goods_receipt_id=77&tab=details', true);
        await waitFor(() => expect(purchaseApiMocks.getGoodsReceipt).toHaveBeenCalledTimes(1));
        expect(purchaseApiMocks.getInvoiceableGoodsReceiptLines).toHaveBeenCalledTimes(1);
        await waitFor(() => expect(screen.getAllByText('Widget')).toHaveLength(1));
        expect(screen.getAllByRole('button', { name: 'Remove' })).toHaveLength(1);
        expect(screen.getByTestId('grn-excluded')).toHaveTextContent('77');
        expect(screen.getByTestId('location-search')).toHaveTextContent('tab=details');
        expect(screen.getByTestId('location-search')).not.toHaveTextContent('goods_receipt_id');
    });
    it('prevents rapid duplicate invoice source adds and allows intentional remove and re-add', async () => {
        const receipt = deferred<GoodsReceipt>();
        const lines = deferred<GoodsReceiptLine[]>();
        purchaseApiMocks.getGoodsReceipt.mockReturnValueOnce(receipt.promise);
        purchaseApiMocks.getInvoiceableGoodsReceiptLines.mockReturnValueOnce(lines.promise);
        renderInvoice('/purchase/invoices/create');
        fireEvent.click(screen.getByRole('button', { name: 'Select GRN 77' }));
        const addButton = screen.getByRole('button', { name: 'Add source' });
        fireEvent.click(addButton);
        fireEvent.click(addButton);
        expect(purchaseApiMocks.getGoodsReceipt).toHaveBeenCalledTimes(1);
        await act(async () => {
            receipt.resolve(goodsReceipt(77));
            lines.resolve([goodsReceiptLine(501)]);
            await receipt.promise;
            await lines.promise;
        });
        expect(await screen.findByText('Widget')).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Remove' }));
        await waitFor(() => expect(screen.queryByText('Widget')).not.toBeInTheDocument());
        purchaseApiMocks.getGoodsReceipt.mockResolvedValueOnce(goodsReceipt(77));
        purchaseApiMocks.getInvoiceableGoodsReceiptLines.mockResolvedValueOnce([goodsReceiptLine(501)]);
        fireEvent.click(screen.getByRole('button', { name: 'Select GRN 77' }));
        fireEvent.click(screen.getByRole('button', { name: 'Add source' }));
        expect(await screen.findByText('Widget')).toBeInTheDocument();
        expect(purchaseApiMocks.getGoodsReceipt).toHaveBeenCalledTimes(2);
        expect(screen.getAllByRole('button', { name: 'Remove' })).toHaveLength(1);
    });
    it('does not restore a removed invoice query source and ignores stale source responses after supplier change', async () => {
        renderInvoice('/purchase/invoices/create?goods_receipt_id=77');
        expect(await screen.findByText('Widget')).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Remove' }));
        await waitFor(() => expect(screen.queryByText('Widget')).not.toBeInTheDocument());
        expect(purchaseApiMocks.getGoodsReceipt).toHaveBeenCalledTimes(1);
        const receipt = deferred<GoodsReceipt>();
        const lines = deferred<GoodsReceiptLine[]>();
        purchaseApiMocks.getGoodsReceipt.mockReturnValueOnce(receipt.promise);
        purchaseApiMocks.getInvoiceableGoodsReceiptLines.mockReturnValueOnce(lines.promise);
        fireEvent.click(screen.getByRole('button', { name: 'Select GRN 77' }));
        fireEvent.click(screen.getByRole('button', { name: 'Add source' }));
        fireEvent.click(screen.getByRole('button', { name: 'Supplier A' }));
        await act(async () => {
            receipt.resolve(goodsReceipt(77));
            lines.resolve([goodsReceiptLine(501)]);
            await receipt.promise;
            await lines.promise;
        });
        expect(screen.queryByText('Widget')).not.toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Supplier B' })).toBeInTheDocument();
    });
    it('leaves invoice state untouched when source loading fails', async () => {
        purchaseApiMocks.getInvoiceableGoodsReceiptLines.mockRejectedValueOnce(new Error('No invoiceable lines'));
        renderInvoice('/purchase/invoices/create');
        fireEvent.click(screen.getByRole('button', { name: 'Select GRN 77' }));
        fireEvent.click(screen.getByRole('button', { name: 'Add source' }));
        expect(await screen.findByText('No invoiceable lines')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Remove' })).not.toBeInTheDocument();
        expect(screen.queryByText('Widget')).not.toBeInTheDocument();
    });
    it('loads a GRN query PO once, deduplicates lines, and does not reselect after clear', async () => {
        renderGoodsReceipt('/purchase/goods-receipts/create?purchase_order_id=31&tab=source', true);
        await waitFor(() => expect(purchaseApiMocks.getPurchaseOrder).toHaveBeenCalledTimes(1));
        expect(screen.getByTestId('po-eligibility')).toHaveTextContent('receivable');
        expect(await screen.findByText('PO-31 - Supplier A')).toBeInTheDocument();
        await waitFor(() => expect(screen.getAllByText('Widget')).toHaveLength(2));
        expect(screen.getByTestId('location-search')).toHaveTextContent('tab=source');
        expect(screen.getByTestId('location-search')).not.toHaveTextContent('purchase_order_id');
        fireEvent.click(screen.getByRole('button', { name: 'Clear PO' }));
        await waitFor(() => expect(screen.queryByText('PO-31 - Supplier A')).not.toBeInTheDocument());
        expect(purchaseApiMocks.getPurchaseOrder).toHaveBeenCalledTimes(1);
    });
    it('ignores stale GRN PO line responses after the source changes', async () => {
        const order31 = deferred<PurchaseOrder>();
        const lines31 = deferred<PurchaseOrderLine[]>();
        purchaseApiMocks.getPurchaseOrder.mockImplementation((id: number) => id === 31 ? order31.promise : Promise.resolve(purchaseOrder(32)));
        purchaseApiMocks.getReceivablePurchaseOrderLines.mockImplementation((id: number) => id === 31 ? lines31.promise : Promise.resolve([purchaseOrderLine(402, 'Gadget')]));
        renderGoodsReceipt('/purchase/goods-receipts/create?purchase_order_id=31');
        fireEvent.click(screen.getByRole('button', { name: 'Select PO 32' }));
        expect(await screen.findByText('PO-32 - Supplier A')).toBeInTheDocument();
        expect(screen.getAllByText('Gadget')).toHaveLength(2);
        await act(async () => {
            order31.resolve(purchaseOrder(31));
            lines31.resolve([purchaseOrderLine(401, 'Widget')]);
            await order31.promise;
            await lines31.promise;
        });
        expect(screen.queryByText('PO-31 - Supplier A')).not.toBeInTheDocument();
        expect(screen.queryByText('Widget')).not.toBeInTheDocument();
    });
    it('loads a Return query GRN once, deduplicates lines, and does not reselect after clear', async () => {
        renderReturn('/purchase/returns/create?goods_receipt_id=77', true);
        await waitFor(() => expect(purchaseApiMocks.getGoodsReceipt).toHaveBeenCalledTimes(1));
        expect(screen.getByTestId('grn-eligibility')).toHaveTextContent('returnable');
        expect(await screen.findByText('GRN-77')).toBeInTheDocument();
        await waitFor(() => expect(screen.getAllByText('Widget')).toHaveLength(2));
        fireEvent.click(screen.getByRole('button', { name: 'Clear GRN' }));
        await waitFor(() => expect(screen.queryByText('GRN-77')).not.toBeInTheDocument());
        expect(purchaseApiMocks.getGoodsReceipt).toHaveBeenCalledTimes(1);
    });
    it('ignores stale Return GRN responses after the source changes', async () => {
        const receipt77 = deferred<GoodsReceipt>();
        const lines77 = deferred<ReturnableLine[]>();
        purchaseApiMocks.getGoodsReceipt.mockImplementation((id: number) => id === 77 ? receipt77.promise : Promise.resolve(goodsReceipt(78)));
        purchaseApiMocks.getReturnableGoodsReceiptLines.mockImplementation((id: number) => id === 77 ? lines77.promise : Promise.resolve([returnableLine(602, 'Gadget')]));
        renderReturn('/purchase/returns/create?goods_receipt_id=77');
        fireEvent.click(screen.getByRole('button', { name: 'Select GRN 78' }));
        expect(await screen.findByText('GRN-78')).toBeInTheDocument();
        expect(screen.getAllByText('Gadget')).toHaveLength(2);
        await act(async () => {
            receipt77.resolve(goodsReceipt(77));
            lines77.resolve([returnableLine(601, 'Widget')]);
            await receipt77.promise;
            await lines77.promise;
        });
        expect(screen.queryByText('GRN-77')).not.toBeInTheDocument();
        expect(screen.queryByText('Widget')).not.toBeInTheDocument();
    });
    it('submits returnable GRN line ids from the backend returnable-line contract', async () => {
        renderReturn('/purchase/returns/create?goods_receipt_id=77');
        await waitFor(() => expect(screen.getAllByText('Widget')).toHaveLength(2));
        fireEvent.click(screen.getByRole('button', { name: 'Select All' }));
        fireEvent.click(screen.getByRole('button', { name: 'Return Selected' }));
        fireEvent.click(screen.getByRole('button', { name: 'Save Draft' }));
        await waitFor(() => expect(purchaseApiMocks.createPurchaseReturn).toHaveBeenCalledTimes(1));
        expect(purchaseApiMocks.createPurchaseReturn).toHaveBeenCalledWith(expect.objectContaining({
            return_type: 'referenced',
            source_id: 77,
            lines: [{
                source_line_type: 'goods_receipt_note_line',
                source_line_id: 601,
                returned_quantity: '5.000000',
                reason: undefined,
            }],
        }));
    });
    it('maps return validation errors to the selected visible GRN line', async () => {
        purchaseApiMocks.getReturnableGoodsReceiptLines.mockResolvedValueOnce([
            returnableLine(601, 'Widget'),
            returnableLine(602, 'Gadget'),
        ]);
        purchaseApiMocks.createPurchaseReturn.mockRejectedValueOnce(new ApiError(
            'Please correct the highlighted fields and try again.',
            422,
            null,
            null,
            { 'lines.0.returned_quantity': ['Return quantity must be greater than zero.'] },
        ));

        renderReturn('/purchase/returns/create?goods_receipt_id=77');
        await waitFor(() => expect(screen.getAllByText('Gadget')).toHaveLength(2));
        fireEvent.click(screen.getAllByRole('checkbox')[1]);
        fireEvent.click(screen.getByRole('button', { name: 'Save Draft' }));

        await waitFor(() => expect(purchaseApiMocks.createPurchaseReturn).toHaveBeenCalledTimes(1));
        expect(purchaseApiMocks.createPurchaseReturn).toHaveBeenCalledWith(expect.objectContaining({
            lines: [expect.objectContaining({
                source_line_id: 602,
                returned_quantity: '0.000000',
            })],
        }));

        const widgetCard = screen.getAllByText('Widget')[0].closest('article');
        const gadgetCard = screen.getAllByText('Gadget')[0].closest('article');
        expect(widgetCard).not.toBeNull();
        expect(gadgetCard).not.toBeNull();
        expect(within(gadgetCard as HTMLElement).getByText('Return quantity must be greater than zero.')).toBeInTheDocument();
        expect(within(widgetCard as HTMLElement).queryByText('Return quantity must be greater than zero.')).not.toBeInTheDocument();
    });
});
function renderInvoice(initialEntry: string, strict = false) {
    renderWithRoute(initialEntry, '/purchase/invoices/create', <PurchaseInvoiceCreatePage />, strict);
}
function renderGoodsReceipt(initialEntry: string, strict = false) {
    renderWithRoute(initialEntry, '/purchase/goods-receipts/create', <GoodsReceiptCreatePage />, strict);
}
function renderReturn(initialEntry: string, strict = false) {
    renderWithRoute(initialEntry, '/purchase/returns/create', <PurchaseReturnCreatePage />, strict);
}
function renderWithRoute(initialEntry: string, path: string, element: ReactElement, strict: boolean) {
    const route = (
        <TestRouter initialEntries={[initialEntry]}>
            <Routes>
                <Route path={path} element={<>{element}<LocationProbe /></>} />
            </Routes>
        </TestRouter>
    );
    render(strict ? <StrictMode>{route}</StrictMode> : route);
}
function LocationProbe() {
    const location = useLocation();
    return <span data-testid="location-search">{location.search}</span>;
}
function purchaseOrder(id: number): PurchaseOrder {
    return {
        id,
        purchase_order_number: `PO-${id}`,
        status: 'approved',
        supplier: { id: 11, name: 'Supplier A' },
        supplier_id: 11,
        warehouse: { id: 21, name: 'Main Warehouse' },
        warehouse_id: 21,
        warehouse_location: { id: 301, name: 'Main Bin' },
        warehouse_location_id: 301,
        currency: { id: 5, code: 'USD', name: 'USD' },
        currency_id: 5,
        exchange_rate: '1.000000',
        subtotal: '100.000000',
        discount_total: '0.000000',
        tax_total: '0.000000',
        charge_total: '0.000000',
        adjustment_total: '0.000000',
        grand_total: '100.000000',
        lines: [],
        adjustments: [],
    };
}
function purchaseOrderLine(id: number, itemName = 'Widget'): PurchaseOrderLine {
    return {
        id,
        item: { id: 101, name: itemName },
        uom: { id: 201, code: 'EA', name: 'Each' },
        ordered_quantity: '5.000000',
        received_quantity: '0.000000',
        invoiced_quantity: '0.000000',
        remaining_quantity: '5.000000',
        remaining_receivable_quantity: '5.000000',
        remaining_invoiceable_quantity: '5.000000',
        unit_price: '10.000000',
        discount_amount: '0.000000',
        tax_amount: '0.000000',
        charge_amount: '0.000000',
    };
}
function goodsReceipt(id: number): GoodsReceipt {
    return {
        id,
        grn_number: `GRN-${id}`,
        received_date: '2026-06-18',
        status: 'posted',
        workflow_status: 'posted',
        supplier: { id: 11, name: 'Supplier A' },
        warehouse: { id: 21, name: 'Main Warehouse' },
        warehouse_location: { id: 301, name: 'Main Bin' },
        subtotal: '100.000000',
        discount_total: '0.000000',
        tax_total: '0.000000',
        charge_total: '0.000000',
        grand_total: '100.000000',
        lines: [],
        adjustments: [],
    };
}
function goodsReceiptLine(id: number, itemName = 'Widget'): GoodsReceiptLine {
    return {
        id,
        purchase_order_line_id: 401,
        item: { id: 101, name: itemName },
        uom: { id: 201, code: 'EA', name: 'Each' },
        received_quantity: '5.000000',
        accepted_quantity: '5.000000',
        rejected_quantity: '0.000000',
        invoiced_quantity: '0.000000',
        remaining_quantity: '5.000000',
        remaining_invoiceable_quantity: '5.000000',
        remaining_returnable_quantity: '5.000000',
        unit_price: '10.000000',
    };
}
function returnableLine(id: number, itemName = 'Widget'): ReturnableLine {
    return {
        id,
        purchase_order_line_id: 401,
        item_id: 101,
        item: { id: 101, name: itemName },
        uom_id: 201,
        uom: { id: 201, code: 'EA', name: 'Each' },
        accepted_quantity: '5.000000',
        returned_quantity: '0.000000',
        remaining_returnable_quantity: '5.000000',
        can_return: true,
        block_reason: null,
        unit_price: '10.000000',
    };
}
function deferred<T>() {
    let resolve!: (value: T) => void;
    let reject!: (reason?: unknown) => void;
    const promise = new Promise<T>((promiseResolve, promiseReject) => {
        resolve = promiseResolve;
        reject = promiseReject;
    });
    return { promise, resolve, reject };
}
