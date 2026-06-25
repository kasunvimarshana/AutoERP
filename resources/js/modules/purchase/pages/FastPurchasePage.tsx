import { useSearchParams } from 'react-router-dom';
import { fieldError, hasNestedFieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useAuth } from '@/modules/auth/AuthProvider';
import { PurchaseDocumentShell, PurchasePageHeader } from '../components/PurchaseDocumentShell';
import { PurchaseTabs, type PurchaseTabItem } from '../components/PurchaseTabs';
import { PurchaseHeaderAdjustmentEditor } from '../components/PurchaseHeaderAdjustmentEditor';
import { FastPurchaseLines } from '../components/FastPurchaseLines';
import { FastPurchaseSummary } from '../components/FastPurchaseSummary';
import {
    FastPurchaseCompletedSummary,
    FastPurchaseDetailsSection,
    FastPurchaseImpactSection,
    FastPurchasePaymentSection,
    FastPurchaseSection,
    fastPurchasePresets,
} from '../components/FastPurchaseSections';
import { useFastPurchaseForm } from '../hooks/useFastPurchaseForm';
import { hasPurchasePermission, purchasePermissions } from '../purchasePermissions';

type FastPurchaseTab = 'details' | 'lines' | 'adjustments' | 'payment' | 'impact';

const tabIds: FastPurchaseTab[] = ['details', 'lines', 'adjustments', 'payment', 'impact'];

export default function FastPurchasePage() {
    const auth = useAuth();
    const [searchParams] = useSearchParams();
    const can = (permission: string) => hasPurchasePermission(auth.permissions, permission);
    const form = useFastPurchaseForm({
        canPreviewPermission: can(purchasePermissions.fastPurchasesView),
        canExecutePermission: can(purchasePermissions.fastPurchasesExecute),
    });

    const {
        context,
        defaults,
        currentError,
        contextRecoveryError,
        retryContext,
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
        recordPayment,
        paymentTotal,
        previewStale,
        canPreview,
        canExecute,
        runPreview,
        submit,
        createAnother,
        resetForm,
        confirmDialog,
        errorIndexForLine,
        errorIndexForPaymentRow,
    } = form;

    const visibleTabIds = recordPayment ? tabIds : tabIds.filter((tab) => tab !== 'payment');
    const activeTab = visibleTabIds.includes(searchParams.get('tab') as FastPurchaseTab)
        ? searchParams.get('tab') as FastPurchaseTab
        : 'details';
    const presetLabel = fastPurchasePresets.find((option) => option.value === preset)?.label ?? preset.replaceAll('_', ' ');

    const tabs: PurchaseTabItem[] = [
        { id: 'details', label: 'Purchase Details', error: hasAnyError(currentError, ['supplier_id', 'purchase_date', 'warehouse_id', 'warehouse_location_id', 'currency_id', 'exchange_rate']) },
        { id: 'lines', label: 'Lines', count: lines.length, error: hasNestedFieldError(currentError, 'lines') },
        { id: 'adjustments', label: 'Adjustments', count: adjustments.length, error: hasNestedFieldError(currentError, 'adjustments') },
        ...(recordPayment ? [{ id: 'payment', label: 'Payment', count: paymentRows.length, error: hasNestedFieldError(currentError, 'payment') }] : []),
        { id: 'impact', label: 'Impact Summary' },
    ];

    const header = (
        <PurchasePageHeader
            title="Fast Purchase"
            description="Quickly record supplier purchases while keeping receipt, invoice, payment, inventory, tax, and finance posting rules authoritative."
            status={<span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{presetLabel}</span>}
            actions={result ? (
                <Button type="button" onClick={createAnother}>Create Another Fast Purchase</Button>
            ) : (
                <>
                    <Button type="button" variant="secondary" onClick={() => void resetForm()} disabled={submitting || previewing}>Reset</Button>
                    <Button type="button" variant="secondary" loading={previewing} disabled={!canPreview || previewing} onClick={() => void runPreview()}>Preview</Button>
                    <Button type="submit" loading={submitting} disabled={!canExecute || previewStale}>Create Fast Purchase</Button>
                </>
            )}
        />
    );

    return (
        <form onSubmit={(event) => { event.preventDefault(); void submit(); }}>
            <PurchaseDocumentShell
                header={header}
                tabs={<PurchaseTabs tabs={tabs} activeTab={activeTab} />}
                summary={<FastPurchaseSummary
                    preview={preview}
                    result={result}
                    stale={previewStale}
                />}
            >
                <ErrorAlert error={currentError} />
                {contextRecoveryError && (
                    <Button type="button" variant="secondary" onClick={retryContext}>Retry purchase defaults</Button>
                )}
                {result ? <FastPurchaseCompletedSummary result={result} /> : <>
                    {activeTab === 'details' && (
                        <FastPurchaseDetailsSection
                            supplier={supplier}
                            supplierReference={supplierReference}
                            currency={currency}
                            exchangeRate={exchangeRate}
                            exchangeRateHint={defaults?.exchange_rate_source}
                            warehouse={warehouse}
                            warehouseLocation={warehouseLocation}
                            purchaseDate={purchaseDate}
                            terms={terms}
                            notes={notes}
                            preset={preset}
                            errorFor={errorFor}
                            onSupplierChange={setSupplier}
                            onSupplierReferenceChange={setSupplierReference}
                            onCurrencyChange={setCurrency}
                            onExchangeRateChange={setExchangeRate}
                            onWarehouseChange={setWarehouse}
                            onWarehouseLocationChange={setWarehouseLocation}
                            onPurchaseDateChange={setPurchaseDate}
                            onTermsChange={setTerms}
                            onNotesChange={setNotes}
                            onPresetChange={setPreset}
                        />
                    )}

                    {activeTab === 'lines' && <FastPurchaseSection title="Lines">
                        <FastPurchaseLines
                            rows={lines}
                            context={context.data}
                            supplierId={supplier?.id}
                            currencyId={currency?.id}
                            warehouseId={warehouse?.id}
                            purchaseDate={purchaseDate}
                            previewLines={!previewStale ? preview?.lines ?? [] : []}
                            errorFor={errorFor}
                            errorIndexForLine={errorIndexForLine}
                            onChange={setLines}
                        />
                    </FastPurchaseSection>}

                    {activeTab === 'adjustments' && <FastPurchaseSection title="Adjustments">
                        <PurchaseHeaderAdjustmentEditor
                            adjustments={adjustments}
                            allocationLines={lines.map((line, index) => ({
                                clientLineKey: line.client_key,
                                label: line.item?.name ?? line.description ?? `Line ${index + 1}`,
                            }))}
                            onChange={setAdjustments}
                            errorFor={errorFor}
                        />
                    </FastPurchaseSection>}

                    {activeTab === 'payment' && (
                        <FastPurchasePaymentSection
                            rows={paymentRows}
                            context={context.data}
                            paymentTotal={paymentTotal}
                            previewTotal={preview?.summary.grand_total}
                            errorFor={errorFor}
                            errorIndexForRow={errorIndexForPaymentRow}
                            onChange={setPaymentRows}
                        />
                    )}

                    {activeTab === 'impact' && <FastPurchaseImpactSection preset={preset} result={preview ?? result} />}
                </>}
            </PurchaseDocumentShell>
            {confirmDialog}
        </form>
    );
}

function hasAnyError(error: ApiError | null, fields: string[]): boolean {
    return fields.some((field) => Boolean(fieldError(error, field)));
}
