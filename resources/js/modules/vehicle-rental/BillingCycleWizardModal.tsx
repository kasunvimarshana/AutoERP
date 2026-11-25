/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useEffect, useRef, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { Modal } from '@/shared/components/Modal';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Tabs } from '@/shared/components/Tabs';
import { businessDateInputValue, formatBusinessDate } from '@/shared/utils/businessDate';
import { AgreementKind, type Agreement } from './agreements';
import {
    createBillingCycle,
    getBillingCycle,
    attachCycleCharts,
    addCycleItem,
    removeCycleItem,
    previewCycleFinal,
    finalizeBillingCycle,
    getEligibleCycleCharts,
    getBillingCycleExportUrl,
    type BillingCycle,
    type BillingCycleItem,
    type BillingCyclePreview,
    type EligibleRunningChart,
} from './agreementApi';

interface Props {
    open: boolean;
    onClose: () => void;
    kind: AgreementKind;
    agreement: Agreement;
    cycle?: BillingCycle | null;
    onCycleUpdated?: (cycle: BillingCycle) => void;
}

type WizardTab = 'overview' | 'charts' | 'items' | 'finalize';

export function BillingCycleWizardModal({
    open,
    onClose,
    kind,
    agreement,
    cycle: initialCycle,
    onCycleUpdated,
}: Props) {
    const [currentCycle, setCurrentCycle] = useState<BillingCycle | null>(initialCycle ?? null);
    const [activeTab, setActiveTab] = useState<WizardTab>('overview');
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [feedback, setFeedback] = useState<string | null>(null);

    // Create Cycle Form State
    const today = businessDateInputValue();
    const [periodFrom, setPeriodFrom] = useState(agreement.starts_on ?? today);
    const [periodUntil, setPeriodUntil] = useState(agreement.ends_on ?? today);
    const [billingMode, setBillingMode] = useState('without_running_chart');
    const [finalizationMode, setFinalizationMode] = useState('supplementary_invoice');
    const [issueInitialInvoice, setIssueInitialInvoice] = useState(true);
    const [invoiceDate, setInvoiceDate] = useState(today);
    const [dueDate, setDueDate] = useState('');
    const [notes, setNotes] = useState('');

    // Attach Charts State
    const [candidateCharts, setCandidateCharts] = useState<EligibleRunningChart[]>([]);
    const [candidateLoading, setCandidateLoading] = useState(false);
    const [selectedChartIds, setSelectedChartIds] = useState<Set<number>>(
        () => new Set(initialCycle?.charts?.map(c => c.running_chart_id) ?? [])
    );

    // Add Item State
    const [itemType, setItemType] = useState<'addition' | 'deduction'>('addition');
    const [category, setCategory] = useState('driver_salary');
    const [description, setDescription] = useState('');
    const [quantity, setQuantity] = useState('1.000');
    const [unitRate, setUnitRate] = useState('');
    const [itemVehicleId, setItemVehicleId] = useState('');
    const [itemDriverId, setItemDriverId] = useState('');

    // Finalize / Preview State
    const [preview, setPreview] = useState<BillingCyclePreview | null>(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [finalizeMode, setFinalizeMode] = useState<'supplementary' | 'consolidated'>(
        () => (initialCycle?.finalization_mode as 'supplementary' | 'consolidated') || 'supplementary'
    );
    const [finalInvoiceDate, setFinalInvoiceDate] = useState(today);
    const [finalDueDate, setFinalDueDate] = useState('');

    const prevInitialCycleIdRef = useRef(initialCycle?.id);
    useEffect(() => {
        if (prevInitialCycleIdRef.current === initialCycle?.id) return;
        prevInitialCycleIdRef.current = initialCycle?.id;
        queueMicrotask(() => {
            setCurrentCycle(initialCycle ?? null);
            if (initialCycle) {
                setActiveTab('overview');
                setFinalizeMode((initialCycle.finalization_mode as 'supplementary' | 'consolidated') || 'supplementary');
                if (initialCycle.charts) {
                    setSelectedChartIds(new Set(initialCycle.charts.map(c => c.running_chart_id)));
                }
            } else {
                setPeriodFrom(agreement.starts_on ?? today);
                setPeriodUntil(agreement.ends_on ?? today);
            }
        });
    }, [initialCycle, agreement, today]);

    const cur = agreement.currency.code;

    const availableVehicles = React.useMemo(() => {
        const map = new Map<number, string>();
        if (candidateCharts) {
            for (const c of candidateCharts) {
                if (c.vehicle_id) map.set(c.vehicle_id, c.vehicle_label || c.vehicle_number);
            }
        }
        if (currentCycle?.charts) {
            for (const c of currentCycle.charts) {
                if (c.vehicle_id) {
                    const label = c.vehicle?.registration_number || c.vehicle?.vehicle_number || `Vehicle #${c.vehicle_id}`;
                    map.set(c.vehicle_id, label);
                }
            }
        }
        if (preview?.vehicle_attributions) {
            for (const va of preview.vehicle_attributions) {
                map.set(va.vehicle_id, va.vehicle_label || va.vehicle_number);
            }
        }
        return Array.from(map.entries()).map(([id, label]) => ({ id, label }));
    }, [candidateCharts, currentCycle, preview]);

    const availableDrivers = React.useMemo(() => {
        const map = new Map<number, string>();
        if (preview?.driver_attributions) {
            for (const da of preview.driver_attributions) {
                if (da.driver_employee_id) {
                    map.set(da.driver_employee_id, `${da.driver_name}${da.driver_reference ? ` (${da.driver_reference})` : ''}`);
                }
            }
        }
        return Array.from(map.entries()).map(([id, label]) => ({ id, label }));
    }, [preview]);

    async function loadCandidates(cycleId: number) {
        setCandidateLoading(true);
        try {
            const list = await getEligibleCycleCharts(cycleId);
            setCandidateCharts(list);
            const attached = new Set(list.filter(c => c.is_attached).map(c => c.id));
            if (attached.size > 0) {
                setSelectedChartIds(attached);
            }
        } catch {
            // graceful fallback
        } finally {
            setCandidateLoading(false);
        }
    }

    async function reloadCycle(cycleId: number) {
        setLoading(true);
        try {
            const updated = await getBillingCycle(cycleId);
            setCurrentCycle(updated);
            if (onCycleUpdated) {
                onCycleUpdated(updated);
            }
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setLoading(false);
        }
    }

    async function handleCreateCycle(e: React.FormEvent) {
        e.preventDefault();
        setSubmitting(true);
        setError(null);
        setFeedback(null);

        try {
            const created = await createBillingCycle(kind, agreement.id, {
                period_from: periodFrom,
                period_until: periodUntil,
                billing_mode: billingMode,
                finalization_mode: finalizationMode,
                issue_initial_invoice: issueInitialInvoice,
                invoice_date: issueInitialInvoice ? invoiceDate : undefined,
                due_date: issueInitialInvoice && dueDate ? dueDate : null,
                notes: notes.trim() || undefined,
            });
            setCurrentCycle(created);
            setFeedback(`Billing Cycle ${created.cycle_number} created successfully.`);
            if (onCycleUpdated) {
                onCycleUpdated(created);
            }
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setSubmitting(false);
        }
    }

    async function handleAttachCharts(e?: React.FormEvent) {
        if (e) e.preventDefault();
        if (!currentCycle) return;

        const ids = Array.from(selectedChartIds);

        if (ids.length === 0) {
            setError(toApiError(new Error('Please select at least one Running Chart to attach.')));
            return;
        }

        setSubmitting(true);
        setError(null);
        try {
            const updated = await attachCycleCharts(currentCycle.id, ids, currentCycle.row_version);
            setCurrentCycle(updated);
            setFeedback('Running charts attached successfully.');
            if (onCycleUpdated) onCycleUpdated(updated);
            await loadCandidates(currentCycle.id);
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setSubmitting(false);
        }
    }

    async function handleAddItem(e: React.FormEvent) {
        e.preventDefault();
        if (!currentCycle || !description.trim() || !unitRate) return;

        setSubmitting(true);
        setError(null);
        try {
            await addCycleItem(currentCycle.id, {
                item_type: itemType,
                category,
                description: description.trim(),
                quantity: quantity || '1.000',
                unit_rate: unitRate,
                vehicle_id: itemVehicleId ? Number(itemVehicleId) : undefined,
                driver_employee_id: itemDriverId ? Number(itemDriverId) : undefined,
            }, currentCycle.row_version);

            setDescription('');
            setUnitRate('');
            setQuantity('1.000');
            setItemVehicleId('');
            setItemDriverId('');
            setFeedback('Item added successfully.');
            await reloadCycle(currentCycle.id);
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setSubmitting(false);
        }
    }

    async function handleRemoveItem(item: BillingCycleItem) {
        if (!currentCycle) return;
        setSubmitting(true);
        setError(null);
        try {
            await removeCycleItem(currentCycle.id, item.id, currentCycle.row_version);
            setFeedback('Item removed.');
            await reloadCycle(currentCycle.id);
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setSubmitting(false);
        }
    }

    async function loadFinalPreview() {
        if (!currentCycle) return;
        setPreviewLoading(true);
        setError(null);
        try {
            const data = await previewCycleFinal(currentCycle.id);
            setPreview(data);
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setPreviewLoading(false);
        }
    }

    async function handleFinalize(e: React.FormEvent) {
        e.preventDefault();
        if (!currentCycle) return;

        setSubmitting(true);
        setError(null);
        try {
            const updated = await finalizeBillingCycle(currentCycle.id, {
                finalization_mode: finalizeMode,
                invoice_date: finalInvoiceDate,
                due_date: finalDueDate || null,
            }, currentCycle.row_version);

            setCurrentCycle(updated);
            setFeedback(`Cycle finalized successfully in ${finalizeMode === 'supplementary' ? 'Supplementary' : 'Consolidated Revision'} mode.`);
            if (onCycleUpdated) onCycleUpdated(updated);
        } catch (err) {
            setError(toApiError(err));
        } finally {
            setSubmitting(false);
        }
    }

    const title = currentCycle
        ? `Billing Cycle ${currentCycle.cycle_number} (${formatBusinessDate(currentCycle.period_from)} – ${formatBusinessDate(currentCycle.period_until)})`
        : `New Billing Cycle for ${agreement.reference}`;

    const isFinalized = currentCycle?.status === 'finalized';

    return (
        <Modal open={open} title={title} onClose={onClose} closeDisabled={submitting}>
            <div className="space-y-4">
                <ErrorAlert error={error} inline />
                {feedback && (
                    <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900 flex items-center justify-between">
                        <span>{feedback}</span>
                        <Button variant="ghost" onClick={() => setFeedback(null)}>Dismiss</Button>
                    </div>
                )}

                {!currentCycle ? (
                    /* Initial Create Cycle Form */
                    <form onSubmit={handleCreateCycle} className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Input
                                label="Billing Period Start"
                                type="date"
                                value={periodFrom}
                                onChange={e => setPeriodFrom(e.target.value)}
                                required
                            />
                            <Input
                                label="Billing Period End"
                                type="date"
                                value={periodUntil}
                                onChange={e => setPeriodUntil(e.target.value)}
                                required
                            />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Select
                                label="Billing Mode"
                                value={billingMode}
                                onChange={e => setBillingMode(e.target.value)}
                                options={[
                                    { value: 'without_running_chart', label: 'Provisional Base Rent Invoicing (Without Running Charts)' },
                                    { value: 'with_running_chart', label: 'Direct Consolidated Invoicing (With Running Charts)' },
                                ]}
                                required
                            />
                            <Select
                                label="Finalization Mode Strategy"
                                value={finalizationMode}
                                onChange={e => setFinalizationMode(e.target.value)}
                                options={[
                                    { value: 'supplementary_invoice', label: 'Mode A: Supplementary Usage Invoice' },
                                    { value: 'consolidated_revision', label: 'Mode B: Consolidated Revision & Credit Note' },
                                ]}
                                required
                            />
                        </div>

                        <div className="rounded-xl border border-blue-200 bg-blue-50/50 p-4 space-y-3">
                            <label className="flex items-center gap-2 cursor-pointer text-sm font-semibold text-slate-900">
                                <input
                                    type="checkbox"
                                    checked={issueInitialInvoice}
                                    onChange={e => setIssueInitialInvoice(e.target.checked)}
                                    className="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                />
                                Issue Initial Base Rent Invoice (Stage 1) Immediately
                            </label>
                            <p className="text-xs text-slate-600">
                                Generating an initial Stage 1 invoice creates the formal receivable for base rent. Running charts, driver overtime, excess mileage, and deductions can then be attached later to finalize Stage 2.
                            </p>

                            {issueInitialInvoice && (
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 pt-2 border-t border-blue-100">
                                    <Input
                                        label="Invoice Date"
                                        type="date"
                                        value={invoiceDate}
                                        onChange={e => setInvoiceDate(e.target.value)}
                                        required
                                    />
                                    <Input
                                        label="Due Date (Optional)"
                                        type="date"
                                        value={dueDate}
                                        onChange={e => setDueDate(e.target.value)}
                                    />
                                </div>
                            )}
                        </div>

                        <Textarea
                            label="Cycle Notes / Instructions"
                            value={notes}
                            onChange={e => setNotes(e.target.value)}
                            placeholder="Optional operational notes..."
                        />

                        <div className="flex justify-end gap-2 pt-2 border-t border-slate-100">
                            <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>
                                Cancel
                            </Button>
                            <Button type="submit" variant="primary" loading={submitting}>
                                Initialize Billing Cycle
                            </Button>
                        </div>
                    </form>
                ) : (
                    /* Existing Cycle Management */
                    <div className="space-y-4">
                        {/* Sub-tabs */}
                        <Tabs<WizardTab>
                            variant="pills"
                            tabs={[
                                { id: 'overview', label: 'Overview' },
                                { id: 'charts', label: 'Charts' },
                                { id: 'items', label: 'Additions & Deductions' },
                                { id: 'finalize', label: 'Finalize' },
                            ]}
                            active={activeTab}
                            onChange={(tab) => {
                                setActiveTab(tab);
                                if (tab === 'charts') {
                                    loadCandidates(currentCycle.id);
                                }
                                if (tab === 'finalize') {
                                    loadFinalPreview();
                                }
                            }}
                        />

                        {loading && <LoadingState label="Refreshing cycle data..." />}

                        {/* Tab 1: Overview */}
                        {activeTab === 'overview' && !loading && (
                            <div className="space-y-4">
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div className="rounded-xl border border-slate-200 bg-white p-3 shadow-xs">
                                        <span className="text-xs text-slate-500 uppercase tracking-wider font-semibold">Status</span>
                                        <div className="mt-1"><StatusBadge status={currentCycle.status} /></div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 bg-white p-3 shadow-xs">
                                        <span className="text-xs text-slate-500 uppercase tracking-wider font-semibold">Mode</span>
                                        <div className="mt-1 font-semibold text-slate-800 capitalize">{currentCycle.billing_mode}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 bg-white p-3 shadow-xs">
                                        <span className="text-xs text-slate-500 uppercase tracking-wider font-semibold">Base Rent</span>
                                        <div className="mt-1 font-bold text-slate-900">
                                            <MoneyDisplay value={currentCycle.base_rent_amount} currency={cur} />
                                        </div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 bg-white p-3 shadow-xs">
                                        <span className="text-xs text-slate-500 uppercase tracking-wider font-semibold">Final Net Total</span>
                                        <div className="mt-1 font-bold text-blue-700">
                                            <MoneyDisplay value={currentCycle.total_billed_amount || currentCycle.base_rent_amount} currency={cur} />
                                        </div>
                                    </div>
                                </div>

                                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-xs space-y-3">
                                    <div className="flex items-center justify-between">
                                        <h4 className="text-sm font-bold text-slate-900">Generated Financial Invoices</h4>
                                        <div className="flex gap-2">
                                            <Button
                                                variant="secondary"
                                                onClick={() => window.open(getBillingCycleExportUrl(currentCycle.id, 'pdf'), '_blank')}
                                            >
                                                Statement (PDF)
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                onClick={() => window.open(getBillingCycleExportUrl(currentCycle.id, 'print'), '_blank')}
                                            >
                                                Print
                                            </Button>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <div className="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                            <span className="text-xs font-semibold text-slate-500 block">Stage 1 Initial Invoice</span>
                                            {currentCycle.initial_invoice ? (
                                                <div className="mt-1">
                                                    <span className="font-bold text-blue-800">{currentCycle.initial_invoice.invoice_number}</span>
                                                    <div className="text-xs text-slate-600 mt-0.5">
                                                        Total: <MoneyDisplay value={currentCycle.initial_invoice.grand_total} currency={cur} />
                                                    </div>
                                                    <div className="mt-1"><StatusBadge status={currentCycle.initial_invoice.status} /></div>
                                                </div>
                                            ) : (
                                                <span className="text-xs text-slate-400 italic">No initial invoice issued</span>
                                            )}
                                        </div>

                                        <div className="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                            <span className="text-xs font-semibold text-slate-500 block">Stage 2 Supplementary Invoice</span>
                                            {currentCycle.supplementary_invoice ? (
                                                <div className="mt-1">
                                                    <span className="font-bold text-indigo-800">{currentCycle.supplementary_invoice.invoice_number}</span>
                                                    <div className="text-xs text-slate-600 mt-0.5">
                                                        Total: <MoneyDisplay value={currentCycle.supplementary_invoice.grand_total} currency={cur} />
                                                    </div>
                                                    <div className="mt-1"><StatusBadge status={currentCycle.supplementary_invoice.status} /></div>
                                                </div>
                                            ) : (
                                                <span className="text-xs text-slate-400 italic">None</span>
                                            )}
                                        </div>

                                        <div className="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                            <span className="text-xs font-semibold text-slate-500 block">Stage 2 Final / Revision Invoice</span>
                                            {currentCycle.final_invoice ? (
                                                <div className="mt-1">
                                                    <span className="font-bold text-emerald-800">{currentCycle.final_invoice.invoice_number}</span>
                                                    <div className="text-xs text-slate-600 mt-0.5">
                                                        Total: <MoneyDisplay value={currentCycle.final_invoice.grand_total} currency={cur} />
                                                    </div>
                                                    <div className="mt-1"><StatusBadge status={currentCycle.final_invoice.status} /></div>
                                                </div>
                                            ) : (
                                                <span className="text-xs text-slate-400 italic">Pending finalization</span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Tab 2: Running Charts */}
                        {activeTab === 'charts' && !loading && (
                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <h4 className="text-sm font-bold text-slate-900">
                                        Attach Telematics Running Charts
                                    </h4>
                                    {!isFinalized && candidateCharts.length > 0 && (
                                        <div className="flex gap-2">
                                            <Button
                                                variant="ghost"
                                                onClick={() => {
                                                    const allAvailable = candidateCharts
                                                        .filter(c => !c.is_already_finalized)
                                                        .map(c => c.id);
                                                    setSelectedChartIds(new Set(allAvailable));
                                                }}
                                            >
                                                Select All Available
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                onClick={() => setSelectedChartIds(new Set())}
                                            >
                                                Deselect All
                                            </Button>
                                        </div>
                                    )}
                                </div>

                                {!isFinalized && candidateLoading && (
                                    <LoadingState label="Loading eligible running charts for period..." />
                                )}

                                {!isFinalized && !candidateLoading && candidateCharts.length > 0 && (
                                    <div className="space-y-3 rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                                        <span className="text-xs font-bold text-slate-700 block">
                                            Select Candidate Trips to Pool into Cycle ({selectedChartIds.size} selected)
                                        </span>

                                        <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white max-h-64 overflow-y-auto">
                                            <table className="w-full text-left text-xs">
                                                <thead className="sticky top-0 bg-slate-100 border-b border-slate-200 font-semibold text-slate-600">
                                                    <tr>
                                                        <th className="px-3 py-2 w-8"></th>
                                                        <th className="px-3 py-2">Chart #</th>
                                                        <th className="px-3 py-2">Dates</th>
                                                        <th className="px-3 py-2">Vehicle</th>
                                                        <th className="px-3 py-2">Driver</th>
                                                        <th className="px-3 py-2 text-right">KM</th>
                                                        <th className="px-3 py-2 text-right">OT (min)</th>
                                                        <th className="px-3 py-2">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-slate-100">
                                                    {candidateCharts.map(c => {
                                                        const isChecked = selectedChartIds.has(c.id);
                                                        return (
                                                            <tr key={c.id} className={c.is_already_finalized ? 'opacity-40 bg-slate-50' : ''}>
                                                                <td className="px-3 py-2 text-center">
                                                                    <input
                                                                        type="checkbox"
                                                                        checked={isChecked}
                                                                        disabled={c.is_already_finalized}
                                                                        onChange={e => {
                                                                            const next = new Set(selectedChartIds);
                                                                            if (e.target.checked) next.add(c.id);
                                                                            else next.delete(c.id);
                                                                            setSelectedChartIds(next);
                                                                        }}
                                                                        className="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                                    />
                                                                </td>
                                                                <td className="px-3 py-2 font-bold text-slate-900">{c.reference}</td>
                                                                <td className="px-3 py-2 text-slate-600">{formatBusinessDate(c.starts_at)}</td>
                                                                <td className="px-3 py-2">
                                                                    <span className="font-medium text-slate-800">{c.vehicle_label}</span>
                                                                    {c.is_replacement && (
                                                                        <span className="ml-1 px-1 py-0.2 rounded bg-amber-100 text-amber-800 text-[9px] font-bold">
                                                                            Replacement
                                                                        </span>
                                                                    )}
                                                                </td>
                                                                <td className="px-3 py-2 text-slate-700">{c.driver_name}</td>
                                                                <td className="px-3 py-2 text-right font-medium">{c.commercial_km}</td>
                                                                <td className="px-3 py-2 text-right">{c.normal_ot_minutes + c.double_ot_minutes + (c.triple_ot_minutes ?? 0)}</td>
                                                                <td className="px-3 py-2">
                                                                    {c.is_already_finalized ? (
                                                                        <span className="text-[10px] text-slate-500 italic">Billed elsewhere</span>
                                                                    ) : c.is_attached ? (
                                                                        <span className="text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">Attached</span>
                                                                    ) : (
                                                                        <span className="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-semibold">Available</span>
                                                                    )}
                                                                </td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                            </table>
                                        </div>

                                        <div className="flex justify-end items-center pt-2">
                                            <Button
                                                type="button"
                                                variant="primary"
                                                onClick={() => handleAttachCharts()}
                                                loading={submitting}
                                                disabled={selectedChartIds.size === 0}
                                            >
                                                Confirm Attached Charts ({selectedChartIds.size})
                                            </Button>
                                        </div>
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <h5 className="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Currently Attached Charts ({currentCycle.charts?.length ?? 0})
                                    </h5>
                                    <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                        <table className="w-full text-left text-xs">
                                            <thead>
                                                <tr className="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500">
                                                    <th className="px-3 py-2">Chart #</th>
                                                    <th className="px-3 py-2">Vehicle</th>
                                                    <th className="px-3 py-2">Driver</th>
                                                    <th className="px-3 py-2">Commercial KM</th>
                                                    <th className="px-3 py-2">Normal OT (min)</th>
                                                    <th className="px-3 py-2">Double OT (min)</th>
                                                    <th className="px-3 py-2">Night Outs</th>
                                                    <th className="px-3 py-2">Tolls</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {currentCycle.charts && currentCycle.charts.length > 0 ? (
                                                    currentCycle.charts.map(chart => (
                                                        <tr key={chart.id}>
                                                            <td className="px-3 py-2 font-medium text-slate-900">{chart.running_chart?.reference ?? `Chart #${chart.running_chart_id}`}</td>
                                                            <td className="px-3 py-2">
                                                                {chart.vehicle?.registration_number ?? chart.vehicle?.vehicle_number ?? `Vehicle #${chart.vehicle_id}`}
                                                                {chart.is_replacement && (
                                                                    <span className="ml-1 px-1 py-0.2 rounded bg-amber-100 text-amber-800 text-[9px] font-bold">
                                                                        [R]
                                                                    </span>
                                                                )}
                                                            </td>
                                                            <td className="px-3 py-2 text-slate-700">{chart.driver_name_snapshot}</td>
                                                            <td className="px-3 py-2 font-medium">{chart.commercial_km} km</td>
                                                            <td className="px-3 py-2">{chart.normal_ot_minutes} m</td>
                                                            <td className="px-3 py-2">{chart.double_ot_minutes} m</td>
                                                            <td className="px-3 py-2">{chart.night_outs}</td>
                                                            <td className="px-3 py-2"><MoneyDisplay value={chart.tolls_amount} currency={cur} /></td>
                                                        </tr>
                                                    ))
                                                ) : (
                                                    <tr>
                                                        <td colSpan={8} className="px-3 py-4 text-center text-slate-400">
                                                            No running charts attached to this cycle yet.
                                                        </td>
                                                    </tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Tab 3: Additions & Deductions */}
                        {activeTab === 'items' && !loading && (
                            <div className="space-y-4">
                                {!isFinalized && (
                                    <form onSubmit={handleAddItem} className="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                                        <h4 className="text-sm font-bold text-slate-900">Add Line Item Adjustment</h4>
                                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                            <Select
                                                label="Item Type"
                                                value={itemType}
                                                onChange={e => setItemType(e.target.value as 'addition' | 'deduction')}
                                                options={[
                                                    { value: 'addition', label: '+ Addition (Charge to Customer / Allowance to Owner)' },
                                                    { value: 'deduction', label: '- Deduction (Rebate to Customer / Deduction from Owner)' },
                                                ]}
                                                required
                                            />
                                            <Select
                                                label="Category"
                                                value={category}
                                                onChange={e => setCategory(e.target.value)}
                                                options={[
                                                    { value: 'driver_salary', label: 'Driver Salary' },
                                                    { value: 'driver_allowance', label: 'Driver Allowance' },
                                                    { value: 'overtime', label: 'Overtime' },
                                                    { value: 'excess_km', label: 'Excess Kilometres' },
                                                    { value: 'fuel_charge', label: 'Fuel Expense / Surcharge' },
                                                    { value: 'toll_charge', label: 'Highway Toll Reimbursement' },
                                                    { value: 'night_out', label: 'Night-Out Allowance' },
                                                    { value: 'downtime_rebate', label: 'Vehicle Breakdown Rebate / Downtime' },
                                                    { value: 'damage_recharge', label: 'Damage Recharge' },
                                                    { value: 'goodwill_discount', label: 'Goodwill Discount' },
                                                    { value: 'other', label: 'Other Operational Item' },
                                                ]}
                                                required
                                            />
                                            <Input
                                                label="Description"
                                                placeholder="e.g. Breakdown rebate for 2 days"
                                                value={description}
                                                onChange={e => setDescription(e.target.value)}
                                                required
                                            />
                                        </div>
                                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <Input
                                                label="Quantity"
                                                type="number"
                                                step="0.000001"
                                                min="0.000001"
                                                value={quantity}
                                                onChange={e => setQuantity(e.target.value)}
                                                required
                                            />
                                            <Input
                                                label={`Unit Rate (${cur})`}
                                                type="number"
                                                step="0.000001"
                                                value={unitRate}
                                                onChange={e => setUnitRate(e.target.value)}
                                                required
                                            />
                                        </div>
                                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <Select
                                                label="Attributed Vehicle (Optional)"
                                                value={itemVehicleId}
                                                onChange={e => setItemVehicleId(e.target.value)}
                                                options={[
                                                    { value: '', label: 'General / No specific vehicle' },
                                                    ...availableVehicles.map(v => ({ value: String(v.id), label: v.label })),
                                                ]}
                                            />
                                            <Select
                                                label="Attributed Driver (Optional)"
                                                value={itemDriverId}
                                                onChange={e => setItemDriverId(e.target.value)}
                                                options={[
                                                    { value: '', label: 'General / No specific driver' },
                                                    ...availableDrivers.map(d => ({ value: String(d.id), label: d.label })),
                                                ]}
                                            />
                                        </div>
                                        <div className="flex justify-end pt-2">
                                            <Button type="submit" variant="primary" loading={submitting} disabled={!description.trim() || !unitRate}>
                                                Add Item
                                            </Button>
                                        </div>
                                    </form>
                                )}

                                <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                    <table className="w-full text-left text-xs">
                                        <thead>
                                            <tr className="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500">
                                                <th className="px-3 py-2">Type</th>
                                                <th className="px-3 py-2">Category</th>
                                                <th className="px-3 py-2">Description</th>
                                                <th className="px-3 py-2">Attribution</th>
                                                <th className="px-3 py-2 text-right">Qty</th>
                                                <th className="px-3 py-2 text-right">Unit Rate</th>
                                                <th className="px-3 py-2 text-right">Total Amount</th>
                                                {!isFinalized && <th className="px-3 py-2 text-right">Action</th>}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {currentCycle.items && currentCycle.items.length > 0 ? (
                                                currentCycle.items.map(item => (
                                                    <tr key={item.id}>
                                                        <td className="px-3 py-2">
                                                            <span className={`inline-flex px-2 py-0.5 rounded text-[11px] font-semibold ${
                                                                item.item_type === 'addition'
                                                                    ? 'bg-emerald-100 text-emerald-800'
                                                                    : 'bg-rose-100 text-rose-800'
                                                              }`}>
                                                                {item.item_type === 'addition' ? '+ Addition' : '- Deduction'}
                                                            </span>
                                                        </td>
                                                        <td className="px-3 py-2 capitalize">{item.category.replace('_', ' ')}</td>
                                                        <td className="px-3 py-2 font-medium text-slate-800">{item.description}</td>
                                                        <td className="px-3 py-2 text-slate-600">
                                                            {item.vehicle?.registration_number || item.vehicle_number ? (
                                                                <span className="inline-block px-1.5 py-0.5 rounded bg-blue-50 text-blue-800 text-[10px] font-semibold mr-1">
                                                                    {item.vehicle?.registration_number || item.vehicle_number}
                                                                </span>
                                                            ) : null}
                                                            {item.driver?.display_name || item.driver_name ? (
                                                                <span className="inline-block px-1.5 py-0.5 rounded bg-purple-50 text-purple-800 text-[10px] font-semibold">
                                                                    {item.driver?.display_name || item.driver_name}
                                                                </span>
                                                            ) : null}
                                                            {!item.vehicle_id && !item.driver_employee_id && !item.vehicle_number && !item.driver_name && (
                                                                <span className="text-slate-400">General</span>
                                                            )}
                                                        </td>
                                                        <td className="px-3 py-2 text-right">{item.quantity}</td>
                                                        <td className="px-3 py-2 text-right"><MoneyDisplay value={item.unit_rate} currency={cur} /></td>
                                                        <td className="px-3 py-2 text-right font-bold text-slate-900">
                                                            <MoneyDisplay value={item.amount} currency={cur} />
                                                        </td>
                                                        {!isFinalized && (
                                                            <td className="px-3 py-2 text-right">
                                                                <Button
                                                                    variant="ghost"
                                                                    onClick={() => handleRemoveItem(item)}
                                                                    disabled={submitting}
                                                                >
                                                                    Delete
                                                                </Button>
                                                            </td>
                                                        )}
                                                    </tr>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td colSpan={isFinalized ? 7 : 8} className="px-3 py-4 text-center text-slate-400">
                                                        No addition or deduction line items added to this cycle.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* Tab 4: Finalize & Issue */}
                        {activeTab === 'finalize' && !loading && (
                            <div className="space-y-4">
                                {isFinalized ? (
                                    <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 space-y-3">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <h4 className="font-bold text-sm">Billing Cycle Finalized</h4>
                                                <p className="text-xs mt-1">
                                                    This cycle has been finalized. Final Invoice: {currentCycle.final_invoice?.invoice_number ?? currentCycle.supplementary_invoice?.invoice_number}.
                                                </p>
                                            </div>
                                            <div className="flex gap-2">
                                                <Button
                                                    variant="secondary"
                                                    onClick={() => window.open(getBillingCycleExportUrl(currentCycle.id, 'pdf'), '_blank')}
                                                >
                                                    Statement (PDF)
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    onClick={() => window.open(getBillingCycleExportUrl(currentCycle.id, 'print'), '_blank')}
                                                >
                                                    Print
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <>
                                        {previewLoading && <LoadingState label="Calculating final invoice amounts..." />}
                                        {preview && (
                                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-4">
                                                <div className="flex items-center justify-between border-b border-slate-200 pb-2">
                                                    <h4 className="text-sm font-bold text-slate-900">Final Financial Breakdown Preview</h4>
                                                    <div className="flex gap-2">
                                                        <Button
                                                            variant="secondary"
                                                            onClick={() => window.open(getBillingCycleExportUrl(currentCycle.id, 'pdf'), '_blank')}
                                                        >
                                                            Preview PDF
                                                        </Button>
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                                    <div>
                                                        <span className="text-xs text-slate-500 font-semibold">Base Rent</span>
                                                        <div className="font-semibold text-slate-800"><MoneyDisplay value={preview.base_rent_amount} currency={cur} /></div>
                                                    </div>
                                                    <div>
                                                        <span className="text-xs text-slate-500 font-semibold">Excess KM & OT</span>
                                                        <div className="font-semibold text-slate-800">
                                                            <MoneyDisplay value={preview.excess_km_amount} currency={cur} />
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span className="text-xs text-slate-500 font-semibold">Additions</span>
                                                        <div className="font-semibold text-emerald-700">+<MoneyDisplay value={preview.additions_amount} currency={cur} /></div>
                                                    </div>
                                                    <div>
                                                        <span className="text-xs text-slate-500 font-semibold">Deductions</span>
                                                        <div className="font-semibold text-rose-700">-<MoneyDisplay value={preview.deductions_amount} currency={cur} /></div>
                                                    </div>
                                                </div>

                                                <div className="pt-3 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                    <div>
                                                        <span className="text-xs text-slate-500 font-semibold">Consolidated Net Total</span>
                                                        <div className="text-lg font-bold text-slate-900"><MoneyDisplay value={preview.total_billed_amount} currency={cur} /></div>
                                                        <span className="block text-[11px] text-slate-500">Formula: Base Rent + Excess KM/OT + Additions − Deductions</span>
                                                    </div>
                                                    <div>
                                                        <span className="text-xs text-slate-500 font-semibold">Stage 1 Invoiced</span>
                                                        <div className="text-lg font-semibold text-slate-600">
                                                            <MoneyDisplay value={preview.initial_invoice?.grand_total ?? '0.000000'} currency={cur} />
                                                        </div>
                                                        <span className="block text-[11px] text-slate-500">Advance base rent invoice previously issued</span>
                                                    </div>
                                                    <div>
                                                        <span className="text-xs text-slate-500 font-semibold">Delta to Settle</span>
                                                        <div className="text-lg font-bold text-blue-700"><MoneyDisplay value={preview.gross_delta_amount} currency={cur} /></div>
                                                        <span className="block text-[11px] text-blue-600 font-medium">Calculation: Consolidated Net Total − Stage 1 Invoiced</span>
                                                    </div>
                                                </div>

                                                {/* Fleet Vehicle Attribution Breakdown */}
                                                {preview.vehicle_attributions && preview.vehicle_attributions.length > 0 && (
                                                    <div className="space-y-2 pt-2 border-t border-slate-200">
                                                        <span className="text-xs font-bold text-slate-700 uppercase tracking-wider block">
                                                            Fleet Vehicle Attributions ({preview.vehicle_attributions.length} Vehicles)
                                                        </span>
                                                        <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                                                            <table className="w-full text-left text-xs">
                                                                <thead className="bg-slate-50 text-slate-600 border-b border-slate-200">
                                                                    <tr>
                                                                        <th className="px-3 py-1.5">Vehicle</th>
                                                                        <th className="px-3 py-1.5">Role</th>
                                                                        <th className="px-3 py-1.5 text-center">Days</th>
                                                                        <th className="px-3 py-1.5 text-right">KM</th>
                                                                        <th className="px-3 py-1.5 text-right">Norm OT</th>
                                                                        <th className="px-3 py-1.5 text-right">Attributed Total</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody className="divide-y divide-slate-100">
                                                                    {preview.vehicle_attributions.map(va => (
                                                                        <tr key={va.vehicle_id}>
                                                                            <td className="px-3 py-1.5 font-bold text-slate-900">{va.vehicle_label}</td>
                                                                            <td className="px-3 py-1.5">
                                                                                {va.is_replacement ? (
                                                                                    <span className="text-amber-700 font-semibold">Replacement</span>
                                                                                ) : (
                                                                                    <span className="text-slate-600">Primary</span>
                                                                                )}
                                                                            </td>
                                                                            <td className="px-3 py-1.5 text-center">{va.custody_days}</td>
                                                                            <td className="px-3 py-1.5 text-right">{va.total_km}</td>
                                                                            <td className="px-3 py-1.5 text-right">{va.normal_ot_hours} h</td>
                                                                            <td className="px-3 py-1.5 text-right font-bold text-slate-900">
                                                                                <MoneyDisplay value={va.subtotal} currency={cur} />
                                                                            </td>
                                                                        </tr>
                                                                    ))}
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                )}

                                                {/* Driver Attribution Breakdown */}
                                                {preview.driver_attributions && preview.driver_attributions.length > 0 && (
                                                    <div className="space-y-2 pt-2 border-t border-slate-200">
                                                        <span className="text-xs font-bold text-slate-700 uppercase tracking-wider block">
                                                            Driver Overtime Attributions ({preview.driver_attributions.length} Drivers)
                                                        </span>
                                                        <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                                                            <table className="w-full text-left text-xs">
                                                                <thead className="bg-slate-50 text-slate-600 border-b border-slate-200">
                                                                    <tr>
                                                                        <th className="px-3 py-1.5">Driver</th>
                                                                        <th className="px-3 py-1.5 text-center">Trips</th>
                                                                        <th className="px-3 py-1.5 text-right">Norm OT (h)</th>
                                                                        <th className="px-3 py-1.5 text-right">Dbl OT (h)</th>
                                                                        <th className="px-3 py-1.5 text-right">Trpl OT (h)</th>
                                                                        <th className="px-3 py-1.5 text-right">Night Outs</th>
                                                                        <th className="px-3 py-1.5 text-right">Driver Total</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody className="divide-y divide-slate-100">
                                                                    {preview.driver_attributions.map((da, idx) => (
                                                                        <tr key={idx}>
                                                                            <td className="px-3 py-1.5 font-bold text-slate-900">{da.driver_name}</td>
                                                                            <td className="px-3 py-1.5 text-center">{da.charts_count}</td>
                                                                            <td className="px-3 py-1.5 text-right">{da.normal_ot_hours}</td>
                                                                            <td className="px-3 py-1.5 text-right">{da.double_ot_hours}</td>
                                                                            <td className="px-3 py-1.5 text-right">{da.triple_ot_hours ?? '0.000'}</td>
                                                                            <td className="px-3 py-1.5 text-right">{da.night_outs}</td>
                                                                            <td className="px-3 py-1.5 text-right font-bold text-slate-900">
                                                                                <MoneyDisplay value={da.total_earnings} currency={cur} />
                                                                            </td>
                                                                        </tr>
                                                                    ))}
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        )}

                                        <form onSubmit={handleFinalize} className="space-y-4">
                                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                                <Select
                                                    label="Finalization Strategy"
                                                    value={finalizeMode}
                                                    onChange={e => setFinalizeMode(e.target.value as 'supplementary' | 'consolidated')}
                                                    options={[
                                                        { value: 'supplementary', label: 'Mode A: Supplementary Usage Invoice' },
                                                        { value: 'consolidated', label: 'Mode B: Consolidated Revision & Void Stage 1' },
                                                    ]}
                                                    required
                                                />
                                                <Input
                                                    label="Invoice Date"
                                                    type="date"
                                                    value={finalInvoiceDate}
                                                    onChange={e => setFinalInvoiceDate(e.target.value)}
                                                    hint="Posting date for final invoice"
                                                    required
                                                />
                                                <Input
                                                    label="Due Date"
                                                    type="date"
                                                    value={finalDueDate}
                                                    onChange={e => setFinalDueDate(e.target.value)}
                                                    hint="Final payment deadline"
                                                />
                                            </div>

                                            <p className="text-xs text-slate-600">
                                                {finalizeMode === 'supplementary'
                                                    ? 'Mode A leaves the original Stage 1 Base Rent invoice untouched and issues a new Supplementary Invoice strictly for the usage, driver fees, and delta adjustments.'
                                                    : 'Mode B generates a single definitive Consolidated Invoice for the full final balance, issues a Credit Note to offset the Stage 1 invoice, and automatically transfers prior payments via PaymentAllocationService.'}
                                            </p>

                                            <div className="flex justify-end gap-2 pt-2 border-t border-slate-200">
                                                <Button type="button" variant="secondary" onClick={onClose} disabled={submitting}>
                                                    Cancel
                                                </Button>
                                                <Button type="submit" variant="primary" loading={submitting}>
                                                    Finalize Cycle & Issue Invoices
                                                </Button>
                                            </div>
                                        </form>
                                    </>
                                )}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </Modal>
    );
}
