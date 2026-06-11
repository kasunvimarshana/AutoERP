import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    PurchaseHeaderAdjustmentEditor,
    type EditableHeaderAdjustment,
} from '@/modules/purchase/components/PurchaseHeaderAdjustmentEditor';
import {
    PurchaseOrderLineEditor,
    previewLineAmounts,
    type EditablePurchaseLine,
} from '@/modules/purchase/components/PurchaseOrderLineEditor';
import {
    PurchaseOrderSummaryPanel,
    type PurchaseTotals,
} from '@/modules/purchase/components/PurchaseOrderSummaryPanel';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { addDecimal, nonNegativeDecimal, percentageOfDecimal, subtractDecimal, sumDecimals } from '@/shared/utils/decimal';
import {
    createSalesOrder,
    createSalesQuotation,
    updateSalesOrder,
    updateSalesQuotation,
} from '../salesApi';
import type { SalesDocumentPayload, SalesOrder, SalesQuotation } from '../salesTypes';
import {
    CustomerLookupSelect,
    SalesCurrencyLookupSelect,
    SalesWarehouseLocationLookupSelect,
    SalesWarehouseLookupSelect,
} from './SalesLookups';

type DocumentKind = 'quotation' | 'order';
type SalesDocument = SalesQuotation | SalesOrder;

function today() {
    return new Date().toISOString().slice(0, 10);
}

function asResource(value: NamedResource | null | undefined): NamedResource | null {
    return value?.id ? value : null;
}

