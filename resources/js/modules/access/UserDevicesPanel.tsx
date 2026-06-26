import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { accessApi, type AccessUserDevice } from './accessApi';

export function UserDevicesPanel({ userId, canManage }: { userId: number; canManage: boolean }) {
    const [page, setPage] = useState(1);
    const [revokeTarget, setRevokeTarget] = useState<AccessUserDevice | null>(null);
    const [busy, setBusy] = useState<number | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const devices = useApi(
        (signal) => accessApi.listUserDevices(userId, { page, per_page: 20, include_revoked: true }, signal),
        [userId, page],
        true,
        false,
    );
    const items = devices.data?.data ?? [];

    async function revoke() {
        if (!revokeTarget) return;
        setBusy(revokeTarget.id);
        setError(null);
        try {
            await accessApi.revokeUserDevice(userId, revokeTarget.id, revokeTarget.row_version);
            setRevokeTarget(null);
            devices.reload();
        } catch (caught) {
            setError(toApiError(caught));
        } finally {
            setBusy(null);
        }
    }

    if (devices.loading && !devices.data) return <LoadingState label="Loading registered devices..." />;

    return (
        <div className="space-y-4">
            <ErrorAlert error={devices.error ?? error} />
            <Panel>
                <p className="text-sm text-slate-600">
                    Device tokens are registered by trusted client applications and never shown here. Administrators can review and revoke registrations only.
                </p>
            </Panel>
            <div className="space-y-3">
                {items.map((device) => (
                    <Panel key={device.id}>
                        <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="font-semibold text-slate-900">{device.device_name || platformLabel(device.platform)}</p>
                                    <StatusBadge status={device.revoked_at ? 'revoked' : 'active'} />
                                </div>
                                <p className="mt-1 text-sm text-slate-500">
                                    {platformLabel(device.platform)} · Last active {formatDate(device.last_active_at)} · Registered {formatDate(device.created_at)}
                                </p>
                            </div>
                            {canManage && !device.revoked_at && (
                                <Button variant="danger" loading={busy === device.id} onClick={() => setRevokeTarget(device)}>Revoke</Button>
                            )}
                        </div>
                    </Panel>
                ))}
                {items.length === 0 && <Panel><p className="text-sm text-slate-500">No devices have been registered for this user.</p></Panel>}
            </div>
            <Pagination meta={devices.data?.meta} onPageChange={setPage} />
            <ConfirmDialog
                open={revokeTarget !== null}
                title="Revoke device registration?"
                message="The client will no longer be able to use this registration token. It must register again before receiving device notifications."
                confirmLabel="Revoke Device"
                loading={revokeTarget !== null && busy === revokeTarget.id}
                onCancel={() => setRevokeTarget(null)}
                onConfirm={() => void revoke()}
            />
        </div>
    );
}

function platformLabel(value: string): string {
    if (value === 'ios') return 'iOS';
    if (value === 'android') return 'Android';
    if (value === 'web') return 'Web';
    return value;
}
