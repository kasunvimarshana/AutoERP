/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useCallback, useEffect, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { StatusBadge } from '@/shared/components/StatusBadge';
import {
    createAttendanceGeofence,
    createAttendanceKiosk,
    deleteAttendanceGeofence,
    deleteAttendanceKiosk,
    listAttendanceGeofences,
    listAttendanceKiosks,
    updateAttendanceGeofence,
    updateAttendanceKiosk,
} from '../../hrApi';
import type { HrAttendanceGeofence, HrAttendanceKiosk } from '../../hrTypes';

interface Props {
    canManage: boolean;
    onKiosksChanged?: () => void;
}

export function GeofencesAndKiosksTab({ canManage, onKiosksChanged }: Props) {
    const [geofences, setGeofences] = useState<HrAttendanceGeofence[]>([]);
    const [kiosks, setKiosks] = useState<HrAttendanceKiosk[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [modalError, setModalError] = useState<ApiError | null>(null);
    const { confirm, confirmDialog } = useConfirmDialog();

    // Geofence modal state
    const [showGeofenceModal, setShowGeofenceModal] = useState(false);
    const [editingGeofence, setEditingGeofence] = useState<HrAttendanceGeofence | null>(null);
    const [gfName, setGfName] = useState('');
    const [gfCode, setGfCode] = useState('');
    const [gfLatitude, setGfLatitude] = useState('');
    const [gfLongitude, setGfLongitude] = useState('');
    const [gfRadius, setGfRadius] = useState(150);
    const [gfIpWhitelist, setGfIpWhitelist] = useState('');
    const [gfNotes, setGfNotes] = useState('');
    const [gfActive, setGfActive] = useState(true);
    const [gfSubmitting, setGfSubmitting] = useState(false);

    // Kiosk modal state
    const [showKioskModal, setShowKioskModal] = useState(false);
    const [editingKiosk, setEditingKiosk] = useState<HrAttendanceKiosk | null>(null);
    const [kioskCode, setKioskCode] = useState('');
    const [kioskName, setKioskName] = useState('');
    const [kioskRotation, setKioskRotation] = useState(20);
    const [kioskGeofenceId, setKioskGeofenceId] = useState<string>('');
    const [kioskActive, setKioskActive] = useState(true);
    const [kioskSubmitting, setKioskSubmitting] = useState(false);

    const loadAll = useCallback(() => {
        setLoading(true);
        setError(null);
        Promise.all([
            listAttendanceGeofences({ per_page: 100 }),
            listAttendanceKiosks({ per_page: 100 }),
        ])
            .then(([gfRes, kioskRes]) => {
                setGeofences(gfRes.data);
                setKiosks(kioskRes.data);
            })
            .catch((err) => setError(toApiError(err)))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        queueMicrotask(() => {
            loadAll();
        });
    }, [loadAll]);

    const openCreateGeofence = () => {
        setEditingGeofence(null);
        setGfName('');
        setGfCode('');
        setGfLatitude('');
        setGfLongitude('');
        setGfRadius(150);
        setGfIpWhitelist('');
        setGfNotes('');
        setGfActive(true);
        setModalError(null);
        setShowGeofenceModal(true);
    };

    const openEditGeofence = (gf: HrAttendanceGeofence) => {
        setEditingGeofence(gf);
        setGfName(gf.name);
        setGfCode(gf.code);
        setGfLatitude(String(gf.latitude));
        setGfLongitude(String(gf.longitude));
        setGfRadius(gf.radius_meters);
        setGfIpWhitelist(gf.ip_whitelist ?? '');
        setGfNotes(gf.notes ?? '');
        setGfActive(gf.is_active);
        setModalError(null);
        setShowGeofenceModal(true);
    };

    const handleSaveGeofence = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!canManage || gfSubmitting) return;
        setGfSubmitting(true);
        setModalError(null);
        try {
            const payload = {
                name: gfName.trim(),
                code: gfCode.trim().toUpperCase(),
                latitude: Number(gfLatitude),
                longitude: Number(gfLongitude),
                radius_meters: Number(gfRadius),
                ip_whitelist: gfIpWhitelist.trim() || null,
                notes: gfNotes.trim() || null,
                is_active: gfActive,
            };
            if (editingGeofence) {
                await updateAttendanceGeofence(editingGeofence.id, {
                    ...payload,
                    expected_version: editingGeofence.row_version,
                });
            } else {
                await createAttendanceGeofence(payload);
            }
            setShowGeofenceModal(false);
            loadAll();
        } catch (err: unknown) {
            setModalError(toApiError(err));
        } finally {
            setGfSubmitting(false);
        }
    };

    const handleDeleteGeofence = async (gf: HrAttendanceGeofence) => {
        if (!canManage) return;
        const ok = await confirm({
            title: 'Delete Geofence',
            message: `Delete geofence "${gf.name}" (${gf.code})?`,
            confirmLabel: 'Delete',
            danger: true,
        });
        if (!ok) return;
        setError(null);
        try {
            await deleteAttendanceGeofence(gf.id);
            loadAll();
        } catch (err: unknown) {
            setError(toApiError(err));
        }
    };

    const openCreateKiosk = () => {
        setEditingKiosk(null);
        setKioskCode('');
        setKioskName('');
        setKioskRotation(20);
        setKioskGeofenceId('');
        setKioskActive(true);
        setModalError(null);
        setShowKioskModal(true);
    };

    const openEditKiosk = (kiosk: HrAttendanceKiosk) => {
        setEditingKiosk(kiosk);
        setKioskCode(kiosk.kiosk_code);
        setKioskName(kiosk.name);
        setKioskRotation(kiosk.rotation_seconds);
        setKioskGeofenceId(kiosk.geofence_id ? String(kiosk.geofence_id) : '');
        setKioskActive(kiosk.is_active);
        setModalError(null);
        setShowKioskModal(true);
    };

    const handleSaveKiosk = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!canManage || kioskSubmitting) return;
        setKioskSubmitting(true);
        setModalError(null);
        try {
            const payload = {
                kiosk_code: kioskCode.trim().toUpperCase(),
                name: kioskName.trim(),
                rotation_seconds: Number(kioskRotation),
                geofence_id: kioskGeofenceId ? Number(kioskGeofenceId) : null,
                is_active: kioskActive,
            };
            if (editingKiosk) {
                await updateAttendanceKiosk(editingKiosk.id, {
                    ...payload,
                    expected_version: editingKiosk.row_version,
                });
            } else {
                await createAttendanceKiosk(payload);
            }
            setShowKioskModal(false);
            loadAll();
            onKiosksChanged?.();
        } catch (err: unknown) {
            setModalError(toApiError(err));
        } finally {
            setKioskSubmitting(false);
        }
    };

    const handleDeleteKiosk = async (kiosk: HrAttendanceKiosk) => {
        if (!canManage) return;
        const ok = await confirm({
            title: 'Delete Kiosk',
            message: `Delete kiosk "${kiosk.name}" (${kiosk.kiosk_code})?`,
            confirmLabel: 'Delete',
            danger: true,
        });
        if (!ok) return;
        setError(null);
        try {
            await deleteAttendanceKiosk(kiosk.id);
            loadAll();
            onKiosksChanged?.();
        } catch (err: unknown) {
            setError(toApiError(err));
        }
    };

    if (loading && geofences.length === 0 && kiosks.length === 0) {
        return <LoadingState label="Loading geofences and kiosks..." />;
    }

    return (
        <div className="space-y-8">
            <ErrorAlert error={error} inline />

            {/* Geofences Section */}
            <section className="space-y-3">
                <div className="flex items-center justify-between">
                    <div>
                        <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">
                            Attendance Geofences
                        </h3>
                        <p className="text-sm text-gray-500">
                            Define GPS perimeter boundaries and optional IP whitelists for mobile and kiosk attendance capture.
                        </p>
                    </div>
                    {canManage && (
                        <Button variant="primary" onClick={openCreateGeofence}>
                            New Geofence
                        </Button>
                    )}
                </div>

                <div className="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table className="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-700">
                        <thead className="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Code</th>
                                <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Name</th>
                                <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Coordinates</th>
                                <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Radius</th>
                                <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Status</th>
                                {canManage && <th className="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Actions</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            {geofences.length === 0 ? (
                                <tr>
                                    <td colSpan={canManage ? 6 : 5} className="py-6 text-center text-gray-500">
                                        No attendance geofences configured.
                                    </td>
                                </tr>
                            ) : (
                                geofences.map((gf) => (
                                    <tr key={gf.id}>
                                        <td className="px-4 py-3 font-mono text-xs font-semibold text-gray-900 dark:text-gray-100">
                                            {gf.code}
                                        </td>
                                        <td className="px-4 py-3 text-gray-800 dark:text-gray-200">{gf.name}</td>
                                        <td className="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">
                                            {gf.latitude}, {gf.longitude}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700 dark:text-gray-300">
                                            {gf.radius_meters} m
                                        </td>
                                        <td className="px-4 py-3">
                                            <StatusBadge status={gf.is_active ? 'active' : 'inactive'} />
                                        </td>
                                        {canManage && (
                                            <td className="px-4 py-3 text-right space-x-2">
                                                <Button variant="secondary" onClick={() => openEditGeofence(gf)}>
                                                    Edit
                                                </Button>
                                                <Button variant="danger" onClick={() => handleDeleteGeofence(gf)}>
                                                    Delete
                                                </Button>
                                            </td>
                                        )}
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </section>

            {/* Kiosks Section */}
            <section className="space-y-3">
                <div className="flex items-center justify-between">
                    <div>
                        <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">
                            Dynamic QR Kiosks
                        </h3>
                        <p className="text-sm text-gray-500">
                            Configure stationary QR kiosk terminals and link them to geofences.
                        </p>
                    </div>
                    {canManage && (
                        <Button variant="primary" onClick={openCreateKiosk}>
                            New Kiosk
                        </Button>
                    )}
                </div>

                <div className="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table className="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-700">
                        <thead className="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Kiosk Code</th>
                                <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Name</th>
                                <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Geofence</th>
                                <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Rotation</th>
                                <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Status</th>
                                {canManage && <th className="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Actions</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            {kiosks.length === 0 ? (
                                <tr>
                                    <td colSpan={canManage ? 6 : 5} className="py-6 text-center text-gray-500">
                                        No attendance kiosks configured.
                                    </td>
                                </tr>
                            ) : (
                                kiosks.map((k) => (
                                    <tr key={k.id}>
                                        <td className="px-4 py-3 font-mono text-xs font-semibold text-gray-900 dark:text-gray-100">
                                            {k.kiosk_code}
                                        </td>
                                        <td className="px-4 py-3 text-gray-800 dark:text-gray-200">{k.name}</td>
                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                            {k.geofence ? `${k.geofence.name} (${k.geofence.code})` : 'Unassigned'}
                                        </td>
                                        <td className="px-4 py-3 text-gray-700 dark:text-gray-300">
                                            {k.rotation_seconds}s
                                        </td>
                                        <td className="px-4 py-3">
                                            <StatusBadge status={k.is_active ? 'active' : 'inactive'} />
                                        </td>
                                        {canManage && (
                                            <td className="px-4 py-3 text-right space-x-2">
                                                <Button variant="secondary" onClick={() => openEditKiosk(k)}>
                                                    Edit
                                                </Button>
                                                <Button variant="danger" onClick={() => handleDeleteKiosk(k)}>
                                                    Delete
                                                </Button>
                                            </td>
                                        )}
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </section>

            {/* Modal: Create / Edit Geofence */}
            <Modal
                open={showGeofenceModal}
                title={editingGeofence ? 'Edit Geofence' : 'New Geofence'}
                onClose={() => setShowGeofenceModal(false)}
                closeDisabled={gfSubmitting}
            >
                <form onSubmit={handleSaveGeofence} className="space-y-4">
                    <ErrorAlert error={modalError} inline />
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Code</label>
                            <input
                                type="text"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={gfCode}
                                onChange={(e) => setGfCode(e.target.value)}
                                placeholder="HQ-GATE"
                                required
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                            <input
                                type="text"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={gfName}
                                onChange={(e) => setGfName(e.target.value)}
                                placeholder="Main Office Perimeter"
                                required
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Latitude</label>
                            <input
                                type="number"
                                step="any"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={gfLatitude}
                                onChange={(e) => setGfLatitude(e.target.value)}
                                placeholder="6.9271"
                                required
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Longitude</label>
                            <input
                                type="number"
                                step="any"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={gfLongitude}
                                onChange={(e) => setGfLongitude(e.target.value)}
                                placeholder="79.8612"
                                required
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Radius (meters)</label>
                            <input
                                type="number"
                                min={10}
                                max={50000}
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={gfRadius}
                                onChange={(e) => setGfRadius(Number(e.target.value))}
                                required
                            />
                        </div>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">IP Whitelist (Optional)</label>
                        <input
                            type="text"
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            value={gfIpWhitelist}
                            onChange={(e) => setGfIpWhitelist(e.target.value)}
                            placeholder="192.168.1.0/24"
                        />
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Notes (Optional)</label>
                        <textarea
                            rows={2}
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            value={gfNotes}
                            onChange={(e) => setGfNotes(e.target.value)}
                        />
                    </div>
                    <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input
                            type="checkbox"
                            checked={gfActive}
                            onChange={(e) => setGfActive(e.target.checked)}
                        />
                        Active
                    </label>
                    <div className="flex justify-end gap-2">
                        <Button variant="secondary" type="button" onClick={() => setShowGeofenceModal(false)}>
                            Cancel
                        </Button>
                        <Button variant="primary" type="submit" disabled={gfSubmitting}>
                            {gfSubmitting ? 'Saving...' : 'Save Geofence'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Modal: Create / Edit Kiosk */}
            <Modal
                open={showKioskModal}
                title={editingKiosk ? 'Edit Kiosk' : 'New Kiosk'}
                onClose={() => setShowKioskModal(false)}
                closeDisabled={kioskSubmitting}
            >
                <form onSubmit={handleSaveKiosk} className="space-y-4">
                    <ErrorAlert error={modalError} inline />
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Kiosk Code</label>
                            <input
                                type="text"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={kioskCode}
                                onChange={(e) => setKioskCode(e.target.value)}
                                placeholder="KIOSK-LOBBY-01"
                                required
                            />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Kiosk Name</label>
                            <input
                                type="text"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={kioskName}
                                onChange={(e) => setKioskName(e.target.value)}
                                placeholder="Main Lobby Tablet"
                                required
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">Linked Geofence</label>
                            <select
                                className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={kioskGeofenceId}
                                onChange={(e) => setKioskGeofenceId(e.target.value)}
                            >
                                <option value="">None (Unrestricted)</option>
                                {geofences.map((gf) => (
                                    <option key={gf.id} value={gf.id}>
                                        {gf.name} ({gf.code})
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">QR Rotation (seconds)</label>
                            <input
                                type="number"
                                min={10}
                                max={300}
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={kioskRotation}
                                onChange={(e) => setKioskRotation(Number(e.target.value))}
                                required
                            />
                        </div>
                    </div>
                    <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input
                            type="checkbox"
                            checked={kioskActive}
                            onChange={(e) => setKioskActive(e.target.checked)}
                        />
                        Active
                    </label>
                    <div className="flex justify-end gap-2">
                        <Button variant="secondary" type="button" onClick={() => setShowKioskModal(false)}>
                            Cancel
                        </Button>
                        <Button variant="primary" type="submit" disabled={kioskSubmitting}>
                            {kioskSubmitting ? 'Saving...' : 'Save Kiosk'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {confirmDialog}
        </div>
    );
}
