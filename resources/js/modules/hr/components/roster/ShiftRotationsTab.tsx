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
    assignRotation,
    cancelRotationAssignment,
    createRotationStep,
    createShiftRotation,
    deleteRotationStep,
    deleteShiftRotation,
    getShiftRotation,
    listRotationAssignments,
    listShiftRotations,
    updateRotationAssignment,
    updateRotationStep,
    updateShiftRotation,
} from '../../hrApi';
import { SHIFT_STATUS_OPTIONS } from '../../hrConstants';
import type {
    EmployeeSummary,
    HrShift,
    HrShiftRotation,
    HrShiftRotationAssignment,
    HrShiftRotationStep,
    ShiftStatus,
} from '../../hrTypes';
import { EmployeeLookupSelect } from '../EmployeeLookupSelect';
import { HrShiftRotationSelect } from '../HrShiftRotationSelect';
import { HrShiftSelect } from '../HrShiftSelect';
import { extractHrApiError } from './rosterError';

interface ShiftRotationsTabProps {
    canManage: boolean;
}

export function ShiftRotationsTab({ canManage }: ShiftRotationsTabProps) {
    const { confirm, confirmDialog } = useConfirmDialog();
    const [error, setError] = useState<string | null>(null);
    const [modalError, setModalError] = useState<string | null>(null);

    // Rotations state
    const [rotations, setRotations] = useState<HrShiftRotation[]>([]);
    const [rotMeta, setRotMeta] = useState<PaginationMeta | undefined>();
    const [rotPage, setRotPage] = useState(1);
    const [rotLoading, setRotLoading] = useState(false);

    // Rotation Modal
    const [showRotModal, setShowRotModal] = useState(false);
    const [editingRot, setEditingRot] = useState<HrShiftRotation | null>(null);
    const [rotCode, setRotCode] = useState('');
    const [rotName, setRotName] = useState('');
    const [rotCycleDays, setRotCycleDays] = useState(7);
    const [rotStatus, setRotStatus] = useState<ShiftStatus>('active');
    const [rotDesc, setRotDesc] = useState('');
    const [savingRot, setSavingRot] = useState(false);

    // Steps Modal
    const [stepsRotation, setStepsRotation] = useState<HrShiftRotation | null>(null);
    const [loadingSteps, setLoadingSteps] = useState(false);
    const [editingStep, setEditingStep] = useState<HrShiftRotationStep | null>(null);
    const [stepDay, setStepDay] = useState(1);
    const [stepShift, setStepShift] = useState<HrShift | null>(null);
    const [stepNotes, setStepNotes] = useState('');
    const [savingStep, setSavingStep] = useState(false);

    // Rotation Assignments state
    const [assignments, setAssignments] = useState<HrShiftRotationAssignment[]>([]);
    const [assignMeta, setAssignMeta] = useState<PaginationMeta | undefined>();
    const [assignPage, setAssignPage] = useState(1);
    const [assignLoading, setAssignLoading] = useState(false);
    const [filterEmployee, setFilterEmployee] = useState<EmployeeSummary | null>(null);
    const [filterRotation, setFilterRotation] = useState<HrShiftRotation | null>(null);

    // Assignment Modal
    const [showAssignModal, setShowAssignModal] = useState(false);
    const [editingAssign, setEditingAssign] = useState<HrShiftRotationAssignment | null>(null);
    const [assignEmployee, setAssignEmployee] = useState<EmployeeSummary | null>(null);
    const [assignRotationObj, setAssignRotationObj] = useState<HrShiftRotation | null>(null);
    const [anchorDate, setAnchorDate] = useState(() => businessDateInputValue());
    const [assignEndDate, setAssignEndDate] = useState('');
    const [assignIsActive, setAssignIsActive] = useState(true);
    const [savingAssign, setSavingAssign] = useState(false);

    const fetchRotations = useCallback((targetPage = rotPage) => {
        setRotLoading(true);
        setError(null);
        listShiftRotations({ page: targetPage, per_page: 25 })
            .then((res) => {
                setRotations(res.data);
                setRotMeta(res.meta);
            })
            .catch((err) => setError(extractHrApiError(err, 'Failed to load shift rotations')))
            .finally(() => setRotLoading(false));
    }, [rotPage]);

    const fetchAssignments = useCallback((targetPage = assignPage) => {
        setAssignLoading(true);
        setError(null);
        listRotationAssignments({
            employee_id: filterEmployee?.id,
            shift_rotation_id: filterRotation?.id,
            page: targetPage,
            per_page: 25,
        })
            .then((res) => {
                setAssignments(res.data);
                setAssignMeta(res.meta);
            })
            .catch((err) => setError(extractHrApiError(err, 'Failed to load rotation assignments')))
            .finally(() => setAssignLoading(false));
    }, [assignPage, filterEmployee?.id, filterRotation?.id]);

    useEffect(() => {
        queueMicrotask(() => {
            fetchRotations(rotPage);
            fetchAssignments(assignPage);
        });
    }, [fetchRotations, fetchAssignments, rotPage, assignPage]);

    // Rotation handlers
    const openCreateRot = () => {
        setEditingRot(null);
        setRotCode('');
        setRotName('');
        setRotCycleDays(7);
        setRotStatus('active');
        setRotDesc('');
        setModalError(null);
        setShowRotModal(true);
    };

    const openEditRot = (rot: HrShiftRotation) => {
        setEditingRot(rot);
        setRotCode(rot.code);
        setRotName(rot.name);
        setRotCycleDays(rot.cycle_days);
        setRotStatus(rot.status);
        setRotDesc(rot.description ?? '');
        setModalError(null);
        setShowRotModal(true);
    };

    const handleSaveRot = async (e: React.FormEvent) => {
        e.preventDefault();
        setSavingRot(true);
        setModalError(null);
        try {
            const payload = {
                code: rotCode,
                name: rotName,
                cycle_days: rotCycleDays,
                status: rotStatus,
                description: rotDesc.trim() || null,
            };
            if (editingRot) {
                await updateShiftRotation(editingRot.id, {
                    ...payload,
                    expected_version: editingRot.row_version,
                });
            } else {
                await createShiftRotation(payload);
            }
            setShowRotModal(false);
            fetchRotations(rotPage);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to save shift rotation'));
        } finally {
            setSavingRot(false);
        }
    };

    const handleDeleteRot = async (rot: HrShiftRotation) => {
        const ok = await confirm({
            title: 'Delete Shift Rotation',
            message: `Delete rotation "${rot.code} - ${rot.name}"? Rotations in use by assignments cannot be deleted.`,
            confirmLabel: 'Delete',
        });
        if (!ok) return;
        setError(null);
        try {
            await deleteShiftRotation(rot.id, rot.row_version);
            fetchRotations(rotPage);
        } catch (err) {
            setError(extractHrApiError(err, 'Failed to delete shift rotation'));
        }
    };

    // Steps handlers
    const refreshStepsRotation = async (rotationId: number) => {
        setLoadingSteps(true);
        try {
            const fresh = await getShiftRotation(rotationId);
            setStepsRotation(fresh);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to load rotation steps'));
        } finally {
            setLoadingSteps(false);
        }
    };

    const openStepsModal = (rot: HrShiftRotation) => {
        setStepsRotation(rot);
        setModalError(null);
        resetStepForm();
        refreshStepsRotation(rot.id);
    };

    const resetStepForm = () => {
        setEditingStep(null);
        setStepDay(1);
        setStepShift(null);
        setStepNotes('');
    };

    const startEditStep = (step: HrShiftRotationStep) => {
        setEditingStep(step);
        setStepDay(step.day_sequence);
        setStepShift(step.shift ?? null);
        setStepNotes(step.notes ?? '');
    };

    const handleSaveStep = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!stepsRotation) return;
        setSavingStep(true);
        setModalError(null);
        try {
            const payload = {
                day_sequence: stepDay,
                shift_id: stepShift?.id ?? null,
                notes: stepNotes.trim() || null,
            };
            if (editingStep) {
                await updateRotationStep(stepsRotation.id, editingStep.id, {
                    ...payload,
                    expected_version: editingStep.row_version,
                });
            } else {
                await createRotationStep(stepsRotation.id, payload);
            }
            resetStepForm();
            await refreshStepsRotation(stepsRotation.id);
            fetchRotations(rotPage);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to save rotation step'));
        } finally {
            setSavingStep(false);
        }
    };

    const handleDeleteStep = async (step: HrShiftRotationStep) => {
        if (!stepsRotation) return;
        const ok = await confirm({
            title: 'Delete Rotation Step',
            message: `Delete Day ${step.day_sequence} step?`,
            confirmLabel: 'Delete',
        });
        if (!ok) return;
        setModalError(null);
        try {
            await deleteRotationStep(stepsRotation.id, step.id);
            if (editingStep?.id === step.id) resetStepForm();
            await refreshStepsRotation(stepsRotation.id);
            fetchRotations(rotPage);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to delete rotation step'));
        }
    };

    // Rotation Assignment handlers
    const openCreateAssign = () => {
        setEditingAssign(null);
        setAssignEmployee(null);
        setAssignRotationObj(null);
        setAnchorDate(businessDateInputValue());
        setAssignEndDate('');
        setAssignIsActive(true);
        setModalError(null);
        setShowAssignModal(true);
    };

    const openEditAssign = (item: HrShiftRotationAssignment) => {
        setEditingAssign(item);
        setAssignEmployee(item.employee ?? null);
        setAssignRotationObj(item.rotation ?? null);
        setAnchorDate(item.anchor_date.slice(0, 10));
        setAssignEndDate(item.end_date ? item.end_date.slice(0, 10) : '');
        setAssignIsActive(item.is_active);
        setModalError(null);
        setShowAssignModal(true);
    };

    const handleSaveAssign = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!assignRotationObj || (!editingAssign && !assignEmployee)) return;
        setSavingAssign(true);
        setModalError(null);
        try {
            if (editingAssign) {
                await updateRotationAssignment(editingAssign.id, {
                    shift_rotation_id: assignRotationObj.id,
                    anchor_date: anchorDate,
                    end_date: assignEndDate || null,
                    is_active: assignIsActive,
                    expected_version: editingAssign.row_version,
                });
            } else if (assignEmployee) {
                await assignRotation({
                    employee_id: assignEmployee.id,
                    shift_rotation_id: assignRotationObj.id,
                    anchor_date: anchorDate,
                    end_date: assignEndDate || null,
                });
            }
            setShowAssignModal(false);
            fetchAssignments(assignPage);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to save rotation assignment'));
        } finally {
            setSavingAssign(false);
        }
    };

    const handleCancelAssign = async (item: HrShiftRotationAssignment) => {
        const ok = await confirm({
            title: 'Deactivate Rotation Assignment',
            message: `Deactivate rotation "${item.rotation?.name ?? ''}" for ${item.employee?.display_name ?? 'employee'}?`,
            confirmLabel: 'Deactivate',
        });
        if (!ok) return;
        setError(null);
        try {
            await cancelRotationAssignment(item.id, item.row_version);
            fetchAssignments(assignPage);
        } catch (err) {
            setError(extractHrApiError(err, 'Failed to deactivate rotation assignment'));
        }
    };

    return (
        <div className="space-y-8">
            {confirmDialog}
            {error && <div className="rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" role="alert">{error}</div>}

            {/* Section 1: Shift Rotations */}
            <div className="space-y-4">
                <div className="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 shadow-2xs">
                    <div>
                        <h3 className="text-base font-semibold text-slate-900">Shift Rotation Patterns</h3>
                        <p className="text-xs text-slate-500">Define multi-day rotating shift cycles and day-by-day step mappings.</p>
                    </div>
                    {canManage && (
                        <Button variant="primary" size="sm" onClick={openCreateRot}>
                            + New Rotation
                        </Button>
                    )}
                </div>

                {rotLoading ? (
                    <LoadingState label="Loading shift rotations..." />
                ) : (
                    <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-2xs">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Rotation</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Cycle Length</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Steps</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Status</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {rotations.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="py-8 text-center text-slate-500">No shift rotations configured.</td>
                                    </tr>
                                ) : (
                                    rotations.map((rot) => (
                                        <tr key={rot.id} className="hover:bg-slate-50">
                                            <td className="px-4 py-3">
                                                <div className="font-semibold text-slate-900">{rot.name}</div>
                                                <div className="text-xs text-slate-500">{rot.code}</div>
                                            </td>
                                            <td className="px-4 py-3 text-slate-700">{rot.cycle_days} days</td>
                                            <td className="px-4 py-3">
                                                <button
                                                    type="button"
                                                    className="text-xs font-medium text-blue-600 hover:underline"
                                                    onClick={() => openStepsModal(rot)}
                                                >
                                                    {rot.steps ? `${rot.steps.length} step(s)` : 'Manage Steps'}
                                                </button>
                                            </td>
                                            <td className="px-4 py-3"><StatusBadge status={rot.status} /></td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button variant="secondary" size="sm" onClick={() => openStepsModal(rot)}>Steps</Button>
                                                    {canManage && (
                                                        <>
                                                            <Button variant="secondary" size="sm" onClick={() => openEditRot(rot)}>Edit</Button>
                                                            <Button variant="danger" size="sm" onClick={() => handleDeleteRot(rot)}>Delete</Button>
                                                        </>
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
                <Pagination meta={rotMeta} onPageChange={setRotPage} disabled={rotLoading} />
            </div>

            {/* Section 2: Rotation Assignments */}
            <div className="space-y-4">
                <div className="flex flex-wrap items-end justify-between gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-2xs">
                    <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        <EmployeeLookupSelect
                            label="Filter by Employee"
                            value={filterEmployee}
                            onChange={(val) => {
                                setFilterEmployee(val);
                                setAssignPage(1);
                            }}
                        />
                        <HrShiftRotationSelect
                            label="Filter by Rotation"
                            value={filterRotation}
                            onChange={(val) => {
                                setFilterRotation(val);
                                setAssignPage(1);
                            }}
                        />
                    </div>
                    {canManage && (
                        <Button variant="primary" size="sm" onClick={openCreateAssign}>
                            + Assign Rotation
                        </Button>
                    )}
                </div>

                {assignLoading ? (
                    <LoadingState label="Loading rotation assignments..." />
                ) : (
                    <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-2xs">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Employee</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Rotation Pattern</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Anchor Date</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">End Date</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Status</th>
                                    {canManage && <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-700">Actions</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {assignments.length === 0 ? (
                                    <tr>
                                        <td colSpan={canManage ? 6 : 5} className="py-8 text-center text-slate-500">No rotation assignments found.</td>
                                    </tr>
                                ) : (
                                    assignments.map((item) => (
                                        <tr key={item.id} className="hover:bg-slate-50">
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-slate-900">{item.employee?.display_name ?? '-'}</div>
                                                <div className="text-xs text-slate-400">{item.employee?.employee_number ?? ''}</div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-slate-900">{item.rotation?.name ?? '-'}</div>
                                                <div className="text-xs text-slate-500">{item.rotation?.code ?? ''}</div>
                                            </td>
                                            <td className="px-4 py-3 text-slate-700">{item.anchor_date.slice(0, 10)}</td>
                                            <td className="px-4 py-3 text-slate-700">{item.end_date ? item.end_date.slice(0, 10) : 'Ongoing'}</td>
                                            <td className="px-4 py-3">
                                                <StatusBadge status={item.is_active ? 'active' : 'inactive'} />
                                            </td>
                                            {canManage && (
                                                <td className="px-4 py-3 text-right">
                                                    <div className="flex justify-end gap-2">
                                                        <Button variant="secondary" size="sm" onClick={() => openEditAssign(item)}>Edit</Button>
                                                        {item.is_active && (
                                                            <Button variant="danger" size="sm" onClick={() => handleCancelAssign(item)}>Deactivate</Button>
                                                        )}
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
                <Pagination meta={assignMeta} onPageChange={setAssignPage} disabled={assignLoading} />
            </div>

            {/* Create / Edit Rotation Modal */}
            <Modal open={showRotModal} title={editingRot ? `Edit Rotation: ${editingRot.code}` : 'Create Shift Rotation'} onClose={() => setShowRotModal(false)}>
                <form onSubmit={handleSaveRot} className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label className="text-sm font-medium text-slate-700">Code</label>
                            <input type="text" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={rotCode} onChange={(e) => setRotCode(e.target.value)} maxLength={64} required />
                        </div>
                        <div className="sm:col-span-2">
                            <label className="text-sm font-medium text-slate-700">Name</label>
                            <input type="text" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={rotName} onChange={(e) => setRotName(e.target.value)} maxLength={100} required />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="text-sm font-medium text-slate-700">Cycle Days (1-365)</label>
                            <input type="number" min={1} max={365} className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={rotCycleDays} onChange={(e) => setRotCycleDays(Number(e.target.value))} required />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-slate-700">Status</label>
                            <select className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm" value={rotStatus} onChange={(e) => setRotStatus(e.target.value as ShiftStatus)}>
                                {SHIFT_STATUS_OPTIONS.map((opt) => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-slate-700">Description</label>
                        <textarea className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" rows={2} value={rotDesc} onChange={(e) => setRotDesc(e.target.value)} />
                    </div>
                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <Button type="button" variant="secondary" onClick={() => setShowRotModal(false)}>Cancel</Button>
                        <Button type="submit" variant="primary" loading={savingRot}>
                            {editingRot ? 'Update Rotation' : 'Create Rotation'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Rotation Steps Modal */}
            <Modal
                open={stepsRotation !== null}
                title={stepsRotation ? `Rotation Steps: ${stepsRotation.name} (${stepsRotation.cycle_days}-day cycle)` : 'Rotation Steps'}
                onClose={() => setStepsRotation(null)}
            >
                <div className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    {loadingSteps ? (
                        <LoadingState label="Loading steps..." />
                    ) : (
                        <div className="overflow-x-auto rounded-lg border border-slate-200">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-700">Day #</th>
                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-700">Shift / Rest</th>
                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-700">Notes</th>
                                        {canManage && <th className="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-700">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200">
                                    {!stepsRotation?.steps || stepsRotation.steps.length === 0 ? (
                                        <tr>
                                            <td colSpan={canManage ? 4 : 3} className="py-4 text-center text-slate-500">No steps defined yet.</td>
                                        </tr>
                                    ) : (
                                        stepsRotation.steps
                                            .slice()
                                            .sort((a, b) => a.day_sequence - b.day_sequence)
                                            .map((step) => (
                                                <tr key={step.id}>
                                                    <td className="px-3 py-2 font-medium text-slate-900">Day {step.day_sequence}</td>
                                                    <td className="px-3 py-2 text-slate-700">
                                                        {step.shift ? `${step.shift.code} - ${step.shift.name}` : 'Rest Day (Off)'}
                                                    </td>
                                                    <td className="px-3 py-2 text-slate-500">{step.notes || '-'}</td>
                                                    {canManage && (
                                                        <td className="px-3 py-2 text-right">
                                                            <div className="flex justify-end gap-1.5">
                                                                <Button variant="secondary" size="sm" onClick={() => startEditStep(step)}>Edit</Button>
                                                                <Button variant="danger" size="sm" onClick={() => handleDeleteStep(step)}>Delete</Button>
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

                    {canManage && stepsRotation && (
                        <form onSubmit={handleSaveStep} className="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <h4 className="text-sm font-semibold text-slate-800">
                                {editingStep ? `Edit Day ${editingStep.day_sequence}` : 'Add Rotation Step'}
                            </h4>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <label className="text-xs font-medium text-slate-700">Day Sequence (1-{stepsRotation.cycle_days})</label>
                                    <input
                                        type="number"
                                        min={1}
                                        max={stepsRotation.cycle_days}
                                        className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm"
                                        value={stepDay}
                                        onChange={(e) => setStepDay(Number(e.target.value))}
                                        required
                                    />
                                </div>
                                <div className="sm:col-span-2">
                                    <HrShiftSelect label="Shift (leave empty for Rest Day)" value={stepShift} onChange={setStepShift} />
                                </div>
                            </div>
                            <div>
                                <label className="text-xs font-medium text-slate-700">Notes (optional)</label>
                                <input
                                    type="text"
                                    className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm"
                                    value={stepNotes}
                                    onChange={(e) => setStepNotes(e.target.value)}
                                    maxLength={255}
                                />
                            </div>
                            <div className="flex justify-end gap-2">
                                {editingStep && (
                                    <Button type="button" variant="secondary" size="sm" onClick={resetStepForm}>Cancel Edit</Button>
                                )}
                                <Button type="submit" variant="primary" size="sm" loading={savingStep}>
                                    {editingStep ? 'Update Step' : 'Add Step'}
                                </Button>
                            </div>
                        </form>
                    )}
                </div>
            </Modal>

            {/* Assign / Edit Rotation Assignment Modal */}
            <Modal open={showAssignModal} title={editingAssign ? 'Edit Rotation Assignment' : 'Assign Rotation to Employee'} onClose={() => setShowAssignModal(false)}>
                <form onSubmit={handleSaveAssign} className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    {editingAssign ? (
                        <div>
                            <label className="text-sm font-medium text-slate-700">Employee</label>
                            <div className="mt-1 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                {assignEmployee?.display_name ?? '-'} {assignEmployee?.employee_number ? `(${assignEmployee.employee_number})` : ''}
                            </div>
                        </div>
                    ) : (
                        <EmployeeLookupSelect label="Employee" value={assignEmployee} onChange={setAssignEmployee} />
                    )}
                    <HrShiftRotationSelect label="Shift Rotation" value={assignRotationObj} onChange={setAssignRotationObj} />
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="text-sm font-medium text-slate-700">Anchor Date (Day 1)</label>
                            <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={anchorDate} onChange={(e) => setAnchorDate(e.target.value)} required />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-slate-700">End Date (optional)</label>
                            <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={assignEndDate} onChange={(e) => setAssignEndDate(e.target.value)} />
                        </div>
                    </div>
                    {editingAssign && (
                        <label className="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" checked={assignIsActive} onChange={(e) => setAssignIsActive(e.target.checked)} />
                            Active assignment
                        </label>
                    )}
                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <Button type="button" variant="secondary" onClick={() => setShowAssignModal(false)}>Cancel</Button>
                        <Button type="submit" variant="primary" loading={savingAssign} disabled={!assignRotationObj || (!editingAssign && !assignEmployee)}>
                            {editingAssign ? 'Update Assignment' : 'Assign Rotation'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
