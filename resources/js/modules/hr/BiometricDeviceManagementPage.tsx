/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useEffect, useState, useCallback } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { AccessDeniedPage } from '@/app/errors/AccessDeniedPage';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Button } from '@/shared/components/Button';
import { LoadingState } from '@/shared/components/LoadingState';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Modal } from '@/shared/components/Modal';
import { EmployeeLookupSelect } from './components/EmployeeLookupSelect';
import {
    createBiometricDevice,
    deleteBiometricDevice,
    listBiometricDeviceUsers,
    listBiometricDevices,
    listBiometricRawLogs,
    mapBiometricDeviceUser,
    pingBiometricDevice,
    reprocessBiometricDeviceLogs,
    syncBiometricDevice,
    unmapBiometricDeviceUser,
    updateBiometricDevice,
    uploadBiometricFileLog,
} from './hrApi';
import { hrPermissions } from './hrPermissions';
import type {
    BiometricProtocol,
    DeviceHealthResult,
    EmployeeSummary,
    HrBiometricDevice,
    HrBiometricDevicePayload,
    HrBiometricDeviceUser,
    HrBiometricRawPunch,
} from './hrTypes';

export default function BiometricDeviceManagementPage() {
    const auth = useAuth();
    const canView = hasPermission(auth, hrPermissions.biometricView);
    const canManage = hasPermission(auth, hrPermissions.biometricManage);
    const canSync = hasPermission(auth, hrPermissions.biometricSync);

    const [devices, setDevices] = useState<HrBiometricDevice[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);

    // Filters
    const [protocolFilter, setProtocolFilter] = useState<string>('');
    const [statusFilter, setStatusFilter] = useState<string>('');
    const [searchFilter, setSearchFilter] = useState<string>('');

    // Add / Edit Modal state
    const [showDeviceModal, setShowDeviceModal] = useState(false);
    const [editingDevice, setEditingDevice] = useState<HrBiometricDevice | null>(null);
    const [deviceCode, setDeviceCode] = useState('');
    const [deviceName, setDeviceName] = useState('');
    const [protocol, setProtocol] = useState<BiometricProtocol>('zkteco_push');
    const [ipAddress, setIpAddress] = useState('');
    const [port, setPort] = useState<string>('');
    const [serialNumber, setSerialNumber] = useState('');
    const [commKey, setCommKey] = useState('');
    const [authToken, setAuthToken] = useState('');
    const [isEnabled, setIsEnabled] = useState(true);
    const [autoMapUsers, setAutoMapUsers] = useState(true);
    const [deviceSubmitting, setDeviceSubmitting] = useState(false);

    // Diagnostics Modal
    const [showPingModal, setShowPingModal] = useState(false);
    const [pingResult, setPingResult] = useState<DeviceHealthResult | null>(null);
    const [pinging, setPinging] = useState(false);
    const [activeDeviceForPing, setActiveDeviceForPing] = useState<HrBiometricDevice | null>(null);

    // Users Mapping Modal
    const [showUsersModal, setShowUsersModal] = useState(false);
    const [activeDeviceForUsers, setActiveDeviceForUsers] = useState<HrBiometricDevice | null>(null);
    const [deviceUsers, setDeviceUsers] = useState<HrBiometricDeviceUser[]>([]);
    const [usersLoading, setUsersLoading] = useState(false);
    const [mappingPin, setMappingPin] = useState('');
    const [mappingCard, setMappingCard] = useState('');
    const [mappingEmployee, setMappingEmployee] = useState<EmployeeSummary | null>(null);
    const [mappingSubmitting, setMappingSubmitting] = useState(false);

    // Raw Logs Modal
    const [showLogsModal, setShowLogsModal] = useState(false);
    const [activeDeviceForLogs, setActiveDeviceForLogs] = useState<HrBiometricDevice | null>(null);
    const [rawLogs, setRawLogs] = useState<HrBiometricRawPunch[]>([]);
    const [logsLoading, setLogsLoading] = useState(false);
    const [logStatusFilter, setLogStatusFilter] = useState<string>('');
    const [logDateFilter, setLogDateFilter] = useState<string>('');

    // USB File Upload Modal
    const [showUploadModal, setShowUploadModal] = useState(false);
    const [activeDeviceForUpload, setActiveDeviceForUpload] = useState<HrBiometricDevice | null>(null);
    const [uploadFile, setUploadFile] = useState<File | null>(null);
    const [uploading, setUploading] = useState(false);

    const loadDevices = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const res = await listBiometricDevices({
                protocol: protocolFilter || undefined,
                status: statusFilter || undefined,
                search: searchFilter || undefined,
                per_page: 50,
            });
            setDevices(res.data || []);
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Failed to load biometric devices');
        } finally {
            setLoading(false);
        }
    }, [protocolFilter, statusFilter, searchFilter]);

    useEffect(() => {
        if (!canView) return;
        queueMicrotask(() => {
            void loadDevices();
        });
    }, [canView, loadDevices]);

    if (!canView) {
        return <AccessDeniedPage message="You do not have permission to view biometric attendance devices." />;
    }

    const openAddDevice = () => {
        setEditingDevice(null);
        setDeviceCode(`BIO-${String(devices.length + 1).padStart(3, '0')}`);
        setDeviceName('');
        setProtocol('zkteco_push');
        setIpAddress('');
        setPort('80');
        setSerialNumber('');
        setCommKey('0');
        setAuthToken('');
        setIsEnabled(true);
        setAutoMapUsers(true);
        setShowDeviceModal(true);
    };

    const openEditDevice = (dev: HrBiometricDevice) => {
        setEditingDevice(dev);
        setDeviceCode(dev.device_code);
        setDeviceName(dev.name);
        setProtocol(dev.protocol);
        setIpAddress(dev.ip_address || '');
        setPort(dev.port ? String(dev.port) : '');
        setSerialNumber(dev.serial_number || '');
        setCommKey(dev.comm_key || '');
        setAuthToken(dev.auth_token || '');
        setIsEnabled(dev.is_enabled);
        setAutoMapUsers(dev.auto_map_users);
        setShowDeviceModal(true);
    };

    const handleSaveDevice = async (e: React.FormEvent) => {
        e.preventDefault();
        setDeviceSubmitting(true);
        setError(null);

        const payload: HrBiometricDevicePayload = {
            device_code: deviceCode,
            name: deviceName,
            protocol,
            ip_address: ipAddress || null,
            port: port ? Number(port) : null,
            serial_number: serialNumber || null,
            comm_key: commKey || null,
            auth_token: authToken || null,
            status: editingDevice ? editingDevice.status : 'active',
            is_enabled: isEnabled,
            auto_map_users: autoMapUsers,
            row_version: editingDevice ? editingDevice.row_version : 1,
            organization_unit_id: editingDevice?.organization_unit_id ?? null,
        };

        try {
            if (editingDevice) {
                await updateBiometricDevice(editingDevice.id, {
                    ...payload,
                    expected_version: editingDevice.row_version,
                });
                setSuccessMessage(`Device [${deviceName}] updated successfully.`);
            } else {
                await createBiometricDevice(payload);
                setSuccessMessage(`Device [${deviceName}] created successfully.`);
            }
            setShowDeviceModal(false);
            loadDevices();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Failed to save biometric device');
        } finally {
            setDeviceSubmitting(false);
        }
    };

    const handleDeleteDevice = async (dev: HrBiometricDevice) => {
        if (!window.confirm(`Are you sure you want to delete or disable device [${dev.name}]?`)) {
            return;
        }
        try {
            await deleteBiometricDevice(dev.id);
            setSuccessMessage(`Device [${dev.name}] deleted/disabled.`);
            loadDevices();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Failed to delete device');
        }
    };

    const handlePing = async (dev: HrBiometricDevice) => {
        setActiveDeviceForPing(dev);
        setShowPingModal(true);
        setPinging(true);
        setPingResult(null);

        try {
            const res = await pingBiometricDevice(dev.id);
            setPingResult(res);
            loadDevices();
        } catch (err: unknown) {
            setPingResult({
                isReachable: false,
                errorMessage: err instanceof Error ? err.message : 'Ping request failed',
            });
        } finally {
            setPinging(false);
        }
    };

    const handleSync = async (dev: HrBiometricDevice) => {
        try {
            const res = await syncBiometricDevice(dev.id);
            setSuccessMessage(`Sync completed for [${dev.name}]. ${JSON.stringify(res)}`);
            loadDevices();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Device sync failed');
        }
    };

    const openUsersModal = async (dev: HrBiometricDevice) => {
        setActiveDeviceForUsers(dev);
        setShowUsersModal(true);
        setUsersLoading(true);
        setMappingPin('');
        setMappingCard('');
        setMappingEmployee(null);

        try {
            const res = await listBiometricDeviceUsers(dev.id);
            setDeviceUsers(res || []);
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Failed to load mapped users');
        } finally {
            setUsersLoading(false);
        }
    };

    const handleAddUserMapping = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!activeDeviceForUsers || !mappingEmployee || !mappingPin) return;

        setMappingSubmitting(true);
        try {
            await mapBiometricDeviceUser(activeDeviceForUsers.id, {
                biometric_user_id: mappingPin,
                employee_id: mappingEmployee.id,
                card_number: mappingCard || undefined,
            });
            setSuccessMessage(`Mapped PIN [${mappingPin}] to [${mappingEmployee.display_name}].`);
            setMappingPin('');
            setMappingCard('');
            setMappingEmployee(null);

            const res = await listBiometricDeviceUsers(activeDeviceForUsers.id);
            setDeviceUsers(res || []);
            loadDevices();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Failed to map user');
        } finally {
            setMappingSubmitting(false);
        }
    };

    const handleRemoveUserMapping = async (mappingId: number) => {
        if (!activeDeviceForUsers) return;
        try {
            await unmapBiometricDeviceUser(activeDeviceForUsers.id, mappingId);
            setSuccessMessage('User mapping removed.');
            const res = await listBiometricDeviceUsers(activeDeviceForUsers.id);
            setDeviceUsers(res || []);
            loadDevices();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Failed to unmap user');
        }
    };

    const handleReprocessLogs = async () => {
        if (!activeDeviceForUsers) return;
        try {
            const res = await reprocessBiometricDeviceLogs(activeDeviceForUsers.id);
            setSuccessMessage(res.message);
            loadDevices();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Failed to reprocess unmapped logs');
        }
    };

    const openRawLogsModal = async (dev: HrBiometricDevice) => {
        setActiveDeviceForLogs(dev);
        setShowLogsModal(true);
        setLogsLoading(true);

        try {
            const res = await listBiometricRawLogs(dev.id, {
                sync_status: logStatusFilter || undefined,
                date: logDateFilter || undefined,
            });
            setRawLogs(res.data || []);
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Failed to load raw logs');
        } finally {
            setLogsLoading(false);
        }
    };

    const handleFilterLogs = async () => {
        if (!activeDeviceForLogs) return;
        setLogsLoading(true);
        try {
            const res = await listBiometricRawLogs(activeDeviceForLogs.id, {
                sync_status: logStatusFilter || undefined,
                date: logDateFilter || undefined,
            });
            setRawLogs(res.data || []);
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Failed to filter logs');
        } finally {
            setLogsLoading(false);
        }
    };

    const openUploadModal = (dev: HrBiometricDevice) => {
        setActiveDeviceForUpload(dev);
        setUploadFile(null);
        setShowUploadModal(true);
    };

    const handleFileUpload = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!activeDeviceForUpload || !uploadFile) return;

        setUploading(true);
        try {
            const res = await uploadBiometricFileLog(activeDeviceForUpload.id, uploadFile);
            setSuccessMessage(res.message);
            setShowUploadModal(false);
            loadDevices();
        } catch (err: unknown) {
            setError(err instanceof Error ? err.message : 'Failed to import file');
        } finally {
            setUploading(false);
        }
    };

    // Calculate Summary Counts
    const totalDevices = devices.length;
    const onlineDevices = devices.filter((d) => d.status === 'active').length;
    const offlineDevices = devices.filter((d) => d.status === 'offline' || d.status === 'error').length;
    const totalPunches = devices.reduce((sum, d) => sum + (d.total_punches_synced || 0), 0);

    return (
        <div className="space-y-6 p-6">
            <ContentHeader
                title="Biometric Device Management"
                description="Connect and monitor ZKTeco, Hikvision, Dahua, and Cloud attendance hardware with automated deduplication."
                actions={
                    canManage ? (
                        <Button variant="primary" onClick={openAddDevice}>
                            + Add Biometric Device
                        </Button>
                    ) : undefined
                }
            />

            {error && (
                <div className="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-400">
                    {error}
                </div>
            )}

            {successMessage && (
                <div className="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                    {successMessage}
                </div>
            )}

            {/* Metrics Grid */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <p className="text-xs font-medium uppercase tracking-wider text-slate-500">Configured Devices</p>
                    <p className="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{totalDevices}</p>
                </div>
                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <p className="text-xs font-medium uppercase tracking-wider text-emerald-600">Active / Online</p>
                    <p className="mt-2 text-2xl font-bold text-emerald-600">{onlineDevices}</p>
                </div>
                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <p className="text-xs font-medium uppercase tracking-wider text-rose-600">Offline / Errors</p>
                    <p className="mt-2 text-2xl font-bold text-rose-600">{offlineDevices}</p>
                </div>
                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                    <p className="text-xs font-medium uppercase tracking-wider text-indigo-600">Total Punches Synced</p>
                    <p className="mt-2 text-2xl font-bold text-indigo-600">{totalPunches.toLocaleString()}</p>
                </div>
            </div>

            {/* Filters Bar */}
            <div className="flex flex-wrap items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div className="flex-1 min-w-[200px]">
                    <input
                        type="text"
                        placeholder="Search device name, code, IP, serial..."
                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        value={searchFilter}
                        onChange={(e) => setSearchFilter(e.target.value)}
                    />
                </div>
                <div className="w-48">
                    <select
                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        value={protocolFilter}
                        onChange={(e) => setProtocolFilter(e.target.value)}
                    >
                        <option value="">All Protocols</option>
                        <option value="zkteco_push">ZKTeco ADMS / Push</option>
                        <option value="zkteco_socket">ZKTeco TCP Socket</option>
                        <option value="hikvision_isapi">Hikvision ISAPI</option>
                        <option value="dahua_cgi">Dahua CGI</option>
                        <option value="generic_webhook">Generic Webhook</option>
                        <option value="usb_import">USB File Import</option>
                    </select>
                </div>
                <div className="w-40">
                    <select
                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="offline">Offline</option>
                        <option value="inactive">Inactive</option>
                        <option value="error">Error</option>
                    </select>
                </div>
                <Button variant="secondary" onClick={loadDevices}>
                    Filter
                </Button>
            </div>

            {/* Devices Table */}
            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
                {loading ? (
                    <LoadingState label="Loading biometric devices..." />
                ) : devices.length === 0 ? (
                    <div className="p-12 text-center text-slate-500">
                        No biometric devices configured yet. Click "+ Add Biometric Device" to register one.
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                            <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50">
                                <tr>
                                    <th className="px-4 py-3">Device Code & Name</th>
                                    <th className="px-4 py-3">Protocol</th>
                                    <th className="px-4 py-3">Endpoint / Serial</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3">Last Heartbeat / Sync</th>
                                    <th className="px-4 py-3 text-right">Punches Synced</th>
                                    <th className="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
                                {devices.map((dev) => (
                                    <tr key={dev.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                        <td className="px-4 py-3">
                                            <div className="font-semibold text-slate-900 dark:text-slate-100">{dev.name}</div>
                                            <div className="text-xs text-slate-400 font-mono">{dev.device_code}</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                {dev.protocol.replace('_', ' ').toUpperCase()}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 font-mono text-xs">
                                            {dev.ip_address ? `${dev.ip_address}:${dev.port || 80}` : dev.serial_number || 'Cloud Webhook'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <StatusBadge status={dev.status} />
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            <div>Sync: {dev.last_sync_at ? new Date(dev.last_sync_at).toLocaleString() : 'Never'}</div>
                                            <div className="text-slate-400">Ping: {dev.last_ping_at ? new Date(dev.last_ping_at).toLocaleTimeString() : 'Never'}</div>
                                        </td>
                                        <td className="px-4 py-3 text-right font-medium text-slate-900 dark:text-slate-100">
                                            {dev.total_punches_synced.toLocaleString()}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-1.5">
                                                <Button size="sm" variant="ghost" onClick={() => handlePing(dev)} title="Diagnostics & Ping">
                                                    Ping
                                                </Button>
                                                {canSync && (
                                                    <Button size="sm" variant="ghost" onClick={() => handleSync(dev)} title="Trigger Log Sync">
                                                        Sync
                                                    </Button>
                                                )}
                                                <Button size="sm" variant="ghost" onClick={() => openUsersModal(dev)} title="Mapped Users">
                                                    Users ({dev.device_users_count ?? 0})
                                                </Button>
                                                <Button size="sm" variant="ghost" onClick={() => openRawLogsModal(dev)} title="Raw Punch Logs">
                                                    Logs
                                                </Button>
                                                {dev.protocol === 'usb_import' && (
                                                    <Button size="sm" variant="ghost" onClick={() => openUploadModal(dev)} title="Upload Flash File">
                                                        Upload
                                                    </Button>
                                                )}
                                                {canManage && (
                                                    <>
                                                        <Button size="sm" variant="ghost" onClick={() => openEditDevice(dev)}>
                                                            Edit
                                                        </Button>
                                                        <Button size="sm" variant="danger" onClick={() => handleDeleteDevice(dev)}>
                                                            Delete
                                                        </Button>
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {/* Add / Edit Device Modal */}
            <Modal
                open={showDeviceModal}
                title={editingDevice ? `Edit Device [${editingDevice.name}]` : 'Add New Biometric Device'}
                onClose={() => setShowDeviceModal(false)}
            >
                <form onSubmit={handleSaveDevice} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="text-xs font-medium text-slate-600 dark:text-slate-300">Device Code *</label>
                            <input
                                required
                                type="text"
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                value={deviceCode}
                                onChange={(e) => setDeviceCode(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="text-xs font-medium text-slate-600 dark:text-slate-300">Device Name *</label>
                            <input
                                required
                                type="text"
                                placeholder="Main Entrance Kiosk"
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                value={deviceName}
                                onChange={(e) => setDeviceName(e.target.value)}
                            />
                        </div>
                    </div>

                    <div>
                        <label className="text-xs font-medium text-slate-600 dark:text-slate-300">Integration Protocol *</label>
                        <select
                            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            value={protocol}
                            onChange={(e) => setProtocol(e.target.value as BiometricProtocol)}
                        >
                            <option value="zkteco_push">ZKTeco ADMS / iClock Push (Cloud HTTP/HTTPS)</option>
                            <option value="zkteco_socket">ZKTeco Standalone (TCP Socket Port 4370)</option>
                            <option value="hikvision_isapi">Hikvision ISAPI (HTTP/REST)</option>
                            <option value="dahua_cgi">Dahua CGI RPC (HTTP)</option>
                            <option value="generic_webhook">Generic IoT Cloud Webhook (JSON)</option>
                            <option value="usb_import">USB Flash / Offline Log Import</option>
                        </select>
                    </div>

                    {/* Protocol-Specific Configuration Guidance */}
                    <div className="rounded-lg bg-indigo-50 p-3 text-xs text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300">
                        {protocol === 'zkteco_push' && (
                            <p>
                                <strong>ZKTeco ADMS Setup:</strong> In machine menu under Comm. &gt; Cloud Server / ADMS, set Server Address to this server's domain/IP, Server Port to 80/443, and ensure the Serial Number below matches the device sticker.
                            </p>
                        )}
                        {protocol === 'zkteco_socket' && (
                            <p>
                                <strong>ZKTeco TCP Socket Setup:</strong> Enter the LAN static IP or VPN address and TCP port (default 4370). Ensure AutoERP worker processes have direct network access.
                            </p>
                        )}
                        {protocol === 'hikvision_isapi' && (
                            <p>
                                <strong>Hikvision Setup:</strong> Provide camera/terminal IP address and admin Comm Key (password). AutoERP will query /ISAPI/AccessControl/AcsEvent.
                            </p>
                        )}
                        {protocol === 'generic_webhook' && (
                            <p>
                                <strong>Generic Webhook URL:</strong> Configure your edge gateway to POST punches to <code>/api/v1/hr/biometric/push/webhook/{deviceCode}</code> with the Bearer Auth Token below.
                            </p>
                        )}
                        {protocol === 'usb_import' && (
                            <p>
                                <strong>USB Import:</strong> Use the "Upload" action to ingest <code>1_attlog.dat</code> or CSV punch dumps directly.
                            </p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="text-xs font-medium text-slate-600 dark:text-slate-300">IP Address (for LAN/VPN)</label>
                            <input
                                type="text"
                                placeholder="192.168.1.201"
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                value={ipAddress}
                                onChange={(e) => setIpAddress(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="text-xs font-medium text-slate-600 dark:text-slate-300">Port Number</label>
                            <input
                                type="number"
                                placeholder="4370"
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                value={port}
                                onChange={(e) => setPort(e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="text-xs font-medium text-slate-600 dark:text-slate-300">Serial Number (SN)</label>
                            <input
                                type="text"
                                placeholder="ZKMB2023004812"
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                value={serialNumber}
                                onChange={(e) => setSerialNumber(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="text-xs font-medium text-slate-600 dark:text-slate-300">Comm Key / Device Password</label>
                            <input
                                type="password"
                                placeholder="0"
                                className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                value={commKey}
                                onChange={(e) => setCommKey(e.target.value)}
                            />
                        </div>
                    </div>

                    <div>
                        <label className="text-xs font-medium text-slate-600 dark:text-slate-300">Auth Token / Webhook HMAC Secret</label>
                        <input
                            type="text"
                            placeholder="Optional shared secret token for webhooks"
                            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            value={authToken}
                            onChange={(e) => setAuthToken(e.target.value)}
                        />
                    </div>

                    <div className="flex items-center gap-6 pt-2">
                        <label className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input
                                type="checkbox"
                                checked={isEnabled}
                                onChange={(e) => setIsEnabled(e.target.checked)}
                                className="rounded border-slate-300 text-indigo-600"
                            />
                            Device Enabled
                        </label>
                        <label className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input
                                type="checkbox"
                                checked={autoMapUsers}
                                onChange={(e) => setAutoMapUsers(e.target.checked)}
                                className="rounded border-slate-300 text-indigo-600"
                            />
                            Auto-Match PIN to Employee Number
                        </label>
                    </div>

                    <div className="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <Button variant="secondary" onClick={() => setShowDeviceModal(false)} disabled={deviceSubmitting}>
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" disabled={deviceSubmitting}>
                            {deviceSubmitting ? 'Saving...' : editingDevice ? 'Update Device' : 'Create Device'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Diagnostics / Ping Modal */}
            <Modal
                open={showPingModal}
                title={`Device Diagnostics: ${activeDeviceForPing?.name || ''}`}
                onClose={() => setShowPingModal(false)}
            >
                <div className="space-y-4">
                    {pinging ? (
                        <LoadingState label="Connecting to biometric device..." />
                    ) : pingResult ? (
                        <div className="space-y-3">
                            <div className="flex items-center gap-3">
                                <StatusBadge
                                    status={pingResult.isReachable ? 'active' : 'offline'}
                                    label={pingResult.isReachable ? 'Online' : 'Offline'}
                                />
                                <span className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                    {pingResult.isReachable ? 'Device connection established successfully' : 'Device unreachable'}
                                </span>
                            </div>

                            {pingResult.isReachable ? (
                                <div className="grid grid-cols-2 gap-3 rounded-lg bg-slate-50 p-4 text-xs dark:bg-slate-800/60">
                                    <div>
                                        <span className="font-semibold text-slate-500">Latency:</span> {pingResult.responseTimeMs} ms
                                    </div>
                                    <div>
                                        <span className="font-semibold text-slate-500">Firmware:</span> {pingResult.firmwareVersion || 'N/A'}
                                    </div>
                                    <div>
                                        <span className="font-semibold text-slate-500">Device Clock:</span> {pingResult.deviceTime || 'Synchronized'}
                                    </div>
                                    <div>
                                        <span className="font-semibold text-slate-500">Clock Drift:</span> {pingResult.clockDriftSeconds ?? 0}s
                                    </div>
                                </div>
                            ) : (
                                <div className="rounded-lg bg-rose-50 p-4 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                                    <strong>Failure Reason:</strong> {pingResult.errorMessage || 'Timeout or network unreachable'}
                                </div>
                            )}
                        </div>
                    ) : null}

                    <div className="flex justify-end pt-3">
                        <Button variant="secondary" onClick={() => setShowPingModal(false)}>
                            Close
                        </Button>
                    </div>
                </div>
            </Modal>

            {/* Users Mapping Modal */}
            <Modal
                open={showUsersModal}
                title={`Biometric User Mappings: ${activeDeviceForUsers?.name || ''}`}
                onClose={() => setShowUsersModal(false)}
            >
                <div className="space-y-4">
                    {canManage && (
                        <form onSubmit={handleAddUserMapping} className="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-3 dark:border-slate-800 dark:bg-slate-800/40">
                            <div className="text-xs font-semibold text-slate-700 dark:text-slate-200">Map Device PIN / Card to Employee</div>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <input
                                        required
                                        type="text"
                                        placeholder="Device PIN (e.g. 101)"
                                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                        value={mappingPin}
                                        onChange={(e) => setMappingPin(e.target.value)}
                                    />
                                </div>
                                <div>
                                    <input
                                        type="text"
                                        placeholder="RFID Card No (Optional)"
                                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                        value={mappingCard}
                                        onChange={(e) => setMappingCard(e.target.value)}
                                    />
                                </div>
                                <div>
                                    <EmployeeLookupSelect
                                        label=""
                                        value={mappingEmployee}
                                        onChange={setMappingEmployee}
                                    />
                                </div>
                            </div>
                            <div className="flex justify-between items-center pt-1">
                                <Button size="sm" variant="secondary" onClick={handleReprocessLogs} type="button">
                                    Reprocess Unmapped Punches
                                </Button>
                                <Button size="sm" variant="primary" type="submit" disabled={mappingSubmitting || !mappingEmployee || !mappingPin}>
                                    {mappingSubmitting ? 'Mapping...' : '+ Link Employee'}
                                </Button>
                            </div>
                        </form>
                    )}

                    <div className="max-h-64 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-800">
                        {usersLoading ? (
                            <LoadingState label="Loading user mappings..." />
                        ) : deviceUsers.length === 0 ? (
                            <div className="p-6 text-center text-xs text-slate-500">
                                No mapped users found for this device.
                            </div>
                        ) : (
                            <table className="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                                <thead className="bg-slate-100 dark:bg-slate-800 text-slate-500">
                                    <tr>
                                        <th className="px-3 py-2">Device PIN</th>
                                        <th className="px-3 py-2">Card No</th>
                                        <th className="px-3 py-2">Mapped Employee</th>
                                        <th className="px-3 py-2 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
                                    {deviceUsers.map((u) => (
                                        <tr key={u.id}>
                                            <td className="px-3 py-2 font-mono font-semibold">{u.biometric_user_id}</td>
                                            <td className="px-3 py-2 font-mono">{u.card_number || '-'}</td>
                                            <td className="px-3 py-2">
                                                {u.employee ? (
                                                    <span>{u.employee.display_name} ({u.employee.employee_number})</span>
                                                ) : (
                                                    <span className="text-rose-500">Unlinked</span>
                                                )}
                                            </td>
                                            <td className="px-3 py-2 text-right">
                                                {canManage && (
                                                    <Button size="sm" variant="danger" onClick={() => handleRemoveUserMapping(u.id)}>
                                                        Remove
                                                    </Button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>

                    <div className="flex justify-end pt-2">
                        <Button variant="secondary" onClick={() => setShowUsersModal(false)}>
                            Close
                        </Button>
                    </div>
                </div>
            </Modal>

            {/* Raw Logs & Audit Modal */}
            <Modal
                open={showLogsModal}
                title={`Raw Punch Logs: ${activeDeviceForLogs?.name || ''}`}
                onClose={() => setShowLogsModal(false)}
            >
                <div className="space-y-4">
                    <div className="flex gap-3">
                        <select
                            className="rounded-md border border-slate-300 px-3 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            value={logStatusFilter}
                            onChange={(e) => setLogStatusFilter(e.target.value)}
                        >
                            <option value="">All Sync Statuses</option>
                            <option value="processed">Processed</option>
                            <option value="unmapped_user">Unmapped User</option>
                            <option value="duplicate">Duplicate (Ignored)</option>
                            <option value="failed">Failed</option>
                        </select>
                        <input
                            type="date"
                            className="rounded-md border border-slate-300 px-3 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            value={logDateFilter}
                            onChange={(e) => setLogDateFilter(e.target.value)}
                        />
                        <Button size="sm" variant="secondary" onClick={handleFilterLogs}>
                            Filter
                        </Button>
                    </div>

                    <div className="max-h-80 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-800">
                        {logsLoading ? (
                            <LoadingState label="Loading raw logs..." />
                        ) : rawLogs.length === 0 ? (
                            <div className="p-8 text-center text-xs text-slate-500">
                                No raw punch logs found.
                            </div>
                        ) : (
                            <table className="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                                <thead className="bg-slate-100 dark:bg-slate-800 text-slate-500">
                                    <tr>
                                        <th className="px-3 py-2">Punch Time</th>
                                        <th className="px-3 py-2">PIN</th>
                                        <th className="px-3 py-2">Employee</th>
                                        <th className="px-3 py-2">Direction</th>
                                        <th className="px-3 py-2">Verify</th>
                                        <th className="px-3 py-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
                                    {rawLogs.map((log) => (
                                        <tr key={log.id}>
                                            <td className="px-3 py-2 font-mono text-[11px]">
                                                {new Date(log.punch_time).toLocaleString()}
                                            </td>
                                            <td className="px-3 py-2 font-mono">{log.biometric_user_id}</td>
                                            <td className="px-3 py-2">
                                                {log.employee ? (
                                                    <span>{log.employee.display_name}</span>
                                                ) : (
                                                    <span className="text-amber-500">Unmapped PIN</span>
                                                )}
                                            </td>
                                            <td className="px-3 py-2 uppercase font-medium">{log.punch_state}</td>
                                            <td className="px-3 py-2 capitalize">{log.verify_method}</td>
                                            <td className="px-3 py-2">
                                                <StatusBadge status={log.sync_status} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>

                    <div className="flex justify-end pt-2">
                        <Button variant="secondary" onClick={() => setShowLogsModal(false)}>
                            Close
                        </Button>
                    </div>
                </div>
            </Modal>

            {/* USB Flash Upload Modal */}
            <Modal
                open={showUploadModal}
                title={`Upload Flash Log File: ${activeDeviceForUpload?.name || ''}`}
                onClose={() => setShowUploadModal(false)}
            >
                <form onSubmit={handleFileUpload} className="space-y-4">
                    <p className="text-xs text-slate-500">
                        Upload a ZKTeco <code>1_attlog.dat</code> file or comma-separated CSV attendance log from an offline flash drive.
                    </p>
                    <input
                        required
                        type="file"
                        accept=".dat,.csv,.txt"
                        className="w-full text-xs"
                        onChange={(e) => setUploadFile(e.target.files?.[0] || null)}
                    />
                    <div className="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <Button variant="secondary" onClick={() => setShowUploadModal(false)} disabled={uploading}>
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" disabled={uploading || !uploadFile}>
                            {uploading ? 'Importing...' : 'Upload & Process Logs'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
