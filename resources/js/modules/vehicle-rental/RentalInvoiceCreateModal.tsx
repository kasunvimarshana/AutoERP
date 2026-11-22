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
import { AgreementKind, type Agreement } from './agreements';
import { getAgreement, searchCustomerAgreements } from './agreementApi';
import { billBaseRent } from './baseRentBillingApi';
import { BillingCycleWizardModal } from './BillingCycleWizardModal';

interface Props {
    open: boolean;
    onClose: () => void;
    initialMode?: 'cycle' | 'base';
    onInvoiceCreated?: () => void;
}

export function RentalInvoiceCreateModal({
    open,
    onClose,
    initialMode = 'cycle',
    onInvoiceCreated,
}: Props) {
    const today = businessDateInputValue();
    const startOfMonth = today.slice(0, 7) + '-01';

    const [mode, setMode] = useState<'cycle' | 'base'>(initialMode);
    const [selectedLookup, setSelectedLookup] = useState<NamedResource | null>(null);
    const [agreement, setAgreement] = useState<Agreement | null>(null);
    const [loadingAgreement, setLoadingAgreement] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    // Wizard modal state for billing cycle
    const [openWizard, setOpenWizard] = useState(false);

    // Base rent form state
    const [periodFrom, setPeriodFrom] = useState(startOfMonth);
    const [periodUntil, setPeriodUntil] = useState(today);
    const [invoiceDate, setInvoiceDate] = useState(today);
    const [dueDate, setDueDate] = useState('');
    const [submittingBase, setSubmittingBase] = useState(false);
    const [createdBaseInvoice, setCreatedBaseInvoice] = useState<{ id: number; invoice_number: string; grand_total: string } | null>(null);

    useEffect(() => {
        setMode(initialMode);
    }, [initialMode]);

    useEffect(() => {
        if (!selectedLookup) {
            setAgreement(null);
            return;
        }

        let active = true;
        setLoadingAgreement(true);
        setError(null);

        getAgreement(AgreementKind.Customer, selectedLookup.id)
            .then(ag => {
                if (active) {
                    setAgreement(ag);
                    if (ag.starts_on) {
                        setPeriodFrom(ag.starts_on);
                    }
                }
            })
            .catch(err => {
                if (active) {
                    setError(toApiError(err));
                }
            })
            .finally(() => {
                if (active) {
                    setLoadingAgreement(false);
                }
            });

        return () => {
            active = false;
        };
    }, [selectedLookup]);

    async function handleBaseRentSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (!agreement) {
            setError(toApiError(new Error('Select an active customer agreement first.')));
            return;
        }

        setSubmittingBase(true);
        setError(null);

        try {
            const invoice = await billBaseRent(
                AgreementKind.Customer,
                agreement,
                { from: periodFrom, until: periodUntil },
                { invoice_date: invoiceDate, due_date: dueDate || null, exchange_rate: '1' }
            );

            setCreatedBaseInvoice(invoice);
            if (onInvoiceCreated) {
                onInvoiceCreated();
            }
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setSubmittingBase(false);
        }
    }

    function handleResetAndClose() {
        setSelectedLookup(null);
        setAgreement(null);
        setCreatedBaseInvoice(null);
        setError(null);
        onClose();
    }

    if (openWizard && agreement) {
        return (
            <BillingCycleWizardModal
                open={openWizard}
                onClose={() => {
                    setOpenWizard(false);
                    handleResetAndClose();
                }}
                kind={AgreementKind.Customer}
                agreement={agreement}
                onCycleUpdated={() => {
                    if (onInvoiceCreated) {
                        onInvoiceCreated();
                    }
                }}
            />
        );
    }

    return (
        <Modal
            open={open}
            title={mode === 'cycle' ? 'Generate Consolidated Billing Cycle Invoice' : 'Issue Advance Base Rent Invoice'}
            onClose={handleResetAndClose}
            closeDisabled={submittingBase || loadingAgreement}
        >
            {createdBaseInvoice ? (
                <div className="space-y-4">
                    <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
                        <h4 className="font-bold text-sm">Customer Invoice Issued Successfully</h4>
                        <p className="text-xs mt-1">
                            Sales Invoice <span className="font-bold">{createdBaseInvoice.invoice_number}</span> has been issued for{' '}
                            <MoneyDisplay value={createdBaseInvoice.grand_total} />.
                        </p>
                    </div>
                    <div className="flex justify-end">
                        <Button type="button" variant="primary" onClick={handleResetAndClose}>
                            Done
                        </Button>
                    </div>
                </div>
            ) : (
                <div className="space-y-4">
                    <ErrorAlert error={error} inline />

                    {/* Mode Toggle */}
                    <div className="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-lg text-xs font-semibold">
                        <button
                            type="button"
                            onClick={() => setMode('cycle')}
                            className={`py-2 px-3 rounded-md transition-all text-center ${
                                mode === 'cycle'
                                    ? 'bg-white shadow text-slate-900 font-bold'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            Consolidated Billing Cycle
                        </button>
                        <button
                            type="button"
                            onClick={() => setMode('base')}
                            className={`py-2 px-3 rounded-md transition-all text-center ${
                                mode === 'base'
                                    ? 'bg-white shadow text-slate-900 font-bold'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            Advance Base Rent
                        </button>
                    </div>

                    <p className="text-xs text-slate-600">
                        {mode === 'cycle'
                            ? 'Aggregates vehicle usage, running charts, excess kilometers, driver overtime, night allowances, and recurring rent into a formal two-stage billing cycle invoice.'
                            : 'Generates an immediate advance invoice for fixed contract base rent for a defined calendar period without finalizing running charts.'}
                    </p>

                    <LookupSelect
                        label="Customer Agreement"
                        value={selectedLookup}
                        onChange={setSelectedLookup}
                        search={searchCustomerAgreements}
                        placeholder="Search active agreement by reference or customer name..."
                        loadOnOpen
                        required
                    />

                    {loadingAgreement && <LoadingState label="Loading agreement context..." />}

                    {agreement && mode === 'cycle' && (
                        <div className="rounded-xl border border-blue-200 bg-blue-50/70 p-4 space-y-3">
                            <div className="flex items-center justify-between text-xs text-blue-900">
                                <div>
                                    <span className="font-bold">Agreement:</span> {agreement.reference}
                                </div>
                                <div>
                                    <span className="font-bold">Basis:</span> {agreement.basis}
                                </div>
                            </div>
                            <p className="text-xs text-blue-800">
                                Ready to configure cycle period, attach eligible finalized running charts, itemize charges, and generate the billing cycle document.
                            </p>
                            <div className="flex justify-end pt-2">
                                <Button
                                    type="button"
                                    variant="primary"
                                    onClick={() => setOpenWizard(true)}
                                >
                                    Launch Cycle Wizard &rarr;
                                </Button>
                            </div>
                        </div>
                    )}

                    {agreement && mode === 'base' && (
                        <form onSubmit={handleBaseRentSubmit} className="space-y-4 pt-2 border-t border-slate-100">
                            <div className="rounded-lg border border-blue-200 bg-blue-50/60 p-3 text-xs text-blue-950 space-y-1">
                                <p className="font-semibold text-blue-900">Base Rent Billing Scope:</p>
                                <p className="text-blue-800">
                                    Issues an advance invoice for fixed contract base rent across the selected coverage span. Variable excess mileage, overtime, and toll adjustments are consolidated separately via the Billing Cycle Wizard.
                                </p>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Input
                                    label="Coverage Period From"
                                    type="date"
                                    value={periodFrom}
                                    onChange={e => setPeriodFrom(e.target.value)}
                                    hint="Coverage start date"
                                    required
                                />
                                <Input
                                    label="Coverage Period Until"
                                    type="date"
                                    value={periodUntil}
                                    onChange={e => setPeriodUntil(e.target.value)}
                                    hint="Coverage end date"
                                    required
                                />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Input
                                    label="Invoice Date"
                                    type="date"
                                    value={invoiceDate}
                                    onChange={e => setInvoiceDate(e.target.value)}
                                    hint="Sales ledger posting date"
                                    required
                                />
                                <Input
                                    label="Due Date"
                                    type="date"
                                    value={dueDate}
                                    onChange={e => setDueDate(e.target.value)}
                                    hint="Customer payment due date"
                                />
                            </div>

                            <div className="flex justify-end gap-2 pt-2 border-t border-slate-100">
                                <Button type="button" variant="secondary" onClick={handleResetAndClose} disabled={submittingBase}>
                                    Cancel
                                </Button>
                                <Button type="submit" variant="primary" loading={submittingBase}>
                                    Issue Base Rent Invoice
                                </Button>
                            </div>
                        </form>
                    )}

                    {!agreement && (
                        <div className="flex justify-end gap-2 pt-2">
                            <Button type="button" variant="secondary" onClick={handleResetAndClose}>
                                Close
                            </Button>
                        </div>
                    )}
                </div>
            )}
        </Modal>
    );
}
