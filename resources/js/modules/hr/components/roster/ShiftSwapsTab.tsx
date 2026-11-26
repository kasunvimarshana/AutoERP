/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useCallback, useEffect, useState } from 'react';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import type { PaginationMeta } from '@/shared/types/pagination';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import {
    cancelShiftSwap,
    listShiftSwaps,
    managerActionShiftSwap,
    peerActionShiftSwap,
    requestShiftSwap,
} from '../../hrApi';
import { SHIFT_SWAP_STATUS_OPTIONS } from '../../hrConstants';
import type { EmployeeSummary, HrShiftSwap, ShiftSwapStatus } from '../../hrTypes';
import { EmployeeLookupSelect } from '../EmployeeLookupSelect';
import { extractHrApiError } from './rosterError';

interface ShiftSwapsTabProps {
    canManage: boolean;
}

export function ShiftSwapsTab({ canManage }: ShiftSwapsTabProps) {
    const { confirm, confirmDialog } = useConfirmDialog();

    const [swaps, setSwaps] = useState<HrShiftSwap[]>([]);
    const [meta, setMeta] = useState<PaginationMeta | undefined>();
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [modalError, setModalError] = useState<string | null>(null);

    // Filters
    const [filterStatus, setFilterStatus] = useState<ShiftSwapStatus | ''>('');
    const [filterEmployee, setFilterEmployee] = useState<EmployeeSummary | null>(null);

    // Request Swap Modal
    const [showRequestModal, setShowRequestModal] = useState(false);
    const [requester, setRequester] = useState<EmployeeSummary | null>(null);
    const [target, setTarget] = useState<EmployeeSummary | null>(null);
    const [swapDate, setSwapDate] = useState(() => businessDateInputValue());
    const [targetSwapDate, setTargetSwapDate] = useState(() => businessDateInputValue());
    const [reason, setReason] = useState('');
    const [submitting, setSubmitting] = useState(false);

    // Manager Action Modal
    const [managerTarget, setManagerTarget] = useState<{ swap: HrShiftSwap; approve: boolean } | null>(null);
    const [managerNotes, setManagerNotes] = useState('');

    const fetchSwaps = useCallback((targetPage = page) => {
        setLoading(true);
        setError(null);
        listShiftSwaps({
            status: filterStatus || undefined,
            employee_id: filterEmployee?.id,
            page: targetPage,
            per_page: 25,
        })
            .then((res) => {
                setSwaps(res.data);
                setMeta(res.meta);
            })
            .catch((err) => setError(extractHrApiError(err, 'Failed to load shift swaps')))
            .finally(() => setLoading(false));
    }, [filterEmployee?.id, filterStatus, page]);

    useEffect(() => {
        queueMicrotask(() => {
            fetchSwaps(page);
        });
    }, [fetchSwaps, page]);

    const handleRequestSwap = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!requester || !target) return;
        setSubmitting(true);
        setModalError(null);
        try {
            await requestShiftSwap({
                requester_employee_id: requester.id,
                target_employee_id: target.id,
                swap_date: swapDate,
                target_swap_date: targetSwapDate,
                reason,
            });
            setShowRequestModal(false);
            setReason('');
            fetchSwaps(page);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to submit swap request'));
        } finally {
            setSubmitting(false);
        }
    };

    const handlePeerAction = async (swap: HrShiftSwap, accept: boolean) => {
        const ok = await confirm({
            title: accept ? 'Accept Shift Swap' : 'Reject Shift Swap',
            message: `Confirm peer ${accept ? 'acceptance' : 'rejection'} for ${swap.requester_employee?.display_name ?? 'requester'} <-> ${swap.target_employee?.display_name ?? 'peer'}?`,
            confirmLabel: accept ? 'Accept Swap' : 'Reject Swap',
            danger: !accept,
        });
        if (!ok) return;
        setError(null);
        try {
            await peerActionShiftSwap(swap.id, swap.target_employee_id, accept, swap.row_version);
            fetchSwaps(page);
        } catch (err) {
            setError(extractHrApiError(err, 'Peer action failed'));
        }
    };

    const handleManagerAction = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!managerTarget) return;
        setSubmitting(true);
        setModalError(null);
        try {
            await managerActionShiftSwap(
                managerTarget.swap.id,
                managerTarget.approve,
                managerNotes.trim() || undefined,
                managerTarget.swap.row_version,
            );
            setManagerTarget(null);
            setManagerNotes('');
            fetchSwaps(page);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Manager action failed'));
        } finally {
            setSubmitting(false);
        }
    };

    const handleCancelSwap = async (swap: HrShiftSwap) => {
        const ok = await confirm({
            title: 'Withdraw Shift Swap',
            message: `Cancel/withdraw swap request between ${swap.requester_employee?.display_name ?? 'requester'} and ${swap.target_employee?.display_name ?? 'peer'}?`,
            confirmLabel: 'Withdraw',
        });
        if (!ok) return;
        setError(null);
        try {
            await cancelShiftSwap(swap.id, swap.row_version);
            fetchSwaps(page);
        } catch (err) {
            setError(extractHrApiError(err, 'Failed to cancel shift swap'));
        }
    };

    return (
        <div className="space-y-4">
            {confirmDialog}
            {error && <div className="rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" role="alert">{error}</div>}

            <div className="flex flex-wrap items-end justify-between gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-2xs">
                <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                    <EmployeeLookupSelect
                        label="Filter by Employee"
                        value={filterEmployee}
                        onChange={(val) => {
                            setFilterEmployee(val);
                            setPage(1);
                        }}
                    />
                    <div className="flex flex-col gap-1">
                        <label className="text-sm font-medium text-slate-700">Swap Status</label>
                        <select
                            className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"
                            value={filterStatus}
                            onChange={(e) => {
                                setFilterStatus(e.target.value as ShiftSwapStatus | '');
                                setPage(1);
                            }}
                        >
                            <option value="">All Statuses</option>
                            {SHIFT_SWAP_STATUS_OPTIONS.map((opt) => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                    </div>
                </div>
                <Button variant="primary" size="sm" onClick={() => { setModalError(null); setShowRequestModal(true); }}>
                    + Request Swap
                </Button>
            </div>

            {loading ? (
                <LoadingState label="Loading shift swaps..." />
            ) : (
                <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-2xs">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Requester</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Target Peer</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Swap Dates</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Reason</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Status</th>
                                <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {swaps.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="py-8 text-center text-slate-500">No shift swap requests found.</td>
                                </tr>
                            ) : (
                                swaps.map((swap) => (
                                    <tr key={swap.id} className="hover:bg-slate-50">
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-slate-900">{swap.requester_employee?.display_name ?? '-'}</div>
                                            <div className="text-xs text-slate-500">
                                                {swap.requester_shift ? `${swap.requester_shift.code} (${swap.requester_shift.start_time.slice(0, 5)})` : 'No shift'}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-slate-900">{swap.target_employee?.display_name ?? '-'}</div>
                                            <div className="text-xs text-slate-500">
                                                {swap.target_shift ? `${swap.target_shift.code} (${swap.target_shift.start_time.slice(0, 5)})` : 'No shift'}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-slate-700">
                                            <div>Req: {swap.swap_date.slice(0, 10)}</div>
                                            <div className="text-xs text-slate-500">Peer: {swap.target_swap_date.slice(0, 10)}</div>
                                        </td>
                                        <td className="max-w-xs px-4 py-3 text-slate-600">
                                            <div className="truncate" title={swap.reason}>{swap.reason}</div>
                                            {swap.manager_action_notes && (
                                                <div className="text-xs text-slate-400">Note: {swap.manager_action_notes}</div>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <StatusBadge status={swap.status} />
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex flex-wrap justify-end gap-1.5">
                                                {swap.status === 'pending_peer' && (
                                                    <>
                                                        <Button variant="primary" size="sm" onClick={() => handlePeerAction(swap, true)}>
                                                            Peer Accept
                                                        </Button>
                                                        <Button variant="danger" size="sm" onClick={() => handlePeerAction(swap, false)}>
                                                            Peer Reject
                                                        </Button>
                                                    </>
                                                )}
                                                {swap.status === 'peer_accepted' && canManage && (
                                                    <>
                                                        <Button
                                                            variant="primary"
                                                            size="sm"
                                                            onClick={() => {
                                                                setModalError(null);
                                                                setManagerTarget({ swap, approve: true });
                                                                setManagerNotes('');
                                                            }}
                                                        >
                                                            Approve
                                                        </Button>
                                                        <Button
                                                            variant="danger"
                                                            size="sm"
                                                            onClick={() => {
                                                                setModalError(null);
                                                                setManagerTarget({ swap, approve: false });
                                                                setManagerNotes('');
                                                            }}
                                                        >
                                                            Reject
                                                        </Button>
                                                    </>
                                                )}
                                                {(swap.status === 'pending_peer' || swap.status === 'peer_accepted') && (
                                                    <Button variant="secondary" size="sm" onClick={() => handleCancelSwap(swap)}>
                                                        Withdraw
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            <Pagination meta={meta} onPageChange={setPage} disabled={loading} />

            {/* Request Swap Modal */}
            <Modal open={showRequestModal} title="Request Shift Swap" onClose={() => setShowRequestModal(false)}>
                <form onSubmit={handleRequestSwap} className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    <EmployeeLookupSelect label="Requester Employee" value={requester} onChange={setRequester} />
                    <div>
                        <label className="text-sm font-medium text-slate-700">Requester Shift Date</label>
                        <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={swapDate} onChange={(e) => setSwapDate(e.target.value)} required />
                    </div>
                    <EmployeeLookupSelect label="Target Peer Employee" value={target} onChange={setTarget} />
                    <div>
                        <label className="text-sm font-medium text-slate-700">Target Peer Shift Date</label>
                        <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={targetSwapDate} onChange={(e) => setTargetSwapDate(e.target.value)} required />
                    </div>
                    <div>
                        <label className="text-sm font-medium text-slate-700">Reason</label>
                        <textarea className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" rows={2} value={reason} onChange={(e) => setReason(e.target.value)} required />
                    </div>
                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <Button type="button" variant="secondary" onClick={() => setShowRequestModal(false)}>Cancel</Button>
                        <Button type="submit" variant="primary" loading={submitting} disabled={!requester || !target || !reason.trim()}>
                            Submit Swap
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Manager Action Modal */}
            <Modal
                open={managerTarget !== null}
                title={managerTarget?.approve ? 'Approve Shift Swap' : 'Reject Shift Swap'}
                onClose={() => setManagerTarget(null)}
            >
                <form onSubmit={handleManagerAction} className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    <p className="text-sm text-slate-600">
                        {managerTarget?.approve ? 'Approve' : 'Reject'} swap between{' '}
                        <span className="font-semibold text-slate-900">{managerTarget?.swap.requester_employee?.display_name}</span> and{' '}
                        <span className="font-semibold text-slate-900">{managerTarget?.swap.target_employee?.display_name}</span>?
                    </p>
                    <div>
                        <label className="text-sm font-medium text-slate-700">Manager Notes (optional)</label>
                        <textarea className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" rows={2} value={managerNotes} onChange={(e) => setManagerNotes(e.target.value)} />
                    </div>
                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <Button type="button" variant="secondary" onClick={() => setManagerTarget(null)}>Cancel</Button>
                        <Button type="submit" variant={managerTarget?.approve ? 'primary' : 'danger'} loading={submitting}>
                            {managerTarget?.approve ? 'Approve Swap' : 'Reject Swap'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
