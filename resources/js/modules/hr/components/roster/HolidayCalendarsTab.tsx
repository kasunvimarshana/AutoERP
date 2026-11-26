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
    createHoliday,
    createHolidayCalendar,
    deleteHoliday,
    deleteHolidayCalendar,
    listHolidayCalendars,
    listHolidays,
    updateHoliday,
    updateHolidayCalendar,
} from '../../hrApi';
import { DEFAULT_OVERTIME_MULTIPLIER } from '../../hrConstants';
import type { HrHoliday, HrHolidayCalendar } from '../../hrTypes';
import { extractHrApiError } from './rosterError';

interface HolidayCalendarsTabProps {
    canManage: boolean;
}

export function HolidayCalendarsTab({ canManage }: HolidayCalendarsTabProps) {
    const { confirm, confirmDialog } = useConfirmDialog();
    const [error, setError] = useState<string | null>(null);
    const [modalError, setModalError] = useState<string | null>(null);

    // Calendars
    const [calendars, setCalendars] = useState<HrHolidayCalendar[]>([]);
    const [calMeta, setCalMeta] = useState<PaginationMeta | undefined>();
    const [calPage, setCalPage] = useState(1);
    const [calYearFilter, setCalYearFilter] = useState<string>('');
    const [calLoading, setCalLoading] = useState(false);
    const [selectedCalendar, setSelectedCalendar] = useState<HrHolidayCalendar | null>(null);

    // Calendar Modal
    const [showCalModal, setShowCalModal] = useState(false);
    const [editingCal, setEditingCal] = useState<HrHolidayCalendar | null>(null);
    const [calCode, setCalCode] = useState('');
    const [calName, setCalName] = useState('');
    const [calYear, setCalYear] = useState(() => Number(businessDateInputValue().slice(0, 4)));
    const [calIsDefault, setCalIsDefault] = useState(false);
    const [savingCal, setSavingCal] = useState(false);

    // Holidays
    const [holidays, setHolidays] = useState<HrHoliday[]>([]);
    const [holMeta, setHolMeta] = useState<PaginationMeta | undefined>();
    const [holPage, setHolPage] = useState(1);
    const [holLoading, setHolLoading] = useState(false);

    // Holiday Modal
    const [showHolModal, setShowHolModal] = useState(false);
    const [editingHol, setEditingHol] = useState<HrHoliday | null>(null);
    const [holName, setHolName] = useState('');
    const [holDate, setHolDate] = useState(() => businessDateInputValue());
    const [holMandatory, setHolMandatory] = useState(true);
    const [holCompOff, setHolCompOff] = useState(true);
    const [holMultiplier, setHolMultiplier] = useState(DEFAULT_OVERTIME_MULTIPLIER);
    const [holDesc, setHolDesc] = useState('');
    const [savingHol, setSavingHol] = useState(false);

    const fetchCalendars = useCallback((targetPage = calPage) => {
        setCalLoading(true);
        setError(null);
        const parsedYear = calYearFilter ? Number(calYearFilter) : undefined;
        listHolidayCalendars({
            calendar_year: parsedYear && !Number.isNaN(parsedYear) ? parsedYear : undefined,
            page: targetPage,
            per_page: 25,
        })
            .then((res) => {
                setCalendars(res.data);
                setCalMeta(res.meta);
                setSelectedCalendar((prev) => {
                    if (prev && res.data.some((c) => c.id === prev.id)) {
                        return res.data.find((c) => c.id === prev.id) ?? prev;
                    }
                    return res.data[0] ?? null;
                });
            })
            .catch((err) => setError(extractHrApiError(err, 'Failed to load holiday calendars')))
            .finally(() => setCalLoading(false));
    }, [calPage, calYearFilter]);

    const fetchHolidays = useCallback((calendarId: number, targetPage = holPage) => {
        setHolLoading(true);
        setError(null);
        listHolidays(calendarId, { page: targetPage, per_page: 50 })
            .then((res) => {
                setHolidays(res.data);
                setHolMeta(res.meta);
            })
            .catch((err) => setError(extractHrApiError(err, 'Failed to load holidays')))
            .finally(() => setHolLoading(false));
    }, [holPage]);

    useEffect(() => {
        queueMicrotask(() => {
            fetchCalendars(calPage);
        });
    }, [fetchCalendars, calPage]);

    useEffect(() => {
        if (selectedCalendar) {
            queueMicrotask(() => {
                fetchHolidays(selectedCalendar.id, holPage);
            });
        } else {
            queueMicrotask(() => {
                setHolidays([]);
                setHolMeta(undefined);
            });
        }
    }, [selectedCalendar, holPage, fetchHolidays]);

    // Calendar handlers
    const openCreateCal = () => {
        setEditingCal(null);
        setCalCode('');
        setCalName('');
        setCalYear(Number(businessDateInputValue().slice(0, 4)));
        setCalIsDefault(false);
        setModalError(null);
        setShowCalModal(true);
    };

    const openEditCal = (cal: HrHolidayCalendar) => {
        setEditingCal(cal);
        setCalCode(cal.code);
        setCalName(cal.name);
        setCalYear(cal.calendar_year);
        setCalIsDefault(cal.is_default);
        setModalError(null);
        setShowCalModal(true);
    };

    const handleSaveCal = async (e: React.FormEvent) => {
        e.preventDefault();
        setSavingCal(true);
        setModalError(null);
        try {
            const payload = {
                code: calCode,
                name: calName,
                calendar_year: calYear,
                is_default: calIsDefault,
            };
            if (editingCal) {
                await updateHolidayCalendar(editingCal.id, {
                    ...payload,
                    expected_version: editingCal.row_version,
                });
            } else {
                await createHolidayCalendar(payload);
            }
            setShowCalModal(false);
            fetchCalendars(calPage);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to save holiday calendar'));
        } finally {
            setSavingCal(false);
        }
    };

    const handleDeleteCal = async (cal: HrHolidayCalendar) => {
        const ok = await confirm({
            title: 'Delete Holiday Calendar',
            message: `Delete holiday calendar "${cal.code} - ${cal.name}" (${cal.calendar_year})?`,
            confirmLabel: 'Delete',
        });
        if (!ok) return;
        setError(null);
        try {
            await deleteHolidayCalendar(cal.id, cal.row_version);
            if (selectedCalendar?.id === cal.id) {
                setSelectedCalendar(null);
            }
            fetchCalendars(calPage);
        } catch (err) {
            setError(extractHrApiError(err, 'Failed to delete holiday calendar'));
        }
    };

    // Holiday handlers
    const openCreateHol = () => {
        setEditingHol(null);
        setHolName('');
        setHolDate(businessDateInputValue());
        setHolMandatory(true);
        setHolCompOff(true);
        setHolMultiplier(DEFAULT_OVERTIME_MULTIPLIER);
        setHolDesc('');
        setModalError(null);
        setShowHolModal(true);
    };

    const openEditHol = (hol: HrHoliday) => {
        setEditingHol(hol);
        setHolName(hol.name);
        setHolDate(hol.holiday_date.slice(0, 10));
        setHolMandatory(hol.is_mandatory);
        setHolCompOff(hol.is_comp_off_eligible);
        setHolMultiplier(String(hol.overtime_multiplier ?? DEFAULT_OVERTIME_MULTIPLIER));
        setHolDesc(hol.description ?? '');
        setModalError(null);
        setShowHolModal(true);
    };

    const handleSaveHol = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedCalendar) return;
        setSavingHol(true);
        setModalError(null);
        try {
            const payload = {
                name: holName,
                holiday_date: holDate,
                is_mandatory: holMandatory,
                is_comp_off_eligible: holCompOff,
                overtime_multiplier: holMultiplier,
                description: holDesc.trim() || null,
            };
            if (editingHol) {
                await updateHoliday(editingHol.id, {
                    ...payload,
                    expected_version: editingHol.row_version,
                });
            } else {
                await createHoliday(selectedCalendar.id, payload);
            }
            setShowHolModal(false);
            fetchHolidays(selectedCalendar.id, holPage);
            fetchCalendars(calPage);
        } catch (err) {
            setModalError(extractHrApiError(err, 'Failed to save holiday'));
        } finally {
            setSavingHol(false);
        }
    };

    const handleDeleteHol = async (hol: HrHoliday) => {
        if (!selectedCalendar) return;
        const ok = await confirm({
            title: 'Delete Holiday',
            message: `Delete holiday "${hol.name}" on ${hol.holiday_date.slice(0, 10)}?`,
            confirmLabel: 'Delete',
        });
        if (!ok) return;
        setError(null);
        try {
            await deleteHoliday(hol.id, hol.row_version);
            fetchHolidays(selectedCalendar.id, holPage);
            fetchCalendars(calPage);
        } catch (err) {
            setError(extractHrApiError(err, 'Failed to delete holiday'));
        }
    };

    return (
        <div className="space-y-6">
            {confirmDialog}
            {error && <div className="rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" role="alert">{error}</div>}

            {/* Holiday Calendars */}
            <div className="space-y-4">
                <div className="flex flex-wrap items-end justify-between gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-2xs">
                    <div className="flex items-center gap-3">
                        <div>
                            <label className="block text-xs font-medium text-slate-700">Filter Year</label>
                            <input
                                type="number"
                                min={2000}
                                max={2100}
                                placeholder="All years"
                                className="mt-1 w-32 rounded-md border border-slate-300 px-3 py-1.5 text-sm"
                                value={calYearFilter}
                                onChange={(e) => {
                                    setCalYearFilter(e.target.value);
                                    setCalPage(1);
                                }}
                            />
                        </div>
                    </div>
                    {canManage && (
                        <Button variant="primary" size="sm" onClick={openCreateCal}>
                            + New Holiday Calendar
                        </Button>
                    )}
                </div>

                {calLoading ? (
                    <LoadingState label="Loading holiday calendars..." />
                ) : (
                    <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-2xs">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Calendar</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Year</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Default</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Holidays</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {calendars.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="py-8 text-center text-slate-500">No holiday calendars found.</td>
                                    </tr>
                                ) : (
                                    calendars.map((cal) => {
                                        const isSelected = selectedCalendar?.id === cal.id;
                                        return (
                                            <tr
                                                key={cal.id}
                                                className={`cursor-pointer ${isSelected ? 'bg-blue-50/70' : 'hover:bg-slate-50'}`}
                                                onClick={() => {
                                                    setSelectedCalendar(cal);
                                                    setHolPage(1);
                                                }}
                                            >
                                                <td className="px-4 py-3">
                                                    <div className="font-semibold text-slate-900">{cal.name}</div>
                                                    <div className="text-xs text-slate-500">{cal.code}</div>
                                                </td>
                                                <td className="px-4 py-3 text-slate-700">{cal.calendar_year}</td>
                                                <td className="px-4 py-3">
                                                    {cal.is_default ? <StatusBadge status="active" label="Default" /> : <span className="text-xs text-slate-400">-</span>}
                                                </td>
                                                <td className="px-4 py-3 text-slate-700">{cal.holidays_count ?? 0}</td>
                                                <td className="px-4 py-3 text-right" onClick={(e) => e.stopPropagation()}>
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            variant="secondary"
                                                            size="sm"
                                                            onClick={() => {
                                                                setSelectedCalendar(cal);
                                                                setHolPage(1);
                                                            }}
                                                        >
                                                            View Holidays
                                                        </Button>
                                                        {canManage && (
                                                            <>
                                                                <Button variant="secondary" size="sm" onClick={() => openEditCal(cal)}>Edit</Button>
                                                                <Button variant="danger" size="sm" onClick={() => handleDeleteCal(cal)}>Delete</Button>
                                                            </>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
                <Pagination meta={calMeta} onPageChange={setCalPage} disabled={calLoading} />
            </div>

            {/* Holidays for Selected Calendar */}
            {selectedCalendar && (
                <div className="space-y-4 rounded-lg border border-slate-200 bg-white p-4 shadow-2xs">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="text-base font-semibold text-slate-900">
                                Holidays — {selectedCalendar.name} ({selectedCalendar.calendar_year})
                            </h3>
                            <p className="text-xs text-slate-500">Public and company holidays override shift schedules on their dates.</p>
                        </div>
                        {canManage && (
                            <Button variant="primary" size="sm" onClick={openCreateHol}>
                                + Add Holiday
                            </Button>
                        )}
                    </div>

                    {holLoading ? (
                        <LoadingState label="Loading holidays..." />
                    ) : (
                        <div className="overflow-x-auto rounded-lg border border-slate-200">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Date</th>
                                        <th className="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Holiday Name</th>
                                        <th className="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">Rules</th>
                                        <th className="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">OT Multiplier</th>
                                        {canManage && <th className="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-700">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200">
                                    {holidays.length === 0 ? (
                                        <tr>
                                            <td colSpan={canManage ? 5 : 4} className="py-6 text-center text-slate-500">No holidays added to this calendar yet.</td>
                                        </tr>
                                    ) : (
                                        holidays.map((hol) => (
                                            <tr key={hol.id} className="hover:bg-slate-50">
                                                <td className="px-4 py-2.5 font-medium text-slate-900">{hol.holiday_date.slice(0, 10)}</td>
                                                <td className="px-4 py-2.5">
                                                    <div className="font-medium text-slate-900">{hol.name}</div>
                                                    {hol.description && <div className="text-xs text-slate-400">{hol.description}</div>}
                                                </td>
                                                <td className="px-4 py-2.5 text-xs text-slate-600">
                                                    {hol.is_mandatory ? 'Mandatory' : 'Optional'} • {hol.is_comp_off_eligible ? 'Comp-Off Eligible' : 'No Comp-Off'}
                                                </td>
                                                <td className="px-4 py-2.5 text-slate-700">{hol.overtime_multiplier}x</td>
                                                {canManage && (
                                                    <td className="px-4 py-2.5 text-right">
                                                        <div className="flex justify-end gap-2">
                                                            <Button variant="secondary" size="sm" onClick={() => openEditHol(hol)}>Edit</Button>
                                                            <Button variant="danger" size="sm" onClick={() => handleDeleteHol(hol)}>Delete</Button>
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
                    <Pagination meta={holMeta} onPageChange={setHolPage} disabled={holLoading} />
                </div>
            )}

            {/* Calendar Modal */}
            <Modal open={showCalModal} title={editingCal ? `Edit Calendar: ${editingCal.code}` : 'Create Holiday Calendar'} onClose={() => setShowCalModal(false)}>
                <form onSubmit={handleSaveCal} className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label className="text-sm font-medium text-slate-700">Code</label>
                            <input type="text" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={calCode} onChange={(e) => setCalCode(e.target.value)} maxLength={32} required />
                        </div>
                        <div className="sm:col-span-2">
                            <label className="text-sm font-medium text-slate-700">Name</label>
                            <input type="text" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={calName} onChange={(e) => setCalName(e.target.value)} maxLength={100} required />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="text-sm font-medium text-slate-700">Calendar Year</label>
                            <input type="number" min={2000} max={2100} className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={calYear} onChange={(e) => setCalYear(Number(e.target.value))} required />
                        </div>
                        <label className="flex items-center gap-2 pt-6 text-sm text-slate-700">
                            <input type="checkbox" checked={calIsDefault} onChange={(e) => setCalIsDefault(e.target.checked)} />
                            Set as default calendar for year
                        </label>
                    </div>
                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <Button type="button" variant="secondary" onClick={() => setShowCalModal(false)}>Cancel</Button>
                        <Button type="submit" variant="primary" loading={savingCal}>
                            {editingCal ? 'Update Calendar' : 'Create Calendar'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Holiday Modal */}
            <Modal open={showHolModal} title={editingHol ? `Edit Holiday: ${editingHol.name}` : 'Add Holiday'} onClose={() => setShowHolModal(false)}>
                <form onSubmit={handleSaveHol} className="space-y-4">
                    {modalError && <div className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{modalError}</div>}
                    <div>
                        <label className="text-sm font-medium text-slate-700">Holiday Name</label>
                        <input type="text" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={holName} onChange={(e) => setHolName(e.target.value)} maxLength={100} required />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="text-sm font-medium text-slate-700">Date</label>
                            <input type="date" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={holDate} onChange={(e) => setHolDate(e.target.value)} required />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-slate-700">Overtime Multiplier (1-10)</label>
                            <input type="number" step="0.25" min={1} max={10} className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={holMultiplier} onChange={(e) => setHolMultiplier(e.target.value)} required />
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-4 text-sm text-slate-700">
                        <label className="flex items-center gap-2">
                            <input type="checkbox" checked={holMandatory} onChange={(e) => setHolMandatory(e.target.checked)} />
                            Mandatory Holiday
                        </label>
                        <label className="flex items-center gap-2">
                            <input type="checkbox" checked={holCompOff} onChange={(e) => setHolCompOff(e.target.checked)} />
                            Comp-Off Eligible
                        </label>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-slate-700">Description (optional)</label>
                        <textarea className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" rows={2} value={holDesc} onChange={(e) => setHolDesc(e.target.value)} />
                    </div>
                    <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <Button type="button" variant="secondary" onClick={() => setShowHolModal(false)}>Cancel</Button>
                        <Button type="submit" variant="primary" loading={savingHol}>
                            {editingHol ? 'Update Holiday' : 'Add Holiday'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
