/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import {
    listPrinterAssignments,
    setDefaultInvoiceLayout,
    deletePrinterAssignment,
} from '../invoiceLayoutApi';
import type { InvoiceLayout } from '../invoiceLayoutTypes';

interface Props {
    open: boolean;
    layouts: InvoiceLayout[];
    organizationUnitId?: number | null;
    organizationUnitName?: string | null;
    onClose: () => void;
}

export function InvoicePrinterAssignmentsModal({
    open,
    layouts,
    organizationUnitId,
    organizationUnitName,
    onClose,
}: Props) {
    const [assignments, setAssignments] = useState<Record<string, string>>({});
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [newPrinter, setNewPrinter] = useState('');
    const [selectedLayoutCode, setSelectedLayoutCode] = useState('');
    const [assignScope, setAssignScope] = useState<'tenant' | 'organization_unit'>('tenant');
    const [error, setError] = useState<ApiError | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const activeLayouts = useMemo(() => layouts.filter((l) => l.is_active), [layouts]);

    const loadAssignments = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const data = await listPrinterAssignments(
                assignScope === 'organization_unit' && organizationUnitId ? organizationUnitId : undefined,
            );
            setAssignments(data || {});
        } catch (caught) {
            setError(toApiError(caught));
        } finally {
            setLoading(false);
        }
    }, [assignScope, organizationUnitId]);

    useEffect(() => {
        if (!open) return;
        queueMicrotask(() => {
            void loadAssignments();
            if (activeLayouts.length > 0) {
                setSelectedLayoutCode((prev) => prev || activeLayouts[0].code);
            }
        });
    }, [open, loadAssignments, activeLayouts]);

    const handleAddAssignment = async (e: React.FormEvent) => {
        e.preventDefault();
        const trimmed = newPrinter.trim();
        if (!trimmed || !selectedLayoutCode) return;

        setSaving(true);
        setError(null);
        setSuccess(null);
        try {
            await setDefaultInvoiceLayout({
                code: selectedLayoutCode,
                scope: 'printer',
                printer: trimmed,
                organization_unit_id: assignScope === 'organization_unit' && organizationUnitId ? organizationUnitId : null,
            });
            setSuccess(`Printer "${trimmed}" successfully mapped to ${selectedLayoutCode}.`);
            setNewPrinter('');
            await loadAssignments();
        } catch (caught) {
            setError(toApiError(caught));
        } finally {
            setSaving(false);
        }
    };

    const handleRemoveAssignment = async (printer: string) => {
        setError(null);
        setSuccess(null);
        try {
            await deletePrinterAssignment(
                printer,
                assignScope === 'organization_unit' && organizationUnitId ? organizationUnitId : undefined,
            );
            setSuccess(`Printer assignment "${printer}" removed.`);
            await loadAssignments();
        } catch (caught) {
            setError(toApiError(caught));
        }
    };

    if (!open) return null;

    const entries = Object.entries(assignments);

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-xs">
            <div className="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
                {/* Header */}
                <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <h2 className="text-lg font-bold text-slate-900">Printer Layout Assignments</h2>
                        <p className="text-xs text-slate-500">
                            Configure which invoice layout template is automatically selected for each physical or network printer.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                    >
                        ✕
                    </button>
                </div>

                <div className="max-h-[75vh] overflow-y-auto p-6 space-y-6">
                    {success && <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />}
                    {error && <ErrorAlert error={error} />}

                    {/* Scope Selector if OU is available */}
                    {organizationUnitId && (
                        <div className="flex items-center gap-4 rounded-lg bg-slate-50 p-3 border border-slate-200">
                            <span className="text-xs font-semibold text-slate-700">Configuration Scope:</span>
                            <label className="flex items-center gap-1.5 text-xs text-slate-700 cursor-pointer">
                                <input
                                    type="radio"
                                    name="assignScope"
                                    checked={assignScope === 'tenant'}
                                    onChange={() => setAssignScope('tenant')}
                                />
                                Organization-wide
                            </label>
                            <label className="flex items-center gap-1.5 text-xs text-slate-700 cursor-pointer">
                                <input
                                    type="radio"
                                    name="assignScope"
                                    checked={assignScope === 'organization_unit'}
                                    onChange={() => setAssignScope('organization_unit')}
                                />
                                Branch: {organizationUnitName || `#${organizationUnitId}`}
                            </label>
                        </div>
                    )}

                    {/* Add Assignment Form */}
                    <form onSubmit={(e) => void handleAddAssignment(e)} className="rounded-xl border border-slate-200 p-4 space-y-3 bg-slate-50/50">
                        <div className="text-xs font-semibold text-slate-800">Assign Printer to Template</div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label className="block text-[11px] font-medium text-slate-600 mb-1">
                                    Printer Name / Identifier
                                </label>
                                <Input
                                    placeholder="e.g. pos_thermal_01, receipt_cashier"
                                    value={newPrinter}
                                    onChange={(e) => setNewPrinter(e.target.value)}
                                    pattern="^[a-zA-Z0-9_\-\.]+$"
                                    title="Alphanumeric, underscores, hyphens, and dots"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-[11px] font-medium text-slate-600 mb-1">
                                    Assigned Layout Template
                                </label>
                                <Select
                                    value={selectedLayoutCode}
                                    onChange={(e) => setSelectedLayoutCode(e.target.value)}
                                    options={activeLayouts.map((l) => ({
                                        value: l.code,
                                        label: `${l.name} (${l.paper_size_label})`,
                                    }))}
                                />
                            </div>
                        </div>
                        <div className="flex justify-end">
                            <Button type="submit" size="sm" loading={saving} disabled={!newPrinter.trim()}>
                                Save Printer Assignment
                            </Button>
                        </div>
                    </form>

                    {/* Current Assignments Table */}
                    <div>
                        <div className="text-xs font-semibold text-slate-800 mb-2">Active Printer Mappings</div>
                        {loading ? (
                            <div className="py-6"><LoadingState label="Loading printer mappings…" /></div>
                        ) : entries.length === 0 ? (
                            <div className="rounded-lg border border-dashed border-slate-200 p-6 text-center text-xs text-slate-500">
                                No specific printer assignments configured for this scope. Default branch or tenant layout will be used.
                            </div>
                        ) : (
                            <div className="overflow-hidden rounded-lg border border-slate-200">
                                <table className="min-w-full divide-y divide-slate-200 text-xs">
                                    <thead className="bg-slate-50 text-slate-600">
                                        <tr>
                                            <th className="px-4 py-2 text-left font-semibold">Printer</th>
                                            <th className="px-4 py-2 text-left font-semibold">Mapped Layout</th>
                                            <th className="px-4 py-2 text-right font-semibold">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 bg-white">
                                        {entries.map(([printer, layoutCode]) => {
                                            const matched = layouts.find((l) => l.code === layoutCode);
                                            return (
                                                <tr key={printer}>
                                                    <td className="px-4 py-2.5 font-mono font-medium text-slate-800">
                                                        {printer}
                                                    </td>
                                                    <td className="px-4 py-2.5 text-slate-600">
                                                        {matched ? (
                                                            <span>
                                                                <strong className="text-slate-800">{matched.name}</strong> ({matched.paper_size_label})
                                                            </span>
                                                        ) : (
                                                            <span className="font-mono">{layoutCode}</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-2.5 text-right">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-red-600 hover:text-red-700 text-xs"
                                                            onClick={() => void handleRemoveAssignment(printer)}
                                                        >
                                                            Remove
                                                        </Button>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>

                <div className="flex justify-end border-t border-slate-200 px-6 py-3">
                    <Button variant="secondary" size="sm" onClick={onClose}>
                        Close
                    </Button>
                </div>
            </div>
        </div>
    );
}