function lineFromDocument(line: NonNullable<SalesDocument['lines']>[number]): EditablePurchaseLine {
    return {
        item: asResource(line.item),
        uom: asResource(line.uom),
        description: line.description ?? '',
        ordered_quantity: line.quantity ?? line.ordered_quantity ?? '1.000000',
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

function adjustmentFromDocument(adjustment: NonNullable<SalesDocument['adjustments']>[number]): EditableHeaderAdjustment {
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

function totalsFor(lines: EditablePurchaseLine[], adjustments: EditableHeaderAdjustment[]): PurchaseTotals {
    const values = lines.map(previewLineAmounts);
    const subtotal = sumDecimals(values.map((line) => line.subtotal));
    const discount = sumDecimals(values.map((line) => line.discount));
    const tax = sumDecimals(values.map((line) => line.tax));
    const charge = sumDecimals(values.map((line) => line.charge));
    const afterDiscount = sumDecimals(values.map((line) => subtractDecimal(line.subtotal, line.discount)));
    const afterLines = sumDecimals(values.map((line) => line.total));
    const amounts = adjustments.map((adjustment) => {
        if (adjustment.calculation_type === 'fixed') return adjustment.amount;
        const basis = adjustment.calculation_base === 'subtotal'
            ? subtotal
            : adjustment.calculation_base === 'subtotal_after_line_discount'
                ? afterDiscount
                : afterLines;
        return percentageOfDecimal(basis, adjustment.rate);
    });
    const increases = sumDecimals(adjustments.flatMap((row, index) => row.effect === 'increase' ? [amounts[index]] : []));
    const decreases = sumDecimals(adjustments.flatMap((row, index) => row.effect === 'decrease' ? [amounts[index]] : []));

    return {
        subtotal,
        discount_total: discount,
        tax_total: tax,
        charge_total: charge,
        header_increase_total: increases,
        header_decrease_total: decreases,
        grand_total: nonNegativeDecimal(subtractDecimal(addDecimal(afterLines, increases), decreases)),
    };
}

export function SalesDocumentForm({ kind, document }: { kind: DocumentKind; document?: SalesDocument }) {
    const navigate = useNavigate();
    const order = kind === 'order' ? document as SalesOrder | undefined : undefined;
    const quotation = kind === 'quotation' ? document as SalesQuotation | undefined : undefined;
    const [documentDate, setDocumentDate] = useState(
        quotation?.quotation_date ?? order?.sales_order_date ?? today(),
    );
    const [secondaryDate, setSecondaryDate] = useState(
        quotation?.valid_until ?? order?.expected_delivery_date ?? '',
    );
    const [customer, setCustomer] = useState<NamedResource | null>(asResource(document?.customer));
    const [warehouse, setWarehouse] = useState<NamedResource | null>(asResource(order?.warehouse));
    const [location, setLocation] = useState<NamedResource | null>(asResource(order?.warehouse_location));
    const [currency, setCurrency] = useState<NamedResource | null>(asResource(document?.currency));
    const [exchangeRate, setExchangeRate] = useState(document?.exchange_rate ?? '1.000000');
    const [notes, setNotes] = useState(document?.notes ?? '');
    const [lines, setLines] = useState<EditablePurchaseLine[]>(
        document?.lines?.length ? document.lines.map(lineFromDocument) : [],
    );
    const [adjustments, setAdjustments] = useState<EditableHeaderAdjustment[]>(
        document?.adjustments?.length ? document.adjustments.map(adjustmentFromDocument) : [],
    );
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const totals = useMemo(() => totalsFor(lines, adjustments), [lines, adjustments]);
    const errorFor = (name: string) => fieldError(error, name);

    const payload = (): SalesDocumentPayload => ({
        ...(kind === 'quotation'
            ? { quotation_date: documentDate, valid_until: secondaryDate || undefined }
            : {
                sales_order_date: documentDate,
                expected_delivery_date: secondaryDate || undefined,
                warehouse_id: warehouse?.id,
                warehouse_location_id: location?.id,
            }),
        customer_id: customer?.id ?? 0,
        currency_id: currency?.id,
        exchange_rate: exchangeRate || '1.000000',
        notes: notes || undefined,
        lines: lines.map((line) => ({
            item_id: line.item?.id ?? 0,
            uom_id: line.uom?.id ?? 0,
            description: line.description || undefined,
            quantity: line.ordered_quantity,
            unit_price: line.unit_price,
            discount_calculation_type: line.discount_calculation_type,
            discount_rate: line.discount_rate,
            discount_amount: line.discount_amount,
            tax_calculation_type: line.tax_calculation_type,
            tax_rate: line.tax_rate,
            tax_amount: line.tax_amount,
            charge_calculation_type: line.charge_calculation_type,
            charge_rate: line.charge_rate,
            charge_amount: line.charge_amount,
        })),
        adjustments: adjustments.map((adjustment, index) => ({
            ...adjustment,
            is_allocatable: true,
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
                const saved = kind === 'quotation'
                    ? document
                        ? await updateSalesQuotation(document.id, payload())
                        : await createSalesQuotation(payload())
                    : document
                        ? await updateSalesOrder(document.id, payload())
                        : await createSalesOrder(payload());
                navigate(`/sales/${kind === 'quotation' ? 'quotations' : 'orders'}/${saved.id}`);
            } catch (requestError) {
                setError(toApiError(requestError));
            } finally {
                setSubmitting(false);
            }
        }}>
            <div className="space-y-5">
                <ErrorAlert error={error} />
                <Panel title={kind === 'quotation' ? 'Quotation header' : 'Order header'}>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <CustomerLookupSelect value={customer} onChange={setCustomer} error={errorFor('customer_id')} />
                        {kind === 'order' && (
                            <SalesWarehouseLookupSelect
                                value={warehouse}
                                onChange={(value) => { setWarehouse(value); setLocation(null); }}
                                error={errorFor('warehouse_id')}
                            />
                        )}
                        {kind === 'order' && (
                            <SalesWarehouseLocationLookupSelect
                                warehouseId={warehouse?.id}
                                value={location}
                                onChange={setLocation}
                                error={errorFor('warehouse_location_id')}
                            />
                        )}
                        <SalesCurrencyLookupSelect value={currency} onChange={setCurrency} error={errorFor('currency_id')} />
                        <Input label={kind === 'quotation' ? 'Quotation date' : 'Order date'} type="date" value={documentDate} error={errorFor(kind === 'quotation' ? 'quotation_date' : 'sales_order_date')} onChange={(event) => setDocumentDate(event.target.value)} />
                        <Input label={kind === 'quotation' ? 'Valid until' : 'Expected delivery'} type="date" value={secondaryDate} error={errorFor(kind === 'quotation' ? 'valid_until' : 'expected_delivery_date')} onChange={(event) => setSecondaryDate(event.target.value)} />
                        <DecimalInput label="Exchange rate" value={exchangeRate} error={errorFor('exchange_rate')} onChange={(event) => setExchangeRate(event.target.value)} />
                    </div>
                    <div className="mt-4">
                        <Textarea label="Notes" value={notes} error={errorFor('notes')} onChange={(event) => setNotes(event.target.value)} />
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
                    <Button type="submit" loading={submitting}>{document ? 'Save changes' : `Create ${kind}`}</Button>
                </div>
            </div>
            <div className="xl:sticky xl:top-20 xl:self-start">
                <PurchaseOrderSummaryPanel totals={totals} />
            </div>
        </form>
    );
}
