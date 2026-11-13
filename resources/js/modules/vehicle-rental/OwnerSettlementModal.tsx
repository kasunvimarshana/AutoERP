import React, { useState } from 'react';
import { Modal } from '@/shared/components/Modal';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { generateOwnerSettlementBill } from './agreementApi';

interface Props {
    open: boolean;
    onClose: () => void;
    ownerAgreementId: number;
    ownerAgreementReference?: string;
    defaultStartDate?: string;
    defaultEndDate?: string;
    onBillGenerated?: (bill: { id: number; invoice_number: string }) => void;
}

export function OwnerSettlementModal({
    open,
    onClose,
    ownerAgreementId,
    ownerAgreementReference,
    defaultStartDate,
    defaultEndDate,
    onBillGenerated,
}: Props) {
    const today = businessDateInputValue();
    const startOfMonth = today.slice(0, 7) + '-01';

    const [startDate, setStartDate] = useState(defaultStartDate ?? startOfMonth);
    const [endDate, setEndDate] = useState(defaultEndDate ?? today);
    const [whtRate, setWhtRate] = useState('10');
    const [invoiceDate, setInvoiceDate] = useState(today);
    const [dueDate, setDueDate] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [generatedBill, setGeneratedBill] = useState<{ id: number; invoice_number: string } | null>(null);

    async function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        setSubmitting(true);
        setError(null);
        setGeneratedBill(null);

        try {
            const bill = await generateOwnerSettlementBill({
                owner_agreement_id: ownerAgreementId,
                start_date: startDate,
                end_date: endDate,
                wht_rate: parseFloat(whtRate) || 10,
                invoice_date: invoiceDate,
                due_date: dueDate || null,
            });

            setGeneratedBill(bill);
            if (onBillGenerated) {
                onBillGenerated(bill);
            }
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setSubmitting(false);
        }
    }

    const title = ownerAgreementReference
        ? `Generate Inbound AP Supplier Bill · ${ownerAgreementReference}`
        : 'Generate Inbound AP Supplier Bill';

    return (
        <Modal open={open} title={title} onClose={onClose} closeDisabled={submitting}>
            {generatedBill ? (
                <div className="space-y-4">
                    <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
                        <h4 className="font-bold text-sm">Supplier Purchase Invoice Created</h4>
                        <p className="text-xs mt-1">
                            Official Accounts Payable Inbound Bill <span className="font-bold">{generatedBill.invoice_number}</span> has been created with base rent disbursements, 10% Withholding Tax deduction, and confirmed maintenance expense offsets.
                        </p>
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="primary"
                            onClick={() => {
                                setGeneratedBill(null);
                                onClose();
                            }}
                        >
                            Done
                        </Button>
                    </div>
                </div>
            ) : (
                <form onSubmit={handleSubmit} className="space-y-4">
                    <ErrorAlert error={error} inline />

                    <p className="text-xs text-slate-600">
                        Generates an official Inbound Purchase Invoice (Supplier Bill) in Accounts Payable. This converts the owner's net disbursement statement into a formal accounting payable, deducting statutory Withholding Tax (WHT) and owner-allocated fleet maintenance chargebacks.
                    </p>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <Input
                            label="Settlement Period Start"
                            type="date"
                            value={startDate}
                            onChange={e => setStartDate(e.target.value)}
                            required
                        />
                        <Input
                            label="Settlement Period End"
                            type="date"
                            value={endDate}
                            onChange={e => setEndDate(e.target.value)}
                            required
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <Input
                            label="WHT Rate (%)"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            value={whtRate}
                            onChange={e => setWhtRate(e.target.value)}
                            required
                        />
                        <Input
                            label="Bill Invoice Date"
                            type="date"
                            value={invoiceDate}
                            onChange={e => setInvoiceDate(e.target.value)}
                            required
                        />
                        <Input
                            label="Payment Due Date"
                            type="date"
                            value={dueDate}
                            onChange={e => setDueDate(e.target.value)}
                        />
                    </div>

                    <div className="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" loading={submitting}>
                            Generate Inbound AP Bill
                        </Button>
                    </div>
                </form>
            )}
        </Modal>
    );
}
