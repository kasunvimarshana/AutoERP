import { useEffect, useMemo, useRef, useState, type FormEvent } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import { addDecimal, percentageOfDecimal, subtractDecimal, sumDecimals } from '@/shared/utils/decimal';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import type { PurchaseOrder, PurchaseOrderPayload } from '../purchaseApi';
import {
    createPurchaseOrder,
    getPurchaseOrderCreateContext,
    getPurchaseSupplierContext,
    getPurchaseWarehouseLocations,
    updatePurchaseOrder,
} from '../purchaseApi';
import { decimalOr, todayDate } from '../purchaseFormUtils';
import { PurchaseHeaderAdjustmentEditor, type EditableHeaderAdjustment } from './PurchaseHeaderAdjustmentEditor';
import { PurchaseOrderLineEditor, previewLineAmounts, type EditablePurchaseLine } from './PurchaseOrderLineEditor';
import { PurchaseOrderSummaryPanel, type PurchaseTotals } from './PurchaseOrderSummaryPanel';
import { CurrencyLookupSelect, SupplierLookupSelect, WarehouseLocationLookupSelect, WarehouseLookupSelect } from './PurchaseLookups';
import { PurchaseDocumentShell, PurchasePageHeader } from './PurchaseDocumentShell';
import { PurchaseTabs, type PurchaseTabItem } from './PurchaseTabs';

interface HeaderState {
    purchase_order_date: string;
    expected_delivery_date: string;
    exchange_rate: string;
    notes: string;
}

const tabs: PurchaseTabItem[] = [
    { id: 'details', label: 'Order Details' },
    { id: 'lines', label: 'Lines' },
    { id: 'adjustments', label: 'Adjustments' },
];

function resourceOrNull(resource: NamedResource | null | undefined): NamedResource | null {
    return resource?.id ? resource : null;
}

function lineFromOrder(line: NonNullable<PurchaseOrder['lines']>[number]): EditablePurchaseLine {
    return {
        item: resourceOrNull(line.item),
        item_variant: resourceOrNull(line.item_variant),
        item_variant_id: line.item_variant_id ?? line.item_variant?.id ?? null,
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
        auto_price: false,
        auto_uom: false,
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
        finance_mapping_label: adjustment.finance_mapping
            ? [adjustment.finance_mapping.cost_treatment, adjustment.finance_mapping.tax_treatment].filter(Boolean).join(' / ')
            : undefined,
        cost_treatment: adjustment.cost_treatment ?? undefined,
        tax_treatment: adjustment.tax_treatment ?? undefined,
        mapping_source: (adjustment.mapping_source as 'catalogue' | 'override' | undefined) ?? 'catalogue',
        override_reason: adjustment.override_reason ?? '',
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
        grand_total: grand,
    };
}

