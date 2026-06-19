import { Link } from 'react-router-dom';
import type { ReactNode } from 'react';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { compareDecimalStrings } from '@/shared/utils/decimal';
import { CurrencyLookupSelect, SupplierLookupSelect, WarehouseLocationLookupSelect, WarehouseLookupSelect } from './PurchaseLookups';
import { PurchasePaymentMethodsEditor, type PurchasePaymentMethodRow } from './PurchasePaymentMethodsEditor';
import type { FastPurchaseContext, FastPurchaseResult } from '../purchaseTypes';

export type FastPurchasePreset = 'expense_only' | 'purchase_receive' | 'purchase_receive_invoice' | 'purchase_receive_invoice_pay';

export const fastPurchasePaymentTerms = [
    { value: 'due_on_receipt', label: 'Due on receipt' },
    { value: 'net_7', label: 'Net 7' },
    { value: 'net_15', label: 'Net 15' },
    { value: 'net_30', label: 'Net 30' },
];

export const fastPurchasePresets: Array<{ value: FastPurchasePreset; label: string; description: string }> = [
    { value: 'expense_only', label: 'Expense Only', description: 'Invoice non-stock supplier costs.' },
    { value: 'purchase_receive', label: 'Purchase + Receive', description: 'Post a goods receipt only.' },
    { value: 'purchase_receive_invoice', label: 'Purchase + Receive + Invoice', description: 'Receive stock and post the supplier invoice.' },
    { value: 'purchase_receive_invoice_pay', label: 'Purchase + Receive + Invoice + Pay', description: 'Create receipt, invoice, and supplier payment.' },
];

export function FastPurchaseSection({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h2 className="mb-4 text-base font-semibold text-slate-950">{title}</h2>
            {children}
        </section>
    );
}

export function FastPurchaseDetailsSection({
    supplier,
    supplierReference,
    currency,
    exchangeRate,
    exchangeRateHint,
    receiveStock,
    warehouse,
    warehouseLocation,
    purchaseDate,
    terms,
    notes,
    preset,
    errorFor,
    onSupplierChange,
    onSupplierReferenceChange,
    onCurrencyChange,
    onExchangeRateChange,
    onWarehouseChange,
    onWarehouseLocationChange,
    onPurchaseDateChange,
    onTermsChange,
    onNotesChange,
    onPresetChange,
}: {
    supplier: NamedResource | null;
    supplierReference: string;
    currency: NamedResource | null;
    exchangeRate: string;
    exchangeRateHint?: string;
    receiveStock: boolean;
    warehouse: NamedResource | null;
    warehouseLocation: NamedResource | null;
    purchaseDate: string;
    terms: string;
    notes: string;
    preset: FastPurchasePreset;
    errorFor: (field: string) => string | undefined;
    onSupplierChange: (supplier: NamedResource | null) => void;
    onSupplierReferenceChange: (reference: string) => void;
    onCurrencyChange: (currency: NamedResource | null) => void;
    onExchangeRateChange: (rate: string) => void;
    onWarehouseChange: (warehouse: NamedResource | null) => void;
    onWarehouseLocationChange: (location: NamedResource | null) => void;
    onPurchaseDateChange: (date: string) => void;
    onTermsChange: (terms: string) => void;
    onNotesChange: (notes: string) => void;
    onPresetChange: (preset: FastPurchasePreset) => void;
}) {
    return (
        <FastPurchaseSection title="Purchase Details">
            <div className="grid gap-4 md:grid-cols-2">
                <SupplierLookupSelect value={supplier} onChange={onSupplierChange} error={errorFor('supplier_id')} />
                <Input label="Supplier reference" value={supplierReference} error={errorFor('supplier_reference')} onChange={(event) => onSupplierReferenceChange(event.target.value)} />
                <CurrencyLookupSelect value={currency} onChange={onCurrencyChange} error={errorFor('currency_id')} />
                <DecimalInput
                    label="Exchange rate"
                    value={exchangeRate}
                    hint={exchangeRateHint}
                    error={errorFor('exchange_rate')}
                    onChange={(event) => onExchangeRateChange(event.target.value)}
                />
                {receiveStock && <WarehouseLookupSelect value={warehouse} onChange={onWarehouseChange} error={errorFor('warehouse_id')} />}
                {receiveStock && <WarehouseLocationLookupSelect warehouseId={warehouse?.id} value={warehouseLocation} onChange={onWarehouseLocationChange} error={errorFor('warehouse_location_id')} />}
                <Input
                    label="Purchase date"
                    type="date"
                    value={purchaseDate}
                    error={errorFor('purchase_date')}
                    onChange={(event) => onPurchaseDateChange(event.target.value)}
                />
                <Select label="Payment terms" value={terms} options={fastPurchasePaymentTerms} onChange={(event) => onTermsChange(event.target.value)} />
                <div className="md:col-span-2">
                    <Textarea label="Notes" value={notes} error={errorFor('notes')} onChange={(event) => onNotesChange(event.target.value)} />
                </div>
            </div>
            <div className="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                {fastPurchasePresets.map((option) => (
                    <label key={option.value} className={`rounded-lg border p-3 text-sm ${preset === option.value ? 'border-sky-300 bg-sky-50 text-sky-900' : 'border-slate-200 bg-white text-slate-700'}`}>
                        <span className="flex items-center gap-2 font-semibold">
                            <input type="radio" name="fast_purchase_preset" checked={preset === option.value} onChange={() => onPresetChange(option.value)} />
                            {option.label}
                        </span>
                        <span className="mt-1 block text-xs text-slate-500">{option.description}</span>
                    </label>
                ))}
            </div>
        </FastPurchaseSection>
    );
}

