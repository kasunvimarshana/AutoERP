import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import type { NamedResource } from '@/shared/types/common';
import {
    createSalesOrder,
    createSalesQuotation,
    updateSalesOrder,
    updateSalesQuotation,
} from '../salesApi';
import type { SalesDocumentPayload, SalesOrder, SalesQuotation } from '../salesTypes';
import { SalesDocumentAdjustmentSection } from './SalesDocumentAdjustmentSection';
import { SalesDocumentHeaderSection } from './SalesDocumentHeaderSection';
import { SalesDocumentLinesSection } from './SalesDocumentLinesSection';
import { SalesDocumentSummarySection } from './SalesDocumentSummarySection';
import {
    adjustmentFromDocument,
    asResource,
    lineFromDocument,
    salesDocumentTotals,
    todayDate,
    type EditableSalesAdjustment,
    type EditableSalesLine,
    type SalesDocument,
} from './salesDocumentFormUtils';
import { getDefaultWarehouse, getDefaultWarehouseLocation } from '@/modules/warehouse/warehouseApi';

type DocumentKind = 'quotation' | 'order';

export function SalesDocumentForm({
    kind,
    document,
}: {
    kind: DocumentKind;
    document?: SalesDocument;
}) {
    const navigate = useNavigate();
    const order = kind === 'order' ? document as SalesOrder | undefined : undefined;
    const quotation = kind === 'quotation' ? document as SalesQuotation | undefined : undefined;
    const [documentDate, setDocumentDate] = useState(
        quotation?.quotation_date ?? order?.sales_order_date ?? todayDate(),
    );
    const [secondaryDate, setSecondaryDate] = useState(
        quotation?.valid_until ?? order?.expected_delivery_date ?? '',
    );
    const [customer, setCustomer] = useState<NamedResource | null>(asResource(document?.customer));
    const [warehouse, setWarehouse] = useState<NamedResource | null>(asResource(order?.warehouse));
    const [location, setLocation] = useState<NamedResource | null>(
        asResource(order?.warehouse_location),
    );
    const [currency, setCurrency] = useState<NamedResource | null>(asResource(document?.currency));
    const [exchangeRate, setExchangeRate] = useState(document?.exchange_rate ?? '1.000000');
    const [notes, setNotes] = useState(document?.notes ?? '');
    const [lines, setLines] = useState<EditableSalesLine[]>(
        document?.lines?.length ? document.lines.map(lineFromDocument) : [],
    );
    const [adjustments, setAdjustments] = useState<EditableSalesAdjustment[]>(
        document?.adjustments?.length ? document.adjustments.map(adjustmentFromDocument) : [],
    );
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const warehouseTouched = useRef(Boolean(order?.warehouse));
    const locationTouched = useRef(Boolean(order?.warehouse_location));
    const totals = useMemo(
        () => salesDocumentTotals(lines, adjustments),
        [lines, adjustments],
    );
    const errorFor = (name: string) => fieldError(error, name);

    useEffect(() => {
        if (kind !== 'order' || document || warehouseTouched.current || warehouse || location) return;

        const controller = new AbortController();
        void getDefaultWarehouse(controller.signal)
            .then(async (defaultWarehouse) => {
                if (controller.signal.aborted || warehouseTouched.current || warehouse || !defaultWarehouse) return;
                setWarehouse(defaultWarehouse);

                if (locationTouched.current || location) return;
                const defaultLocation = await getDefaultWarehouseLocation(Number(defaultWarehouse.id), controller.signal);
                if (!controller.signal.aborted && !locationTouched.current && !location && defaultLocation) {
                    setLocation(defaultLocation);
                }
            })
            .catch(() => undefined);

        return () => controller.abort();
    }, [document, kind, location, warehouse]);

    async function submit(payload: SalesDocumentPayload) {
        const saved = kind === 'quotation'
            ? document
                ? await updateSalesQuotation(document.id, payload)
                : await createSalesQuotation(payload)
            : document
                ? await updateSalesOrder(document.id, payload)
                : await createSalesOrder(payload);

        navigate(`/sales/${kind === 'quotation' ? 'quotations' : 'orders'}/${saved.id}`);
    }

    function payload(): SalesDocumentPayload {
        return {
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
                quantity: line.quantity,
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
        };
    }

    return (
        <form
            className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_320px]"
            onSubmit={async (event) => {
                event.preventDefault();
                if (submitting) return;
                setSubmitting(true);
                setError(null);
                try {
                    await submit(payload());
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setSubmitting(false);
                }
            }}
        >
            <div className="space-y-5">
                <ErrorAlert error={error} />
                <SalesDocumentHeaderSection
                    kind={kind}
                    customer={customer}
                    warehouse={warehouse}
                    location={location}
                    currency={currency}
                    documentDate={documentDate}
                    secondaryDate={secondaryDate}
                    exchangeRate={exchangeRate}
                    notes={notes}
                    onCustomerChange={setCustomer}
                    onWarehouseChange={(value) => {
                        warehouseTouched.current = true;
                        setWarehouse(value);
                        setLocation(null);
                    }}
                    onLocationChange={(value) => {
                        locationTouched.current = true;
                        setLocation(value);
                    }}
                    onCurrencyChange={setCurrency}
                    onDocumentDateChange={setDocumentDate}
                    onSecondaryDateChange={setSecondaryDate}
                    onExchangeRateChange={setExchangeRate}
                    onNotesChange={setNotes}
                    errorFor={errorFor}
                />
                <SalesDocumentLinesSection
                    lines={lines}
                    onChange={setLines}
                    errorFor={errorFor}
                />
                <SalesDocumentAdjustmentSection
                    adjustments={adjustments}
                    onChange={setAdjustments}
                    errorFor={errorFor}
                />
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={() => navigate(-1)}>
                        Cancel
                    </Button>
                    <Button type="submit" loading={submitting}>
                        {document ? 'Save changes' : `Create ${kind}`}
                    </Button>
                </div>
            </div>
            <SalesDocumentSummarySection totals={totals} />
        </form>
    );
}