export function PurchaseOrderForm({ order }: { order?: PurchaseOrder }) {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const requestedTab = searchParams.get('tab') ?? tabs[0].id;
    const activeTab = tabs.some((tab) => tab.id === requestedTab) ? requestedTab : tabs[0].id;
    const [header, setHeaderState] = useState<HeaderState>({
        purchase_order_date: order?.purchase_order_date ?? todayDate(),
        expected_delivery_date: order?.expected_delivery_date ?? '',
        exchange_rate: order?.exchange_rate ?? '1.000000',
        notes: order?.notes ?? '',
    });
    const [supplier, setSupplierState] = useState<NamedResource | null>(resourceOrNull(order?.supplier));
    const [warehouse, setWarehouseState] = useState<NamedResource | null>(resourceOrNull(order?.warehouse));
    const [warehouseLocation, setWarehouseLocationState] = useState<NamedResource | null>(resourceOrNull(order?.warehouse_location));
    const [currency, setCurrencyState] = useState<NamedResource | null>(resourceOrNull(order?.currency));
    const [baseCurrencyId, setBaseCurrencyId] = useState<number | null>(null);
    const [currencySource, setCurrencySource] = useState('');
    const [exchangeRateSource, setExchangeRateSource] = useState('');
    const [warehouseSource, setWarehouseSource] = useState('');
    const [locationSource, setLocationSource] = useState('');
    const [lines, setLinesState] = useState<EditablePurchaseLine[]>(order?.lines?.length ? order.lines.map(lineFromOrder) : []);
    const [adjustments, setAdjustmentsState] = useState<EditableHeaderAdjustment[]>(order?.adjustments?.length ? order.adjustments.map(adjustmentFromOrder) : []);
    const [submitting, setSubmitting] = useState(false);
    const [dirty, setDirty] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const totals = useMemo(() => calculatePreview(lines, adjustments), [lines, adjustments]);
    const errorFor = (field: string) => fieldError(error, field);
    const supplierTouched = useRef(Boolean(order?.supplier));
    const warehouseTouched = useRef(Boolean(order?.warehouse));
    const locationTouched = useRef(Boolean(order?.warehouse_location));
    const currencyTouched = useRef(Boolean(order?.currency));
    const exchangeTouched = useRef(Boolean(order?.exchange_rate));

    useUnsavedChanges(dirty && !submitting);

    const setHeader = (next: HeaderState) => {
        setDirty(true);
        setHeaderState(next);
    };
    const setSupplier = (next: NamedResource | null) => {
        setDirty(true);
        supplierTouched.current = true;
        setSupplierState(next);
    };
    const setWarehouse = (next: NamedResource | null) => {
        setDirty(true);
        warehouseTouched.current = true;
        locationTouched.current = false;
        setWarehouseState(next);
        setWarehouseLocationState(null);
        setLocationSource('');
    };
    const setWarehouseLocation = (next: NamedResource | null) => {
        setDirty(true);
        locationTouched.current = true;
        setWarehouseLocationState(next);
        setLocationSource(next ? 'manual' : '');
    };
    const setCurrency = (next: NamedResource | null) => {
        setDirty(true);
        currencyTouched.current = true;
        setCurrencyState(next);
        setCurrencySource(next ? 'manual' : '');
        if (next?.id && baseCurrencyId === next.id) {
            setHeaderState((current) => ({ ...current, exchange_rate: '1.000000' }));
            setExchangeRateSource('tenant_default');
        }
    };
    const setLines = (next: EditablePurchaseLine[]) => {
        setDirty(true);
        setLinesState(next);
    };
    const setAdjustments = (next: EditableHeaderAdjustment[]) => {
        setDirty(true);
        setAdjustmentsState(next);
    };

    useEffect(() => {
        if (order) return;

        const controller = new AbortController();
        void getPurchaseOrderCreateContext(controller.signal)
            .then((context) => {
                if (controller.signal.aborted) return;
                setBaseCurrencyId(context.exchange_rate_context.base_currency_id ?? null);
                if (!currencyTouched.current && context.defaults.currency) {
                    setCurrencyState(context.defaults.currency);
                    setCurrencySource(context.defaults.currency_source ?? 'tenant_default');
                }
                if (!exchangeTouched.current) {
                    setHeaderState((current) => ({
                        ...current,
                        purchase_order_date: context.defaults.purchase_order_date ?? current.purchase_order_date,
                        expected_delivery_date: context.defaults.expected_delivery_date ?? current.expected_delivery_date,
                        exchange_rate: context.defaults.exchange_rate ?? current.exchange_rate,
                    }));
                    setExchangeRateSource(context.defaults.exchange_rate_source ?? 'tenant_default');
                }
                if (!warehouseTouched.current && context.defaults.warehouse) {
                    setWarehouseState(context.defaults.warehouse);
                    setWarehouseSource(context.defaults.warehouse_source ?? 'organization_unit_default');
                }
                if (!locationTouched.current && context.defaults.warehouse_location) {
                    setWarehouseLocationState(context.defaults.warehouse_location);
                    setLocationSource(context.defaults.warehouse_location_source ?? 'warehouse_default');
                }
            })
            .catch(() => undefined);

        return () => controller.abort();
    }, [order]);

    useEffect(() => {
        if (!warehouse?.id || locationTouched.current) return;
        const controller = new AbortController();
        void getPurchaseWarehouseLocations(warehouse.id, controller.signal)
            .then((locations) => {
                if (controller.signal.aborted || locationTouched.current) return;
                const defaultLocation = locations.find((location) => Boolean((location as NamedResource & { is_default?: boolean }).is_default)) ?? locations[0] ?? null;
                setWarehouseLocationState(defaultLocation);
                setLocationSource(defaultLocation ? 'warehouse_default' : '');
            })
            .catch(() => undefined);

        return () => controller.abort();
    }, [warehouse?.id]);

    useEffect(() => {
        if (!supplier?.id || !supplierTouched.current) return;
        const controller = new AbortController();
        void getPurchaseSupplierContext(supplier.id, controller.signal)
            .then((context) => {
                if (controller.signal.aborted) return;
                if (!currencyTouched.current && context.currency) {
                    setCurrencyState(context.currency);
                    setCurrencySource(context.currency_source ?? 'supplier_default');
                    if (context.currency.id === baseCurrencyId && !exchangeTouched.current) {
                        setHeaderState((current) => ({ ...current, exchange_rate: '1.000000' }));
                        setExchangeRateSource('tenant_default');
                    }
                }
            })
            .catch(() => undefined);

        return () => controller.abort();
    }, [supplier?.id, baseCurrencyId]);

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
            item_variant_id: line.item_variant_id ?? line.item_variant?.id ?? undefined,
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
            cost_treatment: adjustment.cost_treatment,
            tax_treatment: adjustment.tax_treatment,
            mapping_source: adjustment.mapping_source,
            override_reason: adjustment.override_reason || undefined,
            sort_order: index,
            description: adjustment.description || undefined,
        })),
    });

    const submit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (submitting) return;
        setSubmitting(true);
        setError(null);
        try {
            const saved = order ? await updatePurchaseOrder(order.id, payload()) : await createPurchaseOrder(payload());
            setDirty(false);
            navigate(`/purchase/orders/${saved.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    const tabItems = tabs.map((tab) => ({
        ...tab,
        count: tab.id === 'lines' ? lines.length : tab.id === 'adjustments' ? adjustments.length : undefined,
        error: tab.id === 'lines'
            ? lines.some((_, index) => Boolean(errorFor(`lines.${index}.item_id`) || errorFor(`lines.${index}.uom_id`)))
            : tab.id === 'adjustments'
                ? adjustments.some((_, index) => Boolean(errorFor(`adjustments.${index}.effect`) || errorFor(`adjustments.${index}.adjustment_type`)))
                : false,
    }));

    return (
        <form onSubmit={submit}>
            <PurchaseDocumentShell
                header={<PurchasePageHeader
                    title={order ? 'Edit Purchase Order' : 'Create Purchase Order'}
                    description="Prepare supplier, warehouse, line, and adjustment details before submitting the order."
                    status={<span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{order?.status ?? 'draft'}</span>}
                    actions={<>
                        <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                        <Button type="submit" loading={submitting}>{order ? 'Save Draft' : 'Save Draft'}</Button>
                    </>}
                />}
                tabs={<PurchaseTabs tabs={tabItems} activeTab={activeTab} />}
                summary={<PurchaseOrderSummaryPanel totals={totals} />}
            >
                <ErrorAlert error={error} />
                {activeTab === 'details' && (
                    <Panel title="Order Details">
                        <div className="grid gap-4 md:grid-cols-2">
                            <SupplierLookupSelect value={supplier} onChange={setSupplier} error={errorFor('supplier_id')} />
                            <CurrencyLookupSelect value={currency} onChange={setCurrency} error={errorFor('currency_id')} />
                            <WarehouseLookupSelect value={warehouse} onChange={setWarehouse} error={errorFor('warehouse_id')} />
                            <WarehouseLocationLookupSelect warehouseId={warehouse?.id ?? null} value={warehouseLocation} onChange={setWarehouseLocation} error={errorFor('warehouse_location_id')} />
                            <Input label="Order date" type="date" value={header.purchase_order_date} error={errorFor('purchase_order_date')} onChange={(event) => setHeader({ ...header, purchase_order_date: event.target.value })} />
                            <Input label="Expected delivery" type="date" value={header.expected_delivery_date} error={errorFor('expected_delivery_date')} onChange={(event) => setHeader({ ...header, expected_delivery_date: event.target.value })} />
                            <DecimalInput label="Exchange rate" value={header.exchange_rate} error={errorFor('exchange_rate')} onChange={(event) => { exchangeTouched.current = true; setExchangeRateSource('manual'); setHeader({ ...header, exchange_rate: event.target.value }); }} />
                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                                <div>Currency: {currencySource ? currencySource.replaceAll('_', ' ') : 'Manual'}</div>
                                <div>Exchange rate: {exchangeRateSource ? exchangeRateSource.replaceAll('_', ' ') : 'Manual required for foreign currency'}</div>
                                <div>Warehouse: {warehouseSource ? warehouseSource.replaceAll('_', ' ') : 'Manual'}</div>
                                <div>Location: {locationSource ? locationSource.replaceAll('_', ' ') : 'Manual'}</div>
                            </div>
                            <div className="md:col-span-2">
                                <Textarea label="Notes" value={header.notes} error={errorFor('notes')} onChange={(event) => setHeader({ ...header, notes: event.target.value })} />
                            </div>
                        </div>
                    </Panel>
                )}
                {activeTab === 'lines' && (
                    <Panel title="Lines">
                        <PurchaseOrderLineEditor
                            lines={lines}
                            onChange={setLines}
                            errorFor={errorFor}
                            supplierId={supplier?.id}
                            currencyId={currency?.id}
                            warehouseId={warehouse?.id}
                        />
                    </Panel>
                )}
                {activeTab === 'adjustments' && (
                    <Panel title="Adjustments">
                        <PurchaseHeaderAdjustmentEditor adjustments={adjustments} onChange={setAdjustments} errorFor={errorFor} />
                    </Panel>
                )}
            </PurchaseDocumentShell>
        </form>
    );
}
