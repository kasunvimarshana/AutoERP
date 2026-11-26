/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useCallback, useEffect, useState } from 'react';
import { Button } from '@/shared/components/Button';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import type { PaginationMeta } from '@/shared/types/pagination';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { EmployeeLookupSelect } from '../EmployeeLookupSelect';
import { extractHrApiError } from '../roster/rosterError';
import { actionCompOffClaim, claimCompOff, listCompOffClaims } from '../../hrApi';
import { COMP_OFF_STATUS_OPTIONS } from '../../hrConstants';
import type { EmployeeSummary, HrCompOffClaim } from '../../hrTypes';

interface CompOffClaimsTabProps {
    canApply: boolean;
    canManage: boolean;
}

export function CompOffClaimsTab({ canApply, canManage }: CompOffClaimsTabProps) {
    const [selectedEmployee, setSelectedEmployee] = useState<EmployeeSummary | null>(null);
    const [statusFilter, setStatusFilter] = useState('');
    const [claims, setClaims] = useState<HrCompOffClaim[]>([]);
    const [meta, setMeta] = useState<PaginationMeta | undefined>();
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Claim Modal
    const [showClaimModal, setShowClaimModal] = useState(false);
    const [claimEmployee, setClaimEmployee] = useState<EmployeeSummary | null>(null);
    const [workedDate, setWorkedDate] = useState(() => businessDateInputValue());
    const [claimedDays, setClaimedDays] = useState('1.00');
    const [reason, setReason] = useState('');
    const [modalError, setModalError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const fetchClaims = useCallback((targetPage = page) => {
        setLoading(true);
        setError(null);
        listCompOffClaims({
            employee_id: selectedEmployee?.id,
            status: statusFilter || undefined,
            page: targetPage,
            per_page: 25,
        })
            .then((res) => {
                setClaims(res.data);
                setMeta(res.meta);
            })
            .catch((err) => setError(extractHrApiError(err, 'Failed to load comp-off claims')))
            .finally(() => setLoading(false));
    }, [page, selectedEmployee?.id, statusFilter]);

    useEffect(() => {
        queueMicrotask(() => {
            fetchClaims(page);
        });
    }, [fetchClaims, page]);

    const openClaimModal = () => {
        setClaimEmployee(selectedEmployee);
        setWorkedDate(businessDateInputValue());
        setClaimedDays('1.00');
        setReason('');
        setModalError(null);
        setShowClaimModal(true);
    };

    const handleClaimSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!claimEmployee) return;
        setSubmitting(true);
        setModalError(null);
        try {
            await claimCompOff({
                employee_id: claimEmployee.id,
                worked_date: workedDate,
                claimed_days: claimedDays,
                reason,
            });
            setShowClaimModal(false);
            fetchClaims(1);
            setPage(1);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Comp-off claim failed'));
        } finally {
            setSubmitting(false);
        }
    };

    const handleAction = async (claim: HrCompOffClaim, approve: boolean) => {
        setError(null);
        try {
            await actionCompOffClaim(claim.id, approve, claim.row_version);
            fetchClaims(page);
        } catch (err) {
            setError(extractHrApiError(err, 'Failed to action comp-off claim'));
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-end justify-between gap-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                <div className="flex flex-wrap items-end gap-4">
                    <div className="w-72">
                        <EmployeeLookupSelect
                            label="Filter by Employee"
                            value={selectedEmployee}
                            onChange={(emp) => {
                                setSelectedEmployee(emp);
                                setPage(1);
                            }}
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select
                            className="mt-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            value={statusFilter}
                            onChange={(e) => {
                                setStatusFilter(e.target.value);
                                setPage(1);
                            }}
                        >
                            <option value="">All Statuses</option>
                            {COMP_OFF_STATUS_OPTIONS.map((opt) => (
                                <option key={opt.value} value={opt.value}>
                                    {opt.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    {(selectedEmployee || statusFilter) && (
                        <Button
                            variant="secondary"
                            onClick={() => {
                                setSelectedEmployee(null);
                                setStatusFilter('');
                                setPage(1);
                            }}
                        >
                            Clear Filters
                        </Button>
                    )}
                </div>
                {canApply && (
                    <Button variant="primary" onClick={openClaimModal}>
                        + Claim Comp-Off
                    </Button>
                )}
            </div>

            {error && (
                <div className="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                    {error}
                </div>
            )}

            <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div className="border-b border-gray-200 p-4 dark:border-gray-700">
                    <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">Compensatory Off Claims</h3>
                </div>
                {loading ? (
                    <LoadingState label="Loading comp-off claims..." />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Employee</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Worked Date</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Claimed Days</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Reason</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Expiry Date</th>
                                    <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Status</th>
                                    <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {claims.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="py-8 text-center text-sm text-gray-500">
                                            No compensatory off claims found.
                                        </td>
                                    </tr>
                                ) : (
                                    claims.map((claim) => (
                                        <tr key={claim.id} className="hover:bg-gray-50 dark:hover:bg-gray-750">
                                            <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {claim.employee?.display_name}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                {claim.worked_date}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {claim.claimed_days}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                {claim.reason}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                                {claim.expiry_date || '-'}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-center">
                                                <StatusBadge status={claim.status} />
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                                {canManage && claim.status === 'pending' && (
                                                    <div className="flex justify-center gap-2">
                                                        <Button size="sm" variant="primary" onClick={() => handleAction(claim, true)}>
                                                            Approve
                                                        </Button>
                                                        <Button size="sm" variant="danger" onClick={() => handleAction(claim, false)}>
                                                            Reject
                                                        </Button>
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
                {meta && <Pagination meta={meta} onPageChange={setPage} />}
            </div>

            <Modal open={showClaimModal} onClose={() => setShowClaimModal(false)} title="Claim Compensatory Off">
                <form onSubmit={handleClaimSubmit} className="space-y-4">
                    {modalError && (
                        <div className="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                            {modalError}
                        </div>
                    )}
                    <EmployeeLookupSelect
                        label="Employee"
                        value={claimEmployee}
                        onChange={setClaimEmployee}
                    />
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Date Worked (Holiday / Rest Day)
                        </label>
                        <input
                            type="date"
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            value={workedDate}
                            onChange={(e) => setWorkedDate(e.target.value)}
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Claimed Days</label>
                        <input
                            type="number"
                            step="0.5"
                            min="0.5"
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            value={claimedDays}
                            onChange={(e) => setClaimedDays(e.target.value)}
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason / Duties Performed</label>
                        <textarea
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            rows={2}
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            placeholder="Duties performed on rest day or holiday..."
                            required
                        />
                    </div>
                    <div className="flex justify-end gap-2 pt-2">
                        <Button variant="secondary" onClick={() => setShowClaimModal(false)}>
                            Cancel
                        </Button>
                        <Button variant="primary" type="submit" disabled={submitting || !claimEmployee || !reason.trim()}>
                            {submitting ? 'Submitting...' : 'Submit Claim'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
