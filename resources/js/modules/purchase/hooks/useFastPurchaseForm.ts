import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { useApi } from '@/shared/hooks/useApi';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import type { NamedResource } from '@/shared/types/common';
import { isPositiveDecimal } from '@/shared/utils/decimal';
import {
    createFastPurchase,
    getFastPurchaseContext,
    getPurchaseSupplierContext,
    getPurchaseWarehouseLocations,
    previewFastPurchase,
    type FastPurchasePayload,
    type FastPurchaseResult,
} from '../purchaseApi';
import { todayDate } from '../purchaseFormUtils';
import { fastPurchaseLineToPayload } from '../components/purchaseLineAdapters';
import { blankPaymentMethodRow, paymentRowsTotal, type PurchasePaymentMethodRow } from '../components/PurchasePaymentMethodsEditor';
import type { FastPurchasePreset } from '../components/FastPurchaseSections';
import type { EditableHeaderAdjustment } from '../components/PurchaseHeaderAdjustmentEditor';
import type { FastPurchaseLineRow } from '../components/FastPurchaseLines';

interface UseFastPurchaseFormOptions {
    canPreviewPermission: boolean;
    canExecutePermission: boolean;
}

export function useFastPurchaseForm({ canPreviewPermission, canExecutePermission }: UseFastPurchaseFormOptions) {
    const context = useApi((signal) => getFastPurchaseContext(signal), []);
    const defaults = context.data?.defaults;

    const [supplier, setSupplierState] = useState<NamedResource | null>(null);
    const [purchaseDate, setPurchaseDateState] = useState(todayDate());
    const [warehouse, setWarehouseState] = useState<NamedResource | null>(null);
    const [warehouseLocation, setWarehouseLocationState] = useState<NamedResource | null>(null);
    const [currency, setCurrencyState] = useState<NamedResource | null>(null);
    const [exchangeRate, setExchangeRateState] = useState('1.000000');
    const [terms, setTerms] = useState('due_on_receipt');
    const [supplierReference, setSupplierReference] = useState('');
    const [notes, setNotes] = useState('');
    const [preset, setPreset] = useState<FastPurchasePreset>('purchase_receive_invoice');
    const [lines, setLines] = useState<FastPurchaseLineRow[]>([]);
    const [adjustments, setAdjustments] = useState<EditableHeaderAdjustment[]>([]);
    const [paymentRows, setPaymentRows] = useState<PurchasePaymentMethodRow[]>([blankPaymentMethodRow()]);
    const [preview, setPreview] = useState<FastPurchaseResult | null>(null);
    const [result, setResult] = useState<FastPurchaseResult | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [previewing, setPreviewing] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const purchaseDateTouched = useRef(false);
    const currencyTouched = useRef(false);
    const warehouseTouched = useRef(false);
    const locationTouched = useRef(false);
    const exchangeRateTouched = useRef(false);
    const lastPreviewKey = useRef<string | null>(null);
    const previewController = useRef<AbortController | null>(null);
    const submittedLineKeys = useRef<string[]>([]);
    const submittedPaymentKeys = useRef<string[]>([]);

    const receiveStock = preset !== 'expense_only';
    const createInvoice = preset !== 'purchase_receive';
    const recordPayment = preset === 'purchase_receive_invoice_pay';
    const paymentTotal = paymentRowsTotal(paymentRows);
    const currentError = error ?? context.error;
    const errorFor = useCallback((field: string) => fieldError(currentError, field), [currentError]);

    useEffect(() => {
        if (!defaults) return;

        if (!purchaseDateTouched.current) setPurchaseDateState(defaults.purchase_date ?? todayDate());
        if (!exchangeRateTouched.current) setExchangeRateState(defaults.exchange_rate ?? '1.000000');
        if (!currencyTouched.current && defaults.currency) setCurrencyState(defaults.currency);
        if (!warehouseTouched.current && defaults.warehouse) setWarehouseState(defaults.warehouse);
        if (!locationTouched.current && defaults.warehouse_location) setWarehouseLocationState(defaults.warehouse_location);
    }, [defaults]);

    useEffect(() => {
        if (!supplier?.id) return;

        const controller = new AbortController();
        void getPurchaseSupplierContext(supplier.id, controller.signal)
            .then((supplierContext) => {
                if (controller.signal.aborted) return;
                if (!currencyTouched.current && supplierContext.currency) {
                    setCurrencyState(supplierContext.currency);
                }
            })
            .catch(() => undefined);

        return () => controller.abort();
    }, [supplier?.id]);

    useEffect(() => {
        if (!warehouse?.id) return;

        const controller = new AbortController();
        void getPurchaseWarehouseLocations(warehouse.id, controller.signal)
            .then((locations) => {
                if (controller.signal.aborted || locationTouched.current) return;
                const defaultLocation = (locations as Array<NamedResource & { is_default?: boolean }>).find((location) => location.is_default) ?? null;
                setWarehouseLocationState(defaultLocation);
            })
            .catch(() => undefined);

        return () => controller.abort();
    }, [warehouse?.id]);

    const setSupplier = (next: NamedResource | null) => {
        if (supplier?.id && next?.id && supplier.id !== next.id && lines.length > 0) {
            const confirmed = window.confirm('Changing the supplier may refresh line UOM, price, and tax defaults.');
            if (!confirmed) return;
        }
        setSupplierState(next);
        setLines((current) => current.map(invalidatePricingContext));
    };

    const setWarehouse = (next: NamedResource | null) => {
        if (warehouse?.id === next?.id) return;
        warehouseTouched.current = true;
        locationTouched.current = false;
        setWarehouseState(next);
        setWarehouseLocationState(null);
    };

    const setWarehouseLocation = (next: NamedResource | null) => {
        locationTouched.current = true;
        setWarehouseLocationState(next);
    };

    const setCurrency = (next: NamedResource | null) => {
        currencyTouched.current = true;
        setCurrencyState(next);
        setLines((current) => current.map(invalidatePricingContext));
    };

    const setPurchaseDate = (next: string) => {
        purchaseDateTouched.current = true;
        setPurchaseDateState(next);
        setLines((current) => current.map(invalidatePricingContext));
    };

    const setExchangeRate = (next: string) => {
        exchangeRateTouched.current = true;
        setExchangeRateState(next);
    };

    const buildPayload = useCallback((paymentRowsOverride = paymentRows): FastPurchasePayload => ({
        supplier_id: supplier?.id ?? 0,
        supplier_reference: supplierReference.trim() || undefined,
        purchase_date: purchaseDate,
        warehouse_id: warehouse?.id,
        warehouse_location_id: warehouseLocation?.id,
        currency_id: currency?.id,
        exchange_rate: exchangeRate || undefined,
        payment_terms: terms || undefined,
        notes: notes || undefined,
        options: {
            receive_stock_now: receiveStock,
            create_supplier_invoice_now: createInvoice,
            record_payment_now: recordPayment,
        },
        lines: lines.map(fastPurchaseLineToPayload),
        adjustments: adjustments.map((adjustment) => ({
            name: adjustment.name,
            adjustment_type: adjustment.adjustment_type,
            effect: adjustment.effect,
            calculation_type: adjustment.calculation_type,
            calculation_base: adjustment.calculation_base,
            rate: adjustment.rate,
            amount: adjustment.amount,
            allocation_method: adjustment.allocation_method,
            cost_treatment: adjustment.cost_treatment,
            tax_treatment: adjustment.tax_treatment,
            mapping_source: adjustment.mapping_source,
            override_reason: adjustment.override_reason || undefined,
            allocations: adjustment.allocation_method === 'manual' ? adjustment.allocations ?? [] : undefined,
            description: adjustment.description || undefined,
        })),
        payment: recordPayment ? {
            amount: paymentRowsTotal(paymentRowsOverride),
            lines: paymentRowsOverride.map((row) => ({
                amount: row.amount || '0.000000',
                payment_method_id: row.payment_method_id ? Number(row.payment_method_id) : undefined,
                source_account_id: row.source_account_id ? Number(row.source_account_id) : undefined,
                reference: row.reference || undefined,
                instrument_number: row.instrument_number || undefined,
                instrument_date: row.instrument_date || undefined,
                external_bank_name: row.external_bank_name || undefined,
                external_bank_branch: row.external_bank_branch || undefined,
            })),
        } : undefined,
    }), [
        adjustments,
        createInvoice,
        currency?.id,
        exchangeRate,
        lines,
        notes,
        paymentRows,
        purchaseDate,
        receiveStock,
        recordPayment,
        supplier?.id,
        supplierReference,
        terms,
        warehouse?.id,
        warehouseLocation?.id,
    ]);

    const currentPayload = useMemo(() => buildPayload(), [buildPayload]);
    const payloadKey = useMemo(() => JSON.stringify(currentPayload), [currentPayload]);
    const previewStale = Boolean(preview && !result && lastPreviewKey.current !== payloadKey);

    const canSubmit = useMemo(() => {
        const hasLine = lines.length > 0;
        const pricingReady = lines.every((line) => (
            line.auto_price !== false
            || line.pricing_state === 'persisted'
            || (line.manual_price_confirmed && line.pricing_context_hash)
        ));
        const paymentReady = !recordPayment || (
            isPositiveDecimal(paymentTotal)
            && paymentRows.every((row) => isPositiveDecimal(row.amount) && row.payment_method_id && row.source_account_id)
        );

        return Boolean(
            supplier?.id
            && supplierReference.trim()
            && purchaseDate
            && hasLine
            && pricingReady
            && warehouse?.id
            && paymentReady
            && !previewing
            && !submitting
        );
    }, [lines, paymentRows, paymentTotal, previewing, purchaseDate, recordPayment, submitting, supplier?.id, supplierReference, warehouse?.id]);

    const canPreview = canSubmit && canPreviewPermission;
    const canExecute = canSubmit && canExecutePermission && !previewStale && !previewing;
    const dirty = Boolean(
        supplier
        || supplierReference
        || notes
        || adjustments.length > 0
        || lines.length > 0
        || paymentRows.some((row) => row.amount || row.reference || row.payment_method_id || row.source_account_id)
    );

    useUnsavedChanges(dirty && !result && !submitting);

    const runPreview = async () => {
        if (previewing || !canPreview) return;
        previewController.current?.abort();
        const controller = new AbortController();
        previewController.current = controller;
        submittedLineKeys.current = lines.map((line) => line.client_key);
        submittedPaymentKeys.current = paymentRows.map((row) => row.key);
        setPreviewing(true);
        setError(null);
        try {
            const next = await previewFastPurchase(currentPayload, controller.signal);
            if (controller.signal.aborted) return;
            setPreview(next);
            setResult(null);
            lastPreviewKey.current = payloadKey;
        } catch (requestError) {
            if (!controller.signal.aborted) setError(toApiError(requestError));
        } finally {
            if (!controller.signal.aborted) setPreviewing(false);
        }
    };

    const submit = async () => {
        if (submitting || !canExecute || previewStale) return;
        previewController.current?.abort();
        setPreviewing(false);
        submittedLineKeys.current = lines.map((line) => line.client_key);
        submittedPaymentKeys.current = paymentRows.map((row) => row.key);
        setSubmitting(true);
        setError(null);
        try {
            const created = await createFastPurchase(currentPayload);
            setResult(created);
            setPreview(created);
            lastPreviewKey.current = payloadKey;
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    const createAnother = () => {
        setSupplierState(null);
        setSupplierReference('');
        setNotes('');
        setLines([]);
        setAdjustments([]);
        setPaymentRows([blankPaymentMethodRow()]);
        setPreview(null);
        setResult(null);
        setError(null);
        lastPreviewKey.current = null;
    };

    const resetForm = () => {
        if (!window.confirm('Reset this fast purchase form and clear entered data?')) return;
        createAnother();
    };

    const errorIndexForLine = useCallback((line: FastPurchaseLineRow, index: number) => {
        const submittedIndex = submittedLineKeys.current.indexOf(line.client_key);
        return submittedIndex >= 0 ? submittedIndex : index;
    }, []);

    const errorIndexForPaymentRow = useCallback((row: PurchasePaymentMethodRow, index: number) => {
        const submittedIndex = submittedPaymentKeys.current.indexOf(row.key);
        return submittedIndex >= 0 ? submittedIndex : index;
    }, []);

    return {
        context,
        defaults,
        currentError,
        errorFor,
        supplier,
        setSupplier,
        purchaseDate,
        setPurchaseDate,
        warehouse,
        setWarehouse,
        warehouseLocation,
        setWarehouseLocation,
        currency,
        setCurrency,
        exchangeRate,
        setExchangeRate,
        terms,
        setTerms,
        supplierReference,
        setSupplierReference,
        notes,
        setNotes,
        preset,
        setPreset,
        lines,
        setLines,
        adjustments,
        setAdjustments,
        paymentRows,
        setPaymentRows,
        preview,
        result,
        previewing,
        submitting,
        receiveStock,
        createInvoice,
        recordPayment,
        paymentTotal,
        previewStale,
        canSubmit,
        canPreview,
        canExecute,
        currentPayload,
        buildPayload,
        runPreview,
        submit,
        createAnother,
        resetForm,
        errorIndexForLine,
        errorIndexForPaymentRow,
    };
}

function invalidatePricingContext(line: FastPurchaseLineRow): FastPurchaseLineRow {
    const manualRequiresConfirmation = line.auto_price === false && line.pricing_state !== 'persisted';

    return {
        ...line,
        price_source_label: undefined,
        price_source: null,
        price_source_id: null,
        pricing_context_hash: null,
        manual_price_confirmed: manualRequiresConfirmation ? false : line.manual_price_confirmed,
        pricing_state: manualRequiresConfirmation ? 'manual_confirmed' : line.pricing_state,
    };
}
