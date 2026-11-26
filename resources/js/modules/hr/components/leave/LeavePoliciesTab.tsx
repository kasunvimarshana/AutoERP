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
import type { PaginationMeta } from '@/shared/types/pagination';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { HrDepartmentSelect } from '../HrDepartmentSelect';
import { HrEmploymentTypeSelect } from '../HrEmploymentTypeSelect';
import { HrLeaveTypeSelect } from '../HrLeaveTypeSelect';
import { extractHrApiError } from '../roster/rosterError';
import {
    createLeavePolicy,
    deleteLeavePolicy,
    listLeavePolicies,
    updateLeavePolicy,
} from '../../hrApi';
import { getLeaveAccrualFrequencyLabel, LEAVE_ACCRUAL_FREQUENCY_OPTIONS } from '../../hrConstants';
import type {
    HrDepartment,
    HrEmploymentType,
    HrLeavePolicy,
    HrLeavePolicyPayload,
    HrLeaveType,
    LeaveAccrualFrequency,
} from '../../hrTypes';

interface LeavePoliciesTabProps {
    canManage: boolean;
}

export function LeavePoliciesTab({ canManage }: LeavePoliciesTabProps) {
    const { confirm, confirmDialog } = useConfirmDialog();

    const [policies, setPolicies] = useState<HrLeavePolicy[]>([]);
    const [meta, setMeta] = useState<PaginationMeta | undefined>();
    const [page, setPage] = useState(1);
    const [filterLeaveType, setFilterLeaveType] = useState<HrLeaveType | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const [showModal, setShowModal] = useState(false);
    const [editingPolicy, setEditingPolicy] = useState<HrLeavePolicy | null>(null);
    const [selectedLeaveType, setSelectedLeaveType] = useState<HrLeaveType | null>(null);
    const [selectedDepartment, setSelectedDepartment] = useState<HrDepartment | null>(null);
    const [selectedEmploymentType, setSelectedEmploymentType] = useState<HrEmploymentType | null>(null);
    const [name, setName] = useState('');
    const [annualAllocationDays, setAnnualAllocationDays] = useState('14.00');
    const [accrualFrequency, setAccrualFrequency] = useState<LeaveAccrualFrequency>('annual_upfront');
    const [maxCarryForwardDays, setMaxCarryForwardDays] = useState('5.00');
    const [carryForwardExpiryMonths, setCarryForwardExpiryMonths] = useState(3);
    const [maxConsecutiveDays, setMaxConsecutiveDays] = useState(10);
    const [probationRestricted, setProbationRestricted] = useState(false);
    const [effectiveFrom, setEffectiveFrom] = useState(() => businessDateInputValue());
    const [effectiveTo, setEffectiveTo] = useState('');
    const [modalError, setModalError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    const fetchPolicies = useCallback((targetPage = page) => {
        setLoading(true);
        setError(null);
        listLeavePolicies({
            leave_type_id: filterLeaveType?.id,
            page: targetPage,
            per_page: 25,
        })
            .then((res) => {
                setPolicies(res.data);
                setMeta(res.meta);
            })
            .catch((err) => setError(extractHrApiError(err, 'Failed to load leave policies')))
            .finally(() => setLoading(false));
    }, [filterLeaveType?.id, page]);

    useEffect(() => {
        queueMicrotask(() => {
            fetchPolicies(page);
        });
    }, [fetchPolicies, page]);

    const openCreate = () => {
        setEditingPolicy(null);
        setSelectedLeaveType(null);
        setSelectedDepartment(null);
        setSelectedEmploymentType(null);
        setName('');
        setAnnualAllocationDays('14.00');
        setAccrualFrequency('annual_upfront');
        setMaxCarryForwardDays('5.00');
        setCarryForwardExpiryMonths(3);
        setMaxConsecutiveDays(10);
        setProbationRestricted(false);
        setEffectiveFrom(businessDateInputValue());
        setEffectiveTo('');
        setModalError(null);
        setShowModal(true);
    };

    const openEdit = (policy: HrLeavePolicy) => {
        setEditingPolicy(policy);
        setSelectedLeaveType(policy.leave_type ?? null);
        setSelectedDepartment(policy.department ?? null);
        setSelectedEmploymentType(policy.employment_type ?? null);
        setName(policy.name);
        setAnnualAllocationDays(policy.annual_allocation_days);
        setAccrualFrequency(policy.accrual_frequency);
        setMaxCarryForwardDays(policy.max_carry_forward_days);
        setCarryForwardExpiryMonths(policy.carry_forward_expiry_months);
        setMaxConsecutiveDays(policy.max_consecutive_days);
        setProbationRestricted(policy.probation_restricted);
        setEffectiveFrom(policy.effective_from.slice(0, 10));
        setEffectiveTo(policy.effective_to ? policy.effective_to.slice(0, 10) : '');
        setModalError(null);
        setShowModal(true);
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedLeaveType) return;
        setSaving(true);
        setModalError(null);

        const payload: HrLeavePolicyPayload = {
            leave_type_id: selectedLeaveType.id,
            department_id: selectedDepartment?.id ?? null,
            employment_type_id: selectedEmploymentType?.id ?? null,
            name,
            annual_allocation_days: annualAllocationDays,
            accrual_frequency: accrualFrequency,
            max_carry_forward_days: maxCarryForwardDays,
            carry_forward_expiry_months: carryForwardExpiryMonths,
            max_consecutive_days: maxConsecutiveDays,
            probation_restricted: probationRestricted,
            effective_from: effectiveFrom,
            effective_to: effectiveTo || null,
        };

        try {
            if (editingPolicy) {
                await updateLeavePolicy(editingPolicy.id, {
                    ...payload,
                    expected_version: editingPolicy.row_version,
                });
            } else {
                await createLeavePolicy(payload);
            }
            setShowModal(false);
            fetchPolicies(editingPolicy ? page : 1);
            if (!editingPolicy) setPage(1);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to save leave policy'));
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async (policy: HrLeavePolicy) => {
        const ok = await confirm({
            title: 'Delete Leave Policy',
            message: `Are you sure you want to delete leave policy "${policy.name}"?`,
            confirmLabel: 'Delete',
            danger: true,
        });
        if (!ok) return;

        setError(null);
        try {
            await deleteLeavePolicy(policy.id, policy.row_version);
            fetchPolicies(page);
        } catch (err) {
            setError(extractHrApiError(err, 'Failed to delete leave policy'));
        }
    };

    return (
        <div className="space-y-6">
            {confirmDialog}

            <div className="flex flex-wrap items-end justify-between gap-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                <div className="flex flex-wrap items-end gap-4">
                    <div className="w-72">
                        <HrLeaveTypeSelect
                            label="Filter by Leave Type"
                            value={filterLeaveType}
                            onChange={(lt) => {
                                setFilterLeaveType(lt);
                                setPage(1);
                            }}
                        />
                    </div>
                    {filterLeaveType && (
                        <Button
                            variant="secondary"
                            onClick={() => {
                                setFilterLeaveType(null);
                                setPage(1);
                            }}
                        >
                            Clear Filter
                        </Button>
                    )}
                </div>
                {canManage && (
                    <Button variant="primary" onClick={openCreate}>
                        + New Leave Policy
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
                    <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">Leave Policies</h3>
                </div>
                {loading ? (
                    <LoadingState label="Loading leave policies..." />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Policy Name</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Leave Type</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Scope</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Annual Days</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Accrual</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Max Consec.</th>
                                    <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Probation Restr.</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Effective</th>
                                    {canManage && (
                                        <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">Actions</th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {policies.length === 0 ? (
                                    <tr>
                                        <td colSpan={canManage ? 9 : 8} className="py-8 text-center text-sm text-gray-500">
                                            No leave policies configured.
                                        </td>
                                    </tr>
                                ) : (
                                    policies.map((p) => (
                                        <tr key={p.id} className="hover:bg-gray-50 dark:hover:bg-gray-750">
                                            <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {p.name}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                {p.leave_type?.name || '-'}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                                {p.department?.name || p.employment_type?.name
                                                    ? [p.department?.name, p.employment_type?.name].filter(Boolean).join(' / ')
                                                    : 'All Employees'}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {p.annual_allocation_days}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                {getLeaveAccrualFrequencyLabel(p.accrual_frequency)}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                                {p.max_consecutive_days}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                                                {p.probation_restricted ? 'Yes' : 'No'}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                                {p.effective_from.slice(0, 10)}
                                                {p.effective_to ? ` to ${p.effective_to.slice(0, 10)}` : ' onwards'}
                                            </td>
                                            {canManage && (
                                                <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                                    <div className="flex justify-center gap-2">
                                                        <Button size="sm" variant="secondary" onClick={() => openEdit(p)}>
                                                            Edit
                                                        </Button>
                                                        <Button size="sm" variant="danger" onClick={() => handleDelete(p)}>
                                                            Delete
                                                        </Button>
                                                    </div>
                                                </td>
                                            )}
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
                {meta && <Pagination meta={meta} onPageChange={setPage} />}
            </div>

            <Modal
                open={showModal}
                onClose={() => setShowModal(false)}
                title={editingPolicy ? `Edit Policy: ${editingPolicy.name}` : 'New Leave Policy'}
            >
                <form onSubmit={handleSubmit} className="space-y-4">
                    {modalError && (
                        <div className="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300">
                            {modalError}
                        </div>
                    )}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Policy Name</label>
                            <input
                                type="text"
                                maxLength={100}
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                required
                            />
                        </div>
                        <HrLeaveTypeSelect
                            label="Leave Type"
                            value={selectedLeaveType}
                            onChange={setSelectedLeaveType}
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <HrDepartmentSelect
                            label="Department (Optional)"
                            value={selectedDepartment}
                            onChange={setSelectedDepartment}
                        />
                        <HrEmploymentTypeSelect
                            value={selectedEmploymentType}
                            onChange={setSelectedEmploymentType}
                        />
                    </div>
                    <div className="grid grid-cols-3 gap-3">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Annual Allocation (Days)</label>
                            <input
                                type="number"
                                step="0.5"
                                min="0"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={annualAllocationDays}
                                onChange={(e) => setAnnualAllocationDays(e.target.value)}
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Accrual Frequency</label>
                            <select
                                className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={accrualFrequency}
                                onChange={(e) => setAccrualFrequency(e.target.value as LeaveAccrualFrequency)}
                            >
                                {LEAVE_ACCRUAL_FREQUENCY_OPTIONS.map((opt) => (
                                    <option key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Consecutive Days</label>
                            <input
                                type="number"
                                min={1}
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={maxConsecutiveDays}
                                onChange={(e) => setMaxConsecutiveDays(Number(e.target.value) || 1)}
                                required
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Carry Forward (Days)</label>
                            <input
                                type="number"
                                step="0.5"
                                min="0"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={maxCarryForwardDays}
                                onChange={(e) => setMaxCarryForwardDays(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Carry Forward Expiry (Months)</label>
                            <input
                                type="number"
                                min={0}
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={carryForwardExpiryMonths}
                                onChange={(e) => setCarryForwardExpiryMonths(Number(e.target.value) || 0)}
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Effective From</label>
                            <input
                                type="date"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={effectiveFrom}
                                onChange={(e) => setEffectiveFrom(e.target.value)}
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Effective To (Optional)</label>
                            <input
                                type="date"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={effectiveTo}
                                onChange={(e) => setEffectiveTo(e.target.value)}
                            />
                        </div>
                    </div>
                    <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input
                            type="checkbox"
                            checked={probationRestricted}
                            onChange={(e) => setProbationRestricted(e.target.checked)}
                        />
                        Restrict leave during employee probation
                    </label>
                    <div className="flex justify-end gap-2 pt-2">
                        <Button variant="secondary" onClick={() => setShowModal(false)}>
                            Cancel
                        </Button>
                        <Button variant="primary" type="submit" disabled={saving || !name.trim() || !selectedLeaveType}>
                            {saving ? 'Saving...' : editingPolicy ? 'Save Changes' : 'Create Policy'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
