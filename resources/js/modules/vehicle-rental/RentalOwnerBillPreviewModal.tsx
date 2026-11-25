/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useState, useEffect } from 'react';
import { Modal } from '@/shared/components/Modal';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { searchOwnerAgreements } from './agreementApi';
import {
    previewRentalOwnerBill,
    type RentalOwnerBillPreview,
} from './rentalBillingApi';

interface Props {
    open: boolean;
    onClose: () => void;
    initialAgreementId?: number;
    onProceedToGenerateBill?: (agreementId: number, startDate: string, endDate: string, whtRate: string) => void;
}

export function RentalOwnerBillPreviewModal({
    open,
    onClose,
    initialAgreementId,
    onProceedToGenerateBill,
}: Props) {
    const today = businessDateInputValue();
    const startOfMonth = today.slice(0, 7) + '-01';

    const [selectedAgreement, setSelectedAgreement] = useState<NamedResource | null>(() => (
        initialAgreementId ? { id: initialAgreementId, name: `Owner Agreement #${initialAgreementId}` } : null
    ));
    const [startDate, setStartDate] = useState(startOfMonth);
    const [endDate, setEndDate] = useState(today);
    const [whtRate, setWhtRate] = useState('10.0');

    const [preview, setPreview] = useState<RentalOwnerBillPreview | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        queueMicrotask(() => {
            if (!open) {
                setPreview(null);
                setError(null);
            } else if (initialAgreementId) {
                setSelectedAgreement({ id: initialAgreementId, name: `Owner Agreement #${initialAgreementId}` });
            }
        });
    }, [open, initialAgreementId]);

    async function handleCalculatePreview(e?: React.FormEvent) {
        if (e) e.preventDefault();

        if (!selectedAgreement) {
            setError(toApiError(new Error('Select a vehicle owner agreement to preview settlement.')));
            return;
        }

        setLoading(true);
        setError(null);
        try {
            const data = await previewRentalOwnerBill({
                owner_agreement_id: selectedAgreement.id,
                start_date: startDate,
                end_date: endDate,
                wht_rate: whtRate,
            });
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
            title="Vehicle Owner Bill & Settlement Preview"
        >
            <div className="space-y-6">
                {error && <ErrorAlert error={error} inline />}

                {/* Form Controls */}
                <form onSubmit={handleCalculatePreview} className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div className="md:col-span-2">
                            <LookupSelect
                                label="Vehicle Owner Agreement"
                                value={selectedAgreement}
                                onChange={setSelectedAgreement}
                                search={searchOwnerAgreements}
                                placeholder="Search by lessor or agreement ref..."
                            />
                        </div>

                        <div>
                            <Input
                                label="Start Date"
                                type="date"
                                value={startDate}
                                onChange={e => setStartDate(e.target.value)}
                            />
                        </div>

                        <div>
                            <Input
                                label="End Date"
                                type="date"
                                value={endDate}
                                onChange={e => setEndDate(e.target.value)}
                            />
                        </div>

                        <div>
                            <Input
                                label="WHT (%)"
                                type="number"
                                step="0.1"
                                min="0"
                                max="100"
                                value={whtRate}
                                onChange={e => setWhtRate(e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="flex justify-end items-center pt-2">
                        <Button type="submit" variant="primary" disabled={loading} loading={loading}>
                            Generate Settlement Preview
                        </Button>
                    </div>
                </form>

                {loading && <LoadingState label="Calculating owner base lease, usage charges, and statutory tax deductions..." />}

                {/* Settlement Document View */}
                {preview && !loading && (
                    <div className="border border-purple-200 rounded-xl bg-white shadow-xs overflow-hidden">
                        {/* Status Strip */}
                        <div className="bg-purple-50 px-6 py-3 border-b border-purple-100 flex flex-wrap items-center justify-between gap-2">
                            <div className="flex items-center gap-3">
                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-purple-100 text-purple-800">
                                    Supplier Bill Pro-Forma Preview
                                </span>
                                <span className="text-xs font-semibold text-slate-700">
                                    Ref: <span className="font-mono">{preview.bill_number}</span>
                                </span>
                            </div>
                            <div className="text-xs text-slate-500">
                                Date: <span className="font-semibold">{preview.document_date}</span> · Period: <span className="font-semibold">{preview.period.from}</span> to <span className="font-semibold">{preview.period.until}</span>
                            </div>
                        </div>

                        {/* Lessor & Vehicle Context */}
                        <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-slate-100">
                            <div>
                                <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                                    Lessor / Vehicle Owner (Supplier)
                                </h4>
                                <div className="text-sm font-semibold text-slate-900">
                                    {preview.supplier?.name ?? 'Vehicle Owner'}
                                </div>
                                <div className="text-xs text-slate-500 mt-1">
                                    Agreement: <span className="font-medium">{preview.agreement.reference}</span>
                                </div>
                            </div>

                            <div>
                                <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                                    Assigned Vehicle & Contract
                                </h4>
                                <div className="text-sm font-semibold text-slate-900">
                                    {preview.agreement.vehicle?.registration_number ?? 'Fleet Vehicle'}
                                </div>
                                {preview.agreement.vehicle?.make && (
                                    <div className="text-xs text-slate-500">
                                        {preview.agreement.vehicle.make} {preview.agreement.vehicle.model ?? ''}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Itemized Charges Table */}
                        <div className="p-6">
                            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                                Itemized Lessor Payable Charges
                            </h4>
                            <div className="overflow-x-auto border border-slate-200 rounded-lg">
                                <table className="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead className="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                                        <tr>
                                            <th className="px-4 py-3 text-left w-12">#</th>
                                            <th className="px-4 py-3 text-left">Description</th>
                                            <th className="px-4 py-3 text-right w-24">Qty</th>
                                            <th className="px-4 py-3 text-right w-36">Gross Payable</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 bg-white">
                                        {preview.lines.map(line => (
                                            <tr key={line.line_number} className="hover:bg-slate-50/50">
                                                <td className="px-4 py-3 text-slate-400 font-mono text-xs">{line.line_number}</td>
                                                <td className="px-4 py-3 font-medium text-slate-900">{line.description}</td>
                                                <td className="px-4 py-3 text-right text-slate-600">{line.quantity}</td>
                                                <td className="px-4 py-3 text-right font-mono font-semibold text-slate-900">
                                                    <MoneyDisplay value={line.line_total} />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {/* Deductions & Statutory Adjustments Table */}
                        {preview.adjustments.length > 0 && (
                            <div className="p-6 pt-0">
                                <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                                    Statutory Tax & Fleet Chargeback Deductions
                                </h4>
                                <div className="overflow-x-auto border border-amber-200 rounded-lg">
                                    <table className="min-w-full divide-y divide-amber-100 text-sm">
                                        <thead className="bg-amber-50 text-amber-900 text-xs font-semibold uppercase tracking-wider">
                                            <tr>
                                                <th className="px-4 py-2 text-left">Adjustment / Recovery</th>
                                                <th className="px-4 py-2 text-left">Type</th>
                                                <th className="px-4 py-2 text-right">Deducted Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-amber-50 bg-white">
                                            {preview.adjustments.map((adj, idx) => (
                                                <tr key={idx}>
                                                    <td className="px-4 py-2.5 font-medium text-slate-800">{adj.name}</td>
                                                    <td className="px-4 py-2.5 text-xs text-slate-500 capitalize">{adj.type}</td>
                                                    <td className="px-4 py-2.5 text-right font-mono font-semibold text-rose-600">
                                                        -<MoneyDisplay value={adj.amount} />
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* Settlement Summary Breakdown Card */}
                        <div className="p-6 bg-slate-50/80 border-t border-slate-200">
                            <div className="flex flex-col md:flex-row justify-between gap-6">
                                <div className="text-xs text-slate-500 max-w-sm space-y-1">
                                    <div className="font-semibold text-slate-700">Statutory & Reconciliation Policy</div>
                                    <p>
                                        Under Inland Revenue Act statutory regulations, {preview.wht_rate}% Withholding Tax is deducted directly from gross lease rent. Fleet maintenance chargebacks are capped at non-taxable net capacity.
                                    </p>
                                </div>

                                <div className="w-full md:w-80 space-y-2 text-sm">
                                    <div className="flex justify-between text-slate-600">
                                        <span>Gross Lease Rent & Usage:</span>
                                        <span className="font-mono"><MoneyDisplay value={preview.financial_breakdown.gross_payable} /></span>
                                    </div>
                                    <div className="flex justify-between text-rose-600">
                                        <span>Statutory Withholding Tax ({preview.wht_rate}%):</span>
                                        <span className="font-mono">-<MoneyDisplay value={preview.financial_breakdown.wht_amount} /></span>
                                    </div>
                                    {parseFloat(preview.financial_breakdown.maintenance_deductions || '0') > 0 && (
                                        <div className="flex justify-between text-rose-600">
                                            <span>Fleet Maintenance Recoveries:</span>
                                            <span className="font-mono">-<MoneyDisplay value={preview.financial_breakdown.maintenance_deductions} /></span>
                                        </div>
                                    )}
                                    <div className="flex justify-between font-semibold text-slate-700 pt-1 border-t border-slate-200">
                                        <span>Total Deductions:</span>
                                        <span className="font-mono text-rose-600">-<MoneyDisplay value={preview.financial_breakdown.total_deductions} /></span>
                                    </div>
                                    <div className="flex justify-between font-bold text-base text-purple-600 pt-2 border-t-2 border-purple-500">
                                        <span>Net Settlement Payable:</span>
                                        <span className="font-mono"><MoneyDisplay value={preview.financial_breakdown.net_payable} /></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Footer Actions */}
                <div className="flex items-center justify-between border-t border-slate-200 pt-4">
                    <span className="text-xs text-slate-500">
                        ⚡ Live preview calculates base lease, usage, statutory 10% WHT, and maintenance chargebacks without saving records.
                    </span>
                    <div className="flex gap-2">
                        {preview && onProceedToGenerateBill && selectedAgreement && (
                            <Button
                                variant="primary"
                                onClick={() => {
                                    onProceedToGenerateBill(selectedAgreement.id, startDate, endDate, whtRate);
                                    onClose();
                                }}
                            >
                                Proceed to Generate Bill
                            </Button>
                        )}
                        <Button variant="secondary" onClick={onClose}>
                            Close
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>
    );
}

export { RentalOwnerBillPreviewModal as OwnerBillPreviewModal };
