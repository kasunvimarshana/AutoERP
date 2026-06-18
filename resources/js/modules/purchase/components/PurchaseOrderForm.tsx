import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import { addDecimal, nonNegativeDecimal, percentageOfDecimal, subtractDecimal, sumDecimals } from '@/shared/utils/decimal';
import type { PurchaseOrder, PurchaseOrderPayload } from '../purchaseApi';
import { createPurchaseOrder, updatePurchaseOrder } from '../purchaseApi';
import { decimalOr, todayDate } from '../purchaseFormUtils';
import { PurchaseHeaderAdjustmentEditor, type EditableHeaderAdjustment } from './PurchaseHeaderAdjustmentEditor';
import { PurchaseOrderLineEditor, previewLineAmounts, type EditablePurchaseLine } from './PurchaseOrderLineEditor';
import { PurchaseOrderSummaryPanel, type PurchaseTotals } from './PurchaseOrderSummaryPanel';
import { CurrencyLookupSelect, SupplierLookupSelect, WarehouseLocationLookupSelect, WarehouseLookupSelect } from './PurchaseLookups';
import { getDefaultWarehouse, getDefaultWarehouseLocation } from '@/modules/warehouse/warehouseApi';

interface HeaderState {
    purchase_order_date: string;
    expected_delivery_date: string;
    exchange_rate: string;
    notes: string;
}

function resourceOrNull(resource: NamedResource | null | undefined): NamedResource | null {
    return resource?.id ? resource : null;
}

function lineFromOrder(line: NonNullable<PurchaseOrder['lines']>[number]): EditablePurchaseLine {
    return {
        item: resourceOrNull(line.item),
        uom: resourceOrNull(line.uom),
        description: line.description ?? '',
        ordered_quantity: line.ordered_quantity,
        unit_price: line.unit_price,
        discount_calculation_type: line.discount_calculation_type ?? 'fixed',
        discount_rate: line.discount_rate ?? '0.000000',
        discount_amount: line.discount_amount,
        tax_calculation_type: line.tax_calculation_type ?? 'fixed',
        tax_rate: line.tax_rate ?? '0.000000',
        tax_amount: line.tax_amount,
        charge_calculation_type: line.charge_calculation_type ?? 'fixed',
        charge_rate: line.charge_rate ?? '0.000000',
        charge_amount: line.charge_amount,
    };
}

function adjustmentFromOrder(adjustment: NonNullable<PurchaseOrder['adjustments']>[number]): EditableHeaderAdjustment {
    return {
        name: adjustment.name,
        adjustment_type: adjustment.adjustment_type,
        effect: adjustment.effect,
        calculation_type: adjustment.calculation_type,
        calculation_base: adjustment.calculation_base ?? 'subtotal',
        rate: adjustment.rate,
        amount: adjustment.amount,
        allocation_method: adjustment.allocation_method,
        description: adjustment.description ?? '',
    };
}

function calculatePreview(lines: EditablePurchaseLine[], adjustments: EditableHeaderAdjustment[]): PurchaseTotals {
    const lineAmounts = lines.map(previewLineAmounts);
    const subtotal = sumDecimals(lineAmounts.map((line) => line.subtotal));
    const discount = sumDecimals(lineAmounts.map((line) => line.discount));
    const tax = sumDecimals(lineAmounts.map((line) => line.tax));
    const charge = sumDecimals(lineAmounts.map((line) => line.charge));
    const subtotalAfterLineDiscount = sumDecimals(lineAmounts.map((line) => subtractDecimal(line.subtotal, line.discount)));
    const subtotalAfterLineAdjustments = sumDecimals(lineAmounts.map((line) => line.total));
    const adjustmentAmounts = adjustments.map((adjustment) => {
        if (adjustment.calculation_type === 'fixed') return adjustment.amount;
        const base = adjustment.calculation_base === 'subtotal'
            ? subtotal
            : adjustment.calculation_base === 'subtotal_after_line_discount'
                ? subtotalAfterLineDiscount
                : subtotalAfterLineAdjustments;
        return percentageOfDecimal(base, adjustment.rate);
    });
    const increases = sumDecimals(adjustments.flatMap((adjustment, index) => adjustment.effect === 'increase' ? [adjustmentAmounts[index]] : []));
    const decreases = sumDecimals(adjustments.flatMap((adjustment, index) => adjustment.effect === 'decrease' ? [adjustmentAmounts[index]] : []));
    const grand = subtractDecimal(addDecimal(subtotalAfterLineAdjustments, increases), decreases);

    return {
        subtotal,
        discount_total: discount,
        tax_total: tax,
        charge_total: charge,
        header_increase_total: increases,
        header_decrease_total: decreases,
        grand_total: nonNegativeDecimal(grand),
    };
}

