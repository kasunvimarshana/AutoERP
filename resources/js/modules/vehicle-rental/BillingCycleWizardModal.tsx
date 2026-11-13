import React, { useEffect, useState } from 'react';
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
    type BillingCycle,
    type BillingCycleItem,
    type BillingCyclePreview,
} from './agreementApi';

interface Props {
    open: boolean;
    onClose: () => void;
    kind: AgreementKind;
    agreement: Agreement;
    cycle?: BillingCycle | null;
    onCycleUpdated?: (cycle: BillingCycle) => void;
}

export function BillingCycleWizardModal({
    open,
    onClose,
    kind,
    agreement,
    cycle: initialCycle,
    onCycleUpdated,
}: Props) {
    const isNew = !initialCycle;
    const [currentCycle, setCurrentCycle] = useState<BillingCycle | null>(initialCycle ?? null);
    const [activeTab, setActiveTab] = useState<'overview' | 'charts' | 'items' | 'finalize'>('overview');
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [feedback, setFeedback] = useState<string | null>(null);

    // Create Cycle Form State
    const today = businessDateInputValue();
    const [periodFrom, setPeriodFrom] = useState(agreement.starts_on ?? today);
    const [periodUntil, setPeriodUntil] = useState(agreement.ends_on ?? today);
    const [billingMode, setBillingMode] = useState('monthly');
    const [finalizationMode, setFinalizationMode] = useState('supplementary');
    const [issueInitialInvoice, setIssueInitialInvoice] = useState(true);
    const [invoiceDate, setInvoiceDate] = useState(today);
    const [dueDate, setDueDate] = useState('');
    const [notes, setNotes] = useState('');

    // Attach Charts State
    const [chartIdsInput, setChartIdsInput] = useState('');

    // Add Item State
    const [itemType, setItemType] = useState<'addition' | 'deduction'>('addition');
    const [category, setCategory] = useState('driver_salary');
    const [description, setDescription] = useState('');
    const [quantity, setQuantity] = useState('1.000');
    const [unitRate, setUnitRate] = useState('');

    // Finalize / Preview State
    const [preview, setPreview] = useState<BillingCyclePreview | null>(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [finalizeMode, setFinalizeMode] = useState<'supplementary' | 'consolidated'>('supplementary');
    const [finalInvoiceDate, setFinalInvoiceDate] = useState(today);
    const [finalDueDate, setFinalDueDate] = useState('');

    useEffect(() => {
        setCurrentCycle(initialCycle ?? null);
        if (initialCycle) {
            setActiveTab('overview');
            setFinalizeMode((initialCycle.finalization_mode as 'supplementary' | 'consolidated') || 'supplementary');
        } else {
            setPeriodFrom(agreement.starts_on ?? today);
            setPeriodUntil(agreement.ends_on ?? today);
        }
    }, [initialCycle, agreement, today]);

    const cur = agreement.currency.code;

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

    async function handleAttachCharts(e: React.FormEvent) {
        e.preventDefault();
        if (!currentCycle) return;
        const ids = chartIdsInput
            .split(',')
            .map(s => parseInt(s.trim(), 10))
            .filter(n => !isNaN(n) && n > 0);

        if (ids.length === 0) {
            setError(toApiError(new Error('Please enter at least one valid Running Chart ID.')));
            return;
        }

        setSubmitting(true);
        setError(null);
        try {
            const updated = await attachCycleCharts(currentCycle.id, ids, currentCycle.row_version);
            setCurrentCycle(updated);
            setChartIdsInput('');
            setFeedback('Running charts attached successfully.');
            if (onCycleUpdated) onCycleUpdated(updated);
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
            }, currentCycle.row_version);

            setDescription('');
            setUnitRate('');
            setQuantity('1.000');
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
                                    { value: 'monthly', label: 'Monthly' },
                                    { value: 'weekly', label: 'Weekly' },
                                    { value: 'daily', label: 'Daily' },
                                    { value: 'custom', label: 'Custom' },
                                ]}
                                required
                            />
                            <Select
                                label="Finalization Mode Strategy"
                                value={finalizationMode}
                                onChange={e => setFinalizationMode(e.target.value)}
                                options={[
                                    { value: 'supplementary', label: 'Mode A: Supplementary Usage Invoice' },
                                    { value: 'consolidated', label: 'Mode B: Consolidated Revision & Credit Note' },
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
                        <div className="flex border-b border-slate-200 gap-2">
                            {(['overview', 'charts', 'items', 'finalize'] as const).map(tab => (
                                <button
                                    key={tab}
                                    type="button"
                                    onClick={() => {
                                        setActiveTab(tab);
                                        if (tab === 'finalize') loadFinalPreview();
                                    }}
                                    className={`px-3 py-2 text-xs font-semibold rounded-t-lg transition-colors capitalize ${
                                        activeTab === tab
                                            ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-600'
                                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                    }`}
                                >
                                    {tab === 'items' ? 'Additions & Deductions' : tab}
                                </button>
                            ))}
                        </div>

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
                                    <h4 className="text-sm font-bold text-slate-900">Generated Financial Invoices</h4>
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
                                        Attached Running Charts ({currentCycle.charts?.length ?? 0})
                                    </h4>
                                </div>

                                {!isFinalized && (
                                    <form onSubmit={handleAttachCharts} className="rounded-xl border border-slate-200 bg-slate-50 p-3 flex gap-2 items-end">
                                        <div className="flex-1">
                                            <Input
                                                label="Attach Running Chart IDs"
                                                placeholder="e.g. 101, 102, 105"
                                                value={chartIdsInput}
                                                onChange={e => setChartIdsInput(e.target.value)}
                                            />
                                        </div>
                                        <Button type="submit" variant="secondary" loading={submitting} disabled={!chartIdsInput.trim()}>
                                            Attach Charts
                                        </Button>
                                    </form>
                                )}

                                <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                    <table className="w-full text-left text-xs">
                                        <thead>
                                            <tr className="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500">
                                                <th className="px-3 py-2">Chart #</th>
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
                                                        <td className="px-3 py-2">{chart.commercial_km} km</td>
                                                        <td className="px-3 py-2">{chart.normal_ot_minutes} m</td>
                                                        <td className="px-3 py-2">{chart.double_ot_minutes} m</td>
                                                        <td className="px-3 py-2">{chart.night_outs}</td>
                                                        <td className="px-3 py-2"><MoneyDisplay value={chart.tolls_amount} currency={cur} /></td>
                                                    </tr>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td colSpan={6} className="px-3 py-4 text-center text-slate-400">
                                                        No running charts attached to this cycle yet.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* Tab 3: Additions & Deductions */}
                        {activeTab === 'items' && !loading && (
                            <div className="space-y-4">
                                {!isFinalized && (
                                    <form onSubmit={handleAddItem} className="rounded-xl border border-blue-200 bg-blue-50/40 p-4 space-y-3">
                                        <h5 className="text-xs font-bold text-slate-800 uppercase tracking-wider">Add Line Item Adjustment</h5>
                                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                            <Select
                                                label="Type"
                                                value={itemType}
                                                onChange={e => setItemType(e.target.value as 'addition' | 'deduction')}
                                                options={[
                                                    { value: 'addition', label: '+ Addition (Chargeable)' },
                                                    { value: 'deduction', label: '- Deduction (Rebate / Penalty)' },
                                                ]}
                                                required
                                            />
                                            <Select
                                                label="Category"
                                                value={category}
                                                onChange={e => setCategory(e.target.value)}
                                                options={[
                                                    { value: 'driver_salary', label: 'Driver Salary' },
                                                    { value: 'overtime', label: 'Driver Overtime (OT)' },
                                                    { value: 'excess_km', label: 'Excess Mileage' },
                                                    { value: 'fuel', label: 'Fuel Surcharge' },
                                                    { value: 'toll', label: 'Highway Toll / Parking' },
                                                    { value: 'night_out', label: 'Night Out Allowance' },
                                                    { value: 'deduction', label: 'Breakdown / Downtime Rebate' },
                                                    { value: 'other', label: 'Other Adjustment' },
                                                ]}
                                                required
                                            />
                                            <Input
                                                label="Description"
                                                placeholder="e.g. Driver Sunday Overtime / Fuel recovery"
                                                value={description}
                                                onChange={e => setDescription(e.target.value)}
                                                required
                                            />
                                        </div>
                                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <Input
                                                label="Quantity / Hours / Days"
                                                type="number"
                                                step="0.001"
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
                                                placeholder="0.00"
                                                required
                                            />
                                        </div>
                                        <div className="flex justify-end">
                                            <Button type="submit" variant="primary" loading={submitting}>
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
                                                <th className="px-3 py-2 text-right">Qty</th>
                                                <th className="px-3 py-2 text-right">Unit Rate</th>
                                                <th className="px-3 py-2 text-right">Total</th>
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
                                                    <td colSpan={isFinalized ? 6 : 7} className="px-3 py-4 text-center text-slate-400">
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
                                    <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
                                        <h4 className="font-bold text-sm">Billing Cycle Finalized</h4>
                                        <p className="text-xs mt-1">
                                            This cycle has been finalized. Final Invoice: {currentCycle.final_invoice?.invoice_number ?? currentCycle.supplementary_invoice?.invoice_number}.
                                        </p>
                                    </div>
                                ) : (
                                    <>
                                        {previewLoading && <LoadingState label="Calculating final invoice amounts..." />}
                                        {preview && (
                                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                                                <h4 className="text-sm font-bold text-slate-900">Final Financial Breakdown Preview</h4>
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
                                                    </div>
                                                    <div>
                                                        <span className="text-xs text-slate-500 font-semibold">Stage 1 Invoiced</span>
                                                        <div className="text-lg font-semibold text-slate-600">
                                                            <MoneyDisplay value={preview.initial_invoice?.grand_total ?? '0.000000'} currency={cur} />
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span className="text-xs text-slate-500 font-semibold">Delta to Settle</span>
                                                        <div className="text-lg font-bold text-blue-700"><MoneyDisplay value={preview.gross_delta_amount} currency={cur} /></div>
                                                    </div>
                                                </div>
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
                                                    required
                                                />
                                                <Input
                                                    label="Due Date"
                                                    type="date"
                                                    value={finalDueDate}
                                                    onChange={e => setFinalDueDate(e.target.value)}
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
