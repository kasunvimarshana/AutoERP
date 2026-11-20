import React, { useState, useEffect } from 'react';
import { Modal } from '@/shared/components/Modal';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { LoadingState } from '@/shared/components/LoadingState';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { searchCustomerAgreements } from './agreementApi';
import {
    previewRentalQuotation,
    type RentalQuotationPreview,
    type RentalQuotationPreviewRequest,
} from './rentalBillingApi';

interface Props {
    open: boolean;
    onClose: () => void;
    initialAgreementId?: number;
    initialAgreementReference?: string;
    initialCycleId?: number;
    onCreateInvoice?: (agreementId: number, periodFrom: string, periodUntil: string) => void;
    onProceedToCreateInvoice?: (agreementId: number) => void;
}

export function RentalQuotationPreviewModal({
    open,
    onClose,
    initialAgreementId,
    initialAgreementReference,
    initialCycleId,
    onCreateInvoice,
    onProceedToCreateInvoice,
}: Props) {
    const today = businessDateInputValue();
    const startOfMonth = today.slice(0, 7) + '-01';

    const [selectedAgreement, setSelectedAgreement] = useState<NamedResource | null>(
        initialAgreementId
            ? { id: initialAgreementId, name: initialAgreementReference || `Agreement #${initialAgreementId}` }
            : null
    );
    const [periodFrom, setPeriodFrom] = useState(startOfMonth);
    const [periodUntil, setPeriodUntil] = useState(today);

    // Optional override parameters
    const [showOverrides, setShowOverrides] = useState(false);
    const [custodyDays, setCustodyDays] = useState<string>('');
    const [excessKm, setExcessKm] = useState<string>('');
    const [normalOtHours, setNormalOtHours] = useState<string>('');
    const [doubleOtHours, setDoubleOtHours] = useState<string>('');
    const [tripleOtHours, setTripleOtHours] = useState<string>('');
    const [nightOuts, setNightOuts] = useState<string>('');
    const [tollsAmount, setTollsAmount] = useState<string>('');
    const [taxRate, setTaxRate] = useState<string>('0');
    const [additionDesc, setAdditionDesc] = useState<string>('');
    const [additionAmount, setAdditionAmount] = useState<string>('');
    const [discountDesc, setDiscountDesc] = useState<string>('');
    const [discountAmount, setDiscountAmount] = useState<string>('');

    // Quotation Result State
    const [loading, setLoading] = useState(false);
    const [preview, setPreview] = useState<RentalQuotationPreview | null>(null);
    const [error, setError] = useState<ApiError | null>(null);

    const activeAgreementId = initialAgreementId ?? selectedAgreement?.id;

    useEffect(() => {
        if (!open) {
            setPreview(null);
            setError(null);
            setLoading(false);
            return;
        }

        if (initialAgreementId) {
            setSelectedAgreement({
                id: initialAgreementId,
                name: initialAgreementReference || `Agreement #${initialAgreementId}`,
            });
        }
    }, [open, initialAgreementId, initialAgreementReference]);

    async function handleGeneratePreview(e?: React.FormEvent) {
        if (e) e.preventDefault();
        if (!activeAgreementId && !initialCycleId) {
            setError(toApiError(new Error('Please select a customer agreement or billing cycle to generate a quotation preview.')));
            return;
        }

        setLoading(true);
        setError(null);

        const payload: RentalQuotationPreviewRequest = {};

        if (initialCycleId) {
            payload.billing_cycle_id = initialCycleId;
        }
        if (activeAgreementId) {
            payload.agreement_id = activeAgreementId;
            payload.customer_agreement_id = activeAgreementId;
        }
        if (periodFrom) payload.period_from = periodFrom;
        if (periodUntil) payload.period_until = periodUntil;

        if (custodyDays && !isNaN(Number(custodyDays))) payload.custody_days = Number(custodyDays);
        if (excessKm) payload.excess_km = excessKm;
        if (normalOtHours) payload.normal_ot_hours = normalOtHours;
        if (doubleOtHours) payload.double_ot_hours = doubleOtHours;
        if (tripleOtHours) payload.triple_ot_hours = tripleOtHours;
        if (nightOuts && !isNaN(Number(nightOuts))) payload.night_outs = Number(nightOuts);
        if (tollsAmount) payload.tolls_amount = tollsAmount;
        if (taxRate) payload.tax_rate = taxRate;

        if (additionDesc && additionAmount) {
            payload.additions = [{ description: additionDesc, amount: additionAmount }];
        }
        if (discountDesc && discountAmount) {
            payload.deductions = [{ description: discountDesc, amount: discountAmount }];
        }

        try {
            const data = await previewRentalQuotation(payload);
            setPreview(data);
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setLoading(false);
        }
    }

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="Rental Quotation & Pro-Forma Preview"
        >
            <div className="space-y-6">
                {/* Simulation Notice Banner */}
                <div className="rounded-lg border border-blue-200 bg-blue-50/70 p-4 text-sm text-blue-900">
                    <div className="flex items-start gap-3">
                        <div className="mt-0.5 rounded-full bg-blue-100 p-1 text-blue-700">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div className="flex-1">
                            <p className="font-semibold text-blue-950">Pre-Billing Quotation Simulation</p>
                            <p className="mt-1 text-xs text-blue-800">
                                This quotation simulates prorated custody days, multi-tier overtime (1.5x / 2.0x / 3.0x), excess mileage, tolls, additions, and statutory taxes.
                                You can review and adjust variables before committing to an actual invoice.
                            </p>
                        </div>
                    </div>
                </div>

                {error && <ErrorAlert error={error} />}

                {/* Configuration Controls Form */}
                <form onSubmit={handleGeneratePreview} className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                        <div className="sm:col-span-2">
                            <LookupSelect
                                label="Customer Agreement"
                                value={selectedAgreement}
                                onChange={setSelectedAgreement}
                                search={searchCustomerAgreements}
                                placeholder="Search customer agreement..."
                                disabled={Boolean(initialAgreementId)}
                            />
                        </div>

                        <div>
                            <Input
                                label="Period From"
                                type="date"
                                value={periodFrom}
                                onChange={e => setPeriodFrom(e.target.value)}
                                required
                            />
                        </div>

                        <div>
                            <Input
                                label="Period Until"
                                type="date"
                                value={periodUntil}
                                onChange={e => setPeriodUntil(e.target.value)}
                                required
                            />
                        </div>
                    </div>

                    <div className="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                        <button
                            type="button"
                            className="text-xs font-medium text-blue-600 hover:text-blue-800"
                            onClick={() => setShowOverrides(!showOverrides)}
                        >
                            {showOverrides ? '▲ Hide Advanced Telematics & Adjustments' : '▼ Customize Overtime, Mileage & Discounts'}
                        </button>
                        <Button
                            type="submit"
                            variant="primary"
                            size="sm"
                            loading={loading}
                            disabled={!activeAgreementId && !initialCycleId}
                        >
                            {preview ? 'Update Preview Calculation' : 'Generate Quotation Preview'}
                        </Button>
                    </div>

                    {showOverrides && (
                        <div className="mt-4 border-t border-slate-100 pt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 text-xs">
                            <Input
                                label="Custody Days (Proration)"
                                type="number"
                                min="0"
                                placeholder="e.g. 30"
                                value={custodyDays}
                                onChange={e => setCustodyDays(e.target.value)}
                            />
                            <Input
                                label="Excess KM"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="e.g. 50"
                                value={excessKm}
                                onChange={e => setExcessKm(e.target.value)}
                            />
                            <Input
                                label="Normal OT Hours (1.5x)"
                                type="number"
                                step="0.1"
                                min="0"
                                placeholder="Normal OT"
                                value={normalOtHours}
                                onChange={e => setNormalOtHours(e.target.value)}
                            />
                            <Input
                                label="Double OT Hours (2.0x)"
                                type="number"
                                step="0.1"
                                min="0"
                                placeholder="Double OT"
                                value={doubleOtHours}
                                onChange={e => setDoubleOtHours(e.target.value)}
                            />
                            <Input
                                label="Triple OT Hours (3.0x)"
                                type="number"
                                step="0.1"
                                min="0"
                                placeholder="Triple OT"
                                value={tripleOtHours}
                                onChange={e => setTripleOtHours(e.target.value)}
                            />
                            <Input
                                label="Driver Night Outs"
                                type="number"
                                min="0"
                                placeholder="Nights"
                                value={nightOuts}
                                onChange={e => setNightOuts(e.target.value)}
                            />
                            <Input
                                label="Highway Tolls"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="Tolls amount"
                                value={tollsAmount}
                                onChange={e => setTollsAmount(e.target.value)}
                            />
                            <Input
                                label="Tax Rate (%)"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                placeholder="Tax %"
                                value={taxRate}
                                onChange={e => setTaxRate(e.target.value)}
                            />
                            <div className="col-span-2">
                                <label className="mb-1.5 block font-medium text-slate-700">Ad-hoc Addition</label>
                                <div className="flex gap-2">
                                    <Input
                                        placeholder="Description (e.g. Airport fee)"
                                        aria-label="Addition description"
                                        value={additionDesc}
                                        onChange={e => setAdditionDesc(e.target.value)}
                                    />
                                    <div className="w-28 shrink-0">
                                        <Input
                                            type="number"
                                            placeholder="Amount"
                                            aria-label="Addition amount"
                                            value={additionAmount}
                                            onChange={e => setAdditionAmount(e.target.value)}
                                        />
                                    </div>
                                </div>
                            </div>
                            <div className="col-span-2">
                                <label className="mb-1.5 block font-medium text-slate-700">Promotional Discount</label>
                                <div className="flex gap-2">
                                    <Input
                                        placeholder="Description (e.g. 5% rebate)"
                                        aria-label="Discount description"
                                        value={discountDesc}
                                        onChange={e => setDiscountDesc(e.target.value)}
                                    />
                                    <div className="w-28 shrink-0">
                                        <Input
                                            type="number"
                                            placeholder="Amount"
                                            aria-label="Discount amount"
                                            value={discountAmount}
                                            onChange={e => setDiscountAmount(e.target.value)}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </form>

                {/* Loading State */}
                {loading && <LoadingState label="Calculating simulated quotation metrics & telematics breakdown..." />}

                {/* Quotation Document View */}
                {!loading && preview && (
                    <div className="rounded-xl border border-slate-300 bg-white p-6 shadow-sm font-sans space-y-6">
                        {/* Quotation Document Header */}
                        <div className="flex flex-col md:flex-row md:items-center justify-between border-b border-slate-200 pb-5">
                            <div>
                                <span className="inline-block rounded bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-800 uppercase tracking-wide">
                                    Pro-Forma Quotation
                                </span>
                                <h2 className="mt-1 text-2xl font-bold text-slate-900 tracking-tight">
                                    {preview.quotation_number}
                                </h2>
                                <p className="text-xs text-slate-500 mt-0.5">
                                    Issue Date: {preview.quotation_date} · Valid for rental term
                                </p>
                            </div>
                            <div className="mt-4 md:mt-0 text-right">
                                <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Quoted Amount</span>
                                <span className="text-3xl font-extrabold text-blue-700">
                                    <MoneyDisplay value={preview.financial_breakdown.grand_total} />
                                </span>
                            </div>
                        </div>

                        {/* Agreement & Customer Context Details */}
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3 rounded-lg bg-slate-50 p-4 text-xs border border-slate-100">
                            <div>
                                <span className="text-slate-400 font-medium block">Customer</span>
                                <span className="font-semibold text-slate-900 text-sm block mt-0.5">
                                    {preview.customer?.name || 'Valued Customer'}
                                </span>
                                {preview.customer?.phone && (
                                    <span className="text-slate-500 block">{preview.customer.phone}</span>
                                )}
                            </div>
                            <div>
                                <span className="text-slate-400 font-medium block">Agreement & Term</span>
                                <span className="font-semibold text-slate-900 block mt-0.5">
                                    {preview.agreement?.reference} ({preview.agreement?.billing_basis || 'Monthly'})
                                </span>
                                <span className="text-slate-500 block">
                                    Period: {preview.period.from} To {preview.period.until}
                                </span>
                            </div>
                            <div>
                                <span className="text-slate-400 font-medium block">Assigned Fleet Vehicle</span>
                                <span className="font-semibold text-slate-900 block mt-0.5">
                                    {preview.telematics?.assigned_vehicles?.join(', ') || 'Standard Allocated Fleet'}
                                </span>
                                <span className="text-slate-500 block">
                                    Total Commercial KM: <span>{preview.telematics?.commercial_km || '0.00'} km</span>
                                </span>
                            </div>
                        </div>

                        {/* Itemized Line Items Table */}
                        <div className="overflow-hidden rounded-lg border border-slate-200">
                            <table className="min-w-full divide-y divide-slate-200 text-left text-xs">
                                <thead className="bg-slate-50 text-slate-700 font-semibold uppercase tracking-wider">
                                    <tr>
                                        <th scope="col" className="px-4 py-2.5">#</th>
                                        <th scope="col" className="px-4 py-2.5">Item Description</th>
                                        <th scope="col" className="px-4 py-2.5 text-right">Quantity</th>
                                        <th scope="col" className="px-4 py-2.5 text-right">Rate</th>
                                        <th scope="col" className="px-4 py-2.5 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white text-slate-800">
                                    {preview.lines && preview.lines.length > 0 ? (
                                        preview.lines.map((line, idx) => (
                                            <tr key={idx} className="hover:bg-slate-50/50">
                                                <td className="px-4 py-2.5 text-slate-400 font-medium">{line.line_number || idx + 1}</td>
                                                <td className="px-4 py-2.5 font-medium text-slate-900">{line.description}</td>
                                                <td className="px-4 py-2.5 text-right text-slate-600">{Number(line.quantity).toFixed(2)}</td>
                                                <td className="px-4 py-2.5 text-right text-slate-600">
                                                    <MoneyDisplay value={line.unit_price} />
                                                </td>
                                                <td className="px-4 py-2.5 text-right font-semibold text-slate-900">
                                                    <MoneyDisplay value={line.line_total} />
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={5} className="px-4 py-4 text-center text-slate-400">
                                                No itemized charges generated for this period.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Financial Totals Summary Breakdown */}
                        <div className="flex flex-col md:flex-row justify-between items-start gap-4 border-t border-slate-200 pt-4">
                            <div className="text-xs text-slate-500 space-y-1">
                                <p className="font-semibold text-slate-700">Quotation Notes & Conditions:</p>
                                <p>• Excess kilometers and multi-tier overtime rates are assessed based on confirmed running charts.</p>
                                <p>• Fuel, highway tolls, and agreed driver allowances are billed at cost recovery.</p>
                                <p>• Final billing is subject to actual vehicle inspection and check-in odometer logs.</p>
                            </div>

                            <div className="w-full md:w-72 space-y-2 text-xs">
                                <div className="flex justify-between py-1 text-slate-600">
                                    <span>Gross Subtotal:</span>
                                    <MoneyDisplay value={preview.financial_breakdown.gross_subtotal} />
                                </div>
                                {Number(preview.financial_breakdown.discount_total || 0) > 0 && (
                                    <div className="flex justify-between py-1 text-rose-600 font-medium">
                                        <span>Deductions / Discount:</span>
                                        <span>-<MoneyDisplay value={preview.financial_breakdown.discount_total} /></span>
                                    </div>
                                )}
                                <div className="flex justify-between py-1 font-semibold text-slate-800 border-t border-slate-100">
                                    <span>Net Subtotal:</span>
                                    <MoneyDisplay value={preview.financial_breakdown.net_subtotal} />
                                </div>
                                {Number(preview.financial_breakdown.tax_amount || 0) > 0 && (
                                    <div className="flex justify-between py-1 text-slate-600">
                                        <span>Applicable Tax ({taxRate}%):</span>
                                        <MoneyDisplay value={preview.financial_breakdown.tax_amount} />
                                    </div>
                                )}
                                <div className="flex justify-between py-2 text-sm font-bold text-slate-900 border-t-2 border-slate-300">
                                    <span>Estimated Grand Total:</span>
                                    <span className="text-blue-700">
                                        <MoneyDisplay value={preview.financial_breakdown.grand_total} />
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Modal Bottom Footer Actions */}
                <div className="flex w-full items-center justify-between border-t border-slate-200 pt-4">
                    <span className="text-xs text-slate-500">
                        ⚡ Preview simulation only — exactly 0 database records are written.
                    </span>
                    <div className="flex gap-2">
                        <Button variant="secondary" onClick={onClose}>
                            Close
                        </Button>
                        {preview && (onProceedToCreateInvoice || onCreateInvoice) && (activeAgreementId || preview.agreement?.id) && (
                            <Button
                                variant="primary"
                                onClick={() => {
                                    const agrId = activeAgreementId ?? preview.agreement.id;
                                    if (onProceedToCreateInvoice) {
                                        onProceedToCreateInvoice(agrId);
                                    } else if (onCreateInvoice) {
                                        onCreateInvoice(agrId, preview.period.from, preview.period.until);
                                    }
                                    onClose();
                                }}
                            >
                                Proceed to Issue Invoice
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </Modal>
    );
}