export function FastPurchasePaymentSection({
    rows,
    context,
    paymentTotal,
    previewTotal,
    errorFor,
    errorIndexForRow,
    onChange,
}: {
    rows: PurchasePaymentMethodRow[];
    context: FastPurchaseContext | null;
    paymentTotal: string;
    previewTotal?: string;
    errorFor: (field: string) => string | undefined;
    errorIndexForRow: (row: PurchasePaymentMethodRow, index: number) => number;
    onChange: (rows: PurchasePaymentMethodRow[]) => void;
}) {
    return (
        <FastPurchaseSection title="Payment">
            <PurchasePaymentMethodsEditor
                rows={rows}
                methods={context?.payment_methods ?? []}
                accounts={context?.payment_accounts ?? []}
                errorFor={errorFor}
                errorIndexForRow={errorIndexForRow}
                onChange={onChange}
            />
            <div className={`mt-3 rounded-lg px-3 py-2 text-sm font-medium ${compareDecimalStrings(paymentTotal, previewTotal ?? paymentTotal) > 0 ? 'bg-rose-50 text-rose-700' : 'bg-slate-50 text-slate-700'}`}>
                Payment rows total: {paymentTotal}
            </div>
        </FastPurchaseSection>
    );
}

export function FastPurchaseImpactSection({ preset, result }: { preset: FastPurchasePreset; result: FastPurchaseResult | null }) {
    const receiveStock = result?.options.receive_stock_now ?? preset !== 'expense_only';
    const createInvoice = result?.options.create_supplier_invoice_now ?? preset !== 'purchase_receive';
    const recordPayment = result?.options.record_payment_now ?? preset === 'purchase_receive_invoice_pay';
    const rows = [
        { label: 'Purchase Order', produced: false, detail: 'Not created by Fast Purchase' },
        {
            label: 'GRN / inventory receipt',
            produced: receiveStock,
            detail: result?.documents.goods_receipt?.number ?? (receiveStock ? 'Will be created' : 'Not produced for this preset'),
        },
        {
            label: 'Supplier Invoice',
            produced: createInvoice,
            detail: result?.documents.supplier_invoice?.number ?? (createInvoice ? 'Will be created' : 'Not produced for this preset'),
        },
        {
            label: 'Payment',
            produced: recordPayment,
            detail: result?.documents.supplier_payment?.number ?? (recordPayment ? 'Will be recorded' : 'Not produced for this preset'),
        },
    ];

    return (
        <FastPurchaseSection title="Impact Summary">
            <div className="grid gap-3 md:grid-cols-2">
                {rows.map((row) => (
                    <div key={row.label} className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <div className="font-semibold text-slate-900">{row.label}</div>
                                <div className="mt-1 text-slate-600">{row.detail}</div>
                            </div>
                            <span className={`rounded-full px-2 py-1 text-xs font-semibold ${row.produced ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-600'}`}>
                                {row.produced ? 'Produced' : 'Not produced'}
                            </span>
                        </div>
                    </div>
                ))}
            </div>
        </FastPurchaseSection>
    );
}

export function FastPurchaseCompletedSummary({ result }: { result: FastPurchaseResult }) {
    const documents = [
        result.documents.goods_receipt,
        result.documents.supplier_invoice,
        result.documents.supplier_payment,
    ].filter(Boolean);

    return (
        <FastPurchaseSection title="Completed">
            <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                Fast purchase completed successfully.
            </div>
            {documents.length > 0 && (
                <div className="mt-4 grid gap-3 md:grid-cols-3">
                    {documents.map((document) => document && (
                        <Link key={document.url} to={document.url} className="rounded-lg border border-slate-200 bg-white p-3 text-sm font-semibold text-sky-700 hover:bg-sky-50">
                            {document.number}
                        </Link>
                    ))}
                </div>
            )}
        </FastPurchaseSection>
    );
}