export function PurchaseOrderForm({ order }: { order?: PurchaseOrder }) {
    const navigate = useNavigate();
    const [header, setHeader] = useState<HeaderState>({
        purchase_order_date: order?.purchase_order_date ?? todayDate(),
        expected_delivery_date: order?.expected_delivery_date ?? '',
        exchange_rate: order?.exchange_rate ?? '1.000000',
        notes: order?.notes ?? '',
    });
    const [supplier, setSupplier] = useState<NamedResource | null>(resourceOrNull(order?.supplier));
    const [warehouse, setWarehouse] = useState<NamedResource | null>(resourceOrNull(order?.warehouse));
    const [warehouseLocation, setWarehouseLocation] = useState<NamedResource | null>(resourceOrNull(order?.warehouse_location));
    const [currency, setCurrency] = useState<NamedResource | null>(resourceOrNull(order?.currency));
    const [lines, setLines] = useState<EditablePurchaseLine[]>(order?.lines?.length ? order.lines.map(lineFromOrder) : []);
    const [adjustments, setAdjustments] = useState<EditableHeaderAdjustment[]>(order?.adjustments?.length ? order.adjustments.map(adjustmentFromOrder) : []);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const totals = useMemo(() => calculatePreview(lines, adjustments), [lines, adjustments]);
    const errorFor = (field: string) => fieldError(error, field);
    const warehouseTouched = useRef(Boolean(order?.warehouse));
    const locationTouched = useRef(Boolean(order?.warehouse_location));

    useEffect(() => {
        if (order || warehouseTouched.current || warehouse || warehouseLocation) return;

        const controller = new AbortController();
        void getDefaultWarehouse(controller.signal)
            .then(async (defaultWarehouse) => {
                if (controller.signal.aborted || warehouseTouched.current || warehouse || !defaultWarehouse) return;
                setWarehouse(defaultWarehouse);

                if (locationTouched.current || warehouseLocation) return;
                const defaultLocation = await getDefaultWarehouseLocation(Number(defaultWarehouse.id), controller.signal);
                if (!controller.signal.aborted && !locationTouched.current && !warehouseLocation && defaultLocation) {
                    setWarehouseLocation(defaultLocation);
                }
            })
            .catch(() => undefined);

        return () => controller.abort();
    }, [order, warehouse, warehouseLocation]);

    const payload = (): PurchaseOrderPayload => ({
        purchase_order_date: header.purchase_order_date,
        supplier_type: 'supplier',
        supplier_id: supplier?.id ?? 0,
        warehouse_id: warehouse?.id ?? 0,
        warehouse_location_id: warehouseLocation?.id,
        currency_id: currency?.id,
        expected_delivery_date: header.expected_delivery_date || undefined,
        exchange_rate: decimalOr(header.exchange_rate, '1.000000'),
        notes: header.notes || undefined,
        lines: lines.map((line) => ({
            item_id: line.item?.id ?? 0,
            uom_id: line.uom?.id ?? 0,
            description: line.description || undefined,
            ordered_quantity: decimalOr(line.ordered_quantity),
            unit_price: decimalOr(line.unit_price),
            discount_calculation_type: line.discount_calculation_type,
            discount_rate: decimalOr(line.discount_rate),
            discount_amount: decimalOr(line.discount_amount),
            tax_calculation_type: line.tax_calculation_type,
            tax_rate: decimalOr(line.tax_rate),
            tax_amount: decimalOr(line.tax_amount),
            charge_calculation_type: line.charge_calculation_type,
            charge_rate: decimalOr(line.charge_rate),
            charge_amount: decimalOr(line.charge_amount),
        })),
        adjustments: adjustments.map((adjustment, index) => ({
            name: adjustment.name,
            adjustment_type: adjustment.adjustment_type,
            effect: adjustment.effect,
            calculation_type: adjustment.calculation_type,
            calculation_base: adjustment.calculation_base,
            rate: decimalOr(adjustment.rate),
            amount: decimalOr(adjustment.amount),
            allocation_method: adjustment.allocation_method,
            sort_order: index,
            description: adjustment.description || undefined,
        })),
    });

    return (
        <form className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_320px]" onSubmit={async (event) => {
            event.preventDefault();
            if (submitting) return;
            setSubmitting(true);
            setError(null);
            try {
                const saved = order ? await updatePurchaseOrder(order.id, payload()) : await createPurchaseOrder(payload());
                navigate(`/purchase/orders/${saved.id}`);
            } catch (requestError) {
                setError(toApiError(requestError));
            } finally {
                setSubmitting(false);
            }
        }}>
            <div className="space-y-5">
                <ErrorAlert error={error} />
                <Panel title="Order header">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <SupplierLookupSelect value={supplier} onChange={setSupplier} error={errorFor('supplier_id')} />
                        <WarehouseLookupSelect value={warehouse} onChange={(value) => { warehouseTouched.current = true; setWarehouse(value); setWarehouseLocation(null); }} error={errorFor('warehouse_id')} />
                        <WarehouseLocationLookupSelect warehouseId={warehouse?.id ?? null} value={warehouseLocation} onChange={(value) => { locationTouched.current = true; setWarehouseLocation(value); }} error={errorFor('warehouse_location_id')} />
                        <CurrencyLookupSelect value={currency} onChange={setCurrency} error={errorFor('currency_id')} />
                        <Input label="Order date" type="date" value={header.purchase_order_date} error={errorFor('purchase_order_date')} onChange={(event) => setHeader({ ...header, purchase_order_date: event.target.value })} />
                        <Input label="Expected delivery" type="date" value={header.expected_delivery_date} error={errorFor('expected_delivery_date')} onChange={(event) => setHeader({ ...header, expected_delivery_date: event.target.value })} />
                        <DecimalInput label="Exchange rate" value={header.exchange_rate} error={errorFor('exchange_rate')} onChange={(event) => setHeader({ ...header, exchange_rate: event.target.value })} />
                    </div>
                    <div className="mt-4">
                        <Textarea label="Notes" value={header.notes} error={errorFor('notes')} onChange={(event) => setHeader({ ...header, notes: event.target.value })} />
                    </div>
                </Panel>
                <Panel title="Lines">
                    <PurchaseOrderLineEditor lines={lines} onChange={setLines} errorFor={errorFor} />
                </Panel>
                <Panel title="Header adjustments">
                    <PurchaseHeaderAdjustmentEditor adjustments={adjustments} onChange={setAdjustments} errorFor={errorFor} />
                </Panel>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                    <Button type="submit" loading={submitting}>{order ? 'Save order' : 'Create order'}</Button>
                </div>
            </div>
            <div className="xl:sticky xl:top-20 xl:self-start">
                <PurchaseOrderSummaryPanel totals={totals} />
            </div>
        </form>
    );
}
