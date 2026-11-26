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
    deleteEmployeeCredential,
    listEmployeeCredentials,
    storeEmployeeCredential,
    updateEmployeeCredentialStatus,
} from '../hrApi';
import type { CredentialStatus, CredentialType, HrEmployeeAttendanceCredential } from '../hrTypes';

interface Props {
    employeeId: number;
    canManage?: boolean;
}

export function EmployeeCredentialTab({ employeeId, canManage = false }: Props) {
    const [credentials, setCredentials] = useState<HrEmployeeAttendanceCredential[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [modalError, setModalError] = useState<ApiError | null>(null);
    const { confirm, confirmDialog } = useConfirmDialog();

    // Issue Modal
    const [showIssueModal, setShowIssueModal] = useState(false);
    const [type, setType] = useState<CredentialType>('rfid_card');
    const [value, setValue] = useState('');
    const [label, setLabel] = useState('');
    const [expiresAt, setExpiresAt] = useState('');

    // Status Action Modal
    const [selectedCred, setSelectedCred] = useState<HrEmployeeAttendanceCredential | null>(null);
    const [newStatus, setNewStatus] = useState<CredentialStatus>('suspended');
    const [revocationReason, setRevocationReason] = useState('');

    const loadCredentials = useCallback(() => {
        setLoading(true);
        setError(null);
        listEmployeeCredentials(employeeId)
            .then((res) => setCredentials(res.data))
            .catch((err) => setError(toApiError(err)))
            .finally(() => setLoading(false));
    }, [employeeId]);

    useEffect(() => {
        queueMicrotask(() => {
            loadCredentials();
        });
    }, [loadCredentials]);

    const handleIssueSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!canManage) return;
        setModalError(null);
        try {
            await storeEmployeeCredential(employeeId, {
                credential_type: type,
                credential_value: value,
                label: label || undefined,
                expires_at: expiresAt ? `${expiresAt} 23:59:59` : undefined,
            });
            setShowIssueModal(false);
            setValue('');
            setLabel('');
            setExpiresAt('');
            loadCredentials();
        } catch (err: unknown) {
            setModalError(toApiError(err));
        }
    };

    const handleStatusSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!canManage || !selectedCred) return;
        setModalError(null);
        try {
            await updateEmployeeCredentialStatus(employeeId, selectedCred.id, {
                status: newStatus,
                revocation_reason: revocationReason || undefined,
                expected_version: selectedCred.row_version,
            });
            setSelectedCred(null);
            setRevocationReason('');
            loadCredentials();
        } catch (err: unknown) {
            setModalError(toApiError(err));
        }
    };

    const handleDelete = async (cred: HrEmployeeAttendanceCredential) => {
        if (!canManage) return;
        const confirmed = await confirm({
            title: 'Delete Credential',
            message: `Delete ${cred.credential_type.replace('_', ' ')} credential "${cred.credential_value}"?`,
            confirmLabel: 'Delete',
            danger: true,
        });
        if (!confirmed) return;
        setError(null);
        try {
            await deleteEmployeeCredential(employeeId, cred.id, cred.row_version);
            loadCredentials();
        } catch (err: unknown) {
            setError(toApiError(err));
        }
    };

    if (loading && credentials.length === 0) return <LoadingState label="Loading credentials..." />;

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">
                        Attendance Credentials & Smart Badges
                    </h3>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        Manage physical RFID smart cards, NFC tags, barcode badges, and mobile device identifiers.
                    </p>
                </div>
                {canManage && (
                    <Button
                        variant="primary"
                        onClick={() => {
                            setModalError(null);
                            setShowIssueModal(true);
                        }}
                    >
                        Issue Credential
                    </Button>
                )}
            </div>

            <ErrorAlert error={error} inline />

            <div className="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table className="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-700">
                    <thead className="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Type</th>
                            <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Credential Value (UID/ID)</th>
                            <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Label</th>
                            <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Status</th>
                            <th className="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Issued / Expires</th>
                            {canManage && <th className="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Actions</th>}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        {credentials.length === 0 ? (
                            <tr>
                                <td colSpan={canManage ? 6 : 5} className="py-6 text-center text-gray-500">
                                    No attendance credentials registered for this employee.
                                </td>
                            </tr>
                        ) : (
                            credentials.map((c) => (
                                <tr key={c.id}>
                                    <td className="px-4 py-3 font-medium uppercase text-gray-900 dark:text-gray-100">
                                        {c.credential_type.replace('_', ' ')}
                                    </td>
                                    <td className="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                                        {c.credential_value}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                                        {c.label || '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <StatusBadge
                                            status={
                                                c.status === 'active'
                                                    ? 'success'
                                                    : c.status === 'suspended'
                                                    ? 'warning'
                                                    : 'danger'
                                            }
                                            label={c.status.toUpperCase()}
                                        />
                                    </td>
                                    <td className="px-4 py-3 text-xs text-gray-500">
                                        {c.issued_at ? c.issued_at.slice(0, 10) : '—'}
                                        {c.expires_at ? ` to ${c.expires_at.slice(0, 10)}` : ' (Never)'}
                                    </td>
                                    {canManage && (
                                        <td className="px-4 py-3 text-right space-x-2">
                                            <Button
                                                variant="secondary"
                                                onClick={() => {
                                                    setModalError(null);
                                                    setSelectedCred(c);
                                                    setNewStatus(c.status === 'active' ? 'suspended' : 'active');
                                                }}
                                            >
                                                Change Status
                                            </Button>
                                            <Button
                                                variant="danger"
                                                onClick={() => handleDelete(c)}
                                            >
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

            {/* Modal: Issue Credential */}
            <Modal
                open={showIssueModal}
                title="Issue Attendance Credential"
                onClose={() => setShowIssueModal(false)}
            >
                <form onSubmit={handleIssueSubmit} className="space-y-4">
                    <ErrorAlert error={modalError} inline />
                    <div>
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Credential Type
                        </label>
                        <select
                            className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            value={type}
                            onChange={(e) => setType(e.target.value as CredentialType)}
                        >
                            <option value="rfid_card">RFID Smart Card (Mifare / EM-Marin)</option>
                            <option value="nfc_tag">NFC Phone / Token</option>
                            <option value="barcode">1D / 2D Barcode</option>
                            <option value="mobile_device_uuid">Registered Mobile Device UUID</option>
                        </select>
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Card UID / Barcode String / Device UUID
                        </label>
                        <input
                            type="text"
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            value={value}
                            onChange={(e) => setValue(e.target.value)}
                            placeholder="e.g. E280689400004012"
                            required
                        />
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Label (Optional)
                        </label>
                        <input
                            type="text"
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            value={label}
                            onChange={(e) => setLabel(e.target.value)}
                            placeholder="e.g. Primary Blue Badge"
                        />
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Expiry Date (Optional)
                        </label>
                        <input
                            type="date"
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            value={expiresAt}
                            onChange={(e) => setExpiresAt(e.target.value)}
                        />
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <Button variant="secondary" type="button" onClick={() => setShowIssueModal(false)}>
                            Cancel
                        </Button>
                        <Button variant="primary" type="submit" disabled={!value}>
                            Register Credential
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Modal: Update Status */}
            <Modal
                open={selectedCred !== null}
                title="Update Credential Status"
                onClose={() => setSelectedCred(null)}
            >
                <form onSubmit={handleStatusSubmit} className="space-y-4">
                    <ErrorAlert error={modalError} inline />
                    <div>
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            New Status
                        </label>
                        <select
                            className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            value={newStatus}
                            onChange={(e) => setNewStatus(e.target.value as CredentialStatus)}
                        >
                            <option value="active">Active</option>
                            <option value="suspended">Suspended (Temporary)</option>
                            <option value="lost">Lost</option>
                            <option value="revoked">Revoked (Permanent)</option>
                        </select>
                    </div>
                    {(newStatus === 'lost' || newStatus === 'revoked') && (
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Revocation Reason
                            </label>
                            <input
                                type="text"
                                className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                                value={revocationReason}
                                onChange={(e) => setRevocationReason(e.target.value)}
                                placeholder="e.g. Card misplaced by employee on commute"
                                required
                            />
                        </div>
                    )}
                    <div className="mt-6 flex justify-end gap-2">
                        <Button variant="secondary" type="button" onClick={() => setSelectedCred(null)}>
                            Cancel
                        </Button>
                        <Button variant="primary" type="submit">
                            Save Status
                        </Button>
                    </div>
                </form>
            </Modal>

            {confirmDialog}
        </div>
    );
}
