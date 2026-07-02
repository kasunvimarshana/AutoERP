import { useMemo, useState } from 'react';
import { PLATFORM_PERMISSION } from '@/app/access/platformPermissions';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { platformAdministrationApi } from './platformAdministrationApi';
import { formatPlatformDateTime } from './platformAdministrationPresentation';
import type { PlatformSession } from './platformAdministrationTypes';

interface OperatorOption extends NamedResource {
    email: string;
    row_version: number;
}

type SecurityAction =
    | { type: 'session'; session: PlatformSession }
    | { type: 'operator_sessions'; operator: OperatorOption }
    | null;

export default function PlatformSecurityPage() {
    const auth = useAuth();
    const canManageSessions = hasPermission(auth, PLATFORM_PERMISSION.sessionsManage);
    const [operator, setOperator] = useState<OperatorOption | null>(null);
    const [page, setPage] = useState(1);
    const sessions = useApi(
        (signal) => platformAdministrationApi.listSessions({
            operator_id: operator?.id,
            page,
            per_page: 25,
        }, signal),
        [operator?.id ?? null, page],
        true,
        false,
    );
    const [action, setAction] = useState<SecurityAction>(null);
    const [reason, setReason] = useState('');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const columns = useMemo<DataColumn<PlatformSession>[]>(() => [
        {
            key: 'operator',
            header: 'Operator',
            render: (session) => session.operator ? (
                <div>
                    <p className="font-semibold text-slate-900">{session.operator.name}</p>
                    <p className="text-xs text-slate-500">{session.operator.email}</p>
                </div>
            ) : 'Operator unavailable',
        },
        {
            key: 'device',
            header: 'Device',
            render: (session) => (
                <div>
                    <p>{session.device_name || 'Unlabelled device'}</p>
                    <p className="max-w-xs truncate text-xs text-slate-500" title={session.user_agent ?? undefined}>{session.user_agent || 'User agent unavailable'}</p>
                </div>
            ),
        },
        { key: 'network', header: 'IP address', render: (session) => session.ip_address || 'Not captured' },
        { key: 'activity', header: 'Last activity', render: (session) => formatPlatformDateTime(session.last_activity_at) },
        { key: 'status', header: 'Status', render: (session) => <StatusBadge status={session.status} /> },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            mobile: false,
            render: (session) => canManageSessions && session.status === 'active' ? (
                <Button
                    variant="danger"
                    className="min-h-8 px-3 py-1 text-xs"
                    onClick={() => openAction({ type: 'session', session })}
                >
                    Revoke
                </Button>
            ) : null,
        },
    ], [canManageSessions]);

    function openAction(next: SecurityAction) {
        setAction(next);
        setReason('');
        setError(null);
        setSuccess(null);
    }

    async function executeAction() {
        if (!action) return;
        setSaving(true);
        setError(null);
        try {
            if (action.type === 'session') {
                await platformAdministrationApi.revokeSession(action.session, reason.trim());
                setSuccess(`The session for ${action.session.operator?.name ?? 'the selected operator'} was revoked.`);
            } else if (action.type === 'operator_sessions') {
                const count = await platformAdministrationApi.revokeOperatorSessions(action.operator.id, reason.trim());
                setSuccess(`${count} active session${count === 1 ? '' : 's'} revoked for ${action.operator.name}.`);
            }
            setAction(null);
            setReason('');
            sessions.reload();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
            sessions.reload();
        } finally {
            setSaving(false);
        }
    }

    return (
        <>
            <ContentHeader
                title="Platform sessions"
                description="Inspect control-plane sessions and revoke compromised access without changing operator credentials."
            />

            <div className="space-y-5">
                <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
                <ErrorAlert error={sessions.error} title="Unable to load platform sessions" />
                <ErrorAlert error={error} title="Platform security action failed" />

                <section className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="grid gap-4 lg:grid-cols-[minmax(260px,1fr)_auto] lg:items-end">
                        <GenericLookupSelect
                            label="Platform operator"
                            value={operator}
                            onChange={(selected) => { setOperator(selected); setPage(1); return true; }}
                            search={searchOperators}
                            formatLabel={(selected) => `${selected.name} · ${selected.email}`}
                            placeholder="Search by operator name or platform email"
                            minSearchLength={0}
                            loadOnOpen
                        />
                        <div className="flex flex-wrap gap-2">
                            <Button variant="secondary" disabled={!operator} onClick={() => { setOperator(null); setPage(1); }}>All operators</Button>
                            {canManageSessions ? (
                                <Button variant="danger" disabled={!operator} onClick={() => operator && openAction({ type: 'operator_sessions', operator })}>
                                    Revoke all sessions
                                </Button>
                            ) : null}
                        </div>
                    </div>
                    <p className="mt-3 text-xs text-slate-500">Select an operator to review a specific account. Session revocation requires recent platform authentication and is recorded in the platform audit log.</p>
                </section>

                {sessions.loading && !sessions.data ? <LoadingState label="Loading platform sessions..." /> : (
                    <DataTable
                        rows={sessions.data?.data ?? []}
                        columns={columns}
                        rowKey={(session) => session.id}
                        emptyMessage="No platform sessions match the current operator filter."
                        mobileSummary={(session) => session.operator?.name ?? 'Operator unavailable'}
                        mobileDetails={(session) => (
                            <div className="space-y-1">
                                <p>{session.device_name || 'Unlabelled device'} · {session.ip_address || 'IP unavailable'}</p>
                                <p>Last activity {formatPlatformDateTime(session.last_activity_at)}</p>
                                <p>Expires {formatPlatformDateTime(session.expires_at)}</p>
                            </div>
                        )}
                        rowBadge={(session) => <StatusBadge status={session.status} />}
                        mobileActions={canManageSessions ? (session) => session.status === 'active' ? (
                            <Button variant="danger" onClick={() => openAction({ type: 'session', session })}>Revoke session</Button>
                        ) : null : undefined}
                    />
                )}
                {sessions.data ? <Pagination meta={sessions.data.meta} onPageChange={setPage} /> : null}
            </div>

            <Modal open={action !== null} title={actionTitle(action)} onClose={() => setAction(null)} closeDisabled={saving}>
                <div className="space-y-5">
                    <p className="text-sm text-slate-700">{actionMessage(action)}</p>
                    <Textarea
                        label="Security reason"
                        required
                        minLength={10}
                        maxLength={500}
                        value={reason}
                        onChange={(event) => setReason(event.target.value)}
                        hint="At least 10 characters. The reason is stored in the platform audit trail."
                    />
                    <div className="flex justify-end gap-2">
                        <Button variant="secondary" disabled={saving} onClick={() => setAction(null)}>Cancel</Button>
                        <Button variant="danger" loading={saving} disabled={reason.trim().length < 10} onClick={executeAction}>Confirm security action</Button>
                    </div>
                </div>
            </Modal>
        </>
    );
}

async function searchOperators({ search, page, perPage, signal }: { search: string; page: number; perPage: number; signal: AbortSignal }) {
    const result = await platformAdministrationApi.listOperators({ search: search || undefined, page, per_page: perPage }, signal);
    return {
        data: result.data.map((operator) => ({
            id: operator.id,
            name: operator.display_name,
            email: operator.email,
            row_version: operator.row_version,
        })),
        meta: result.meta,
    };
}

function actionTitle(action: SecurityAction): string {
    if (action?.type === 'session') return 'Revoke platform session';
    if (action?.type === 'operator_sessions') return `Revoke all sessions for ${action.operator.name}`;
    return 'Platform security action';
}

function actionMessage(action: SecurityAction): string {
    if (action?.type === 'session') return 'This immediately invalidates the selected platform session and its access and refresh tokens.';
    if (action?.type === 'operator_sessions') return 'This immediately invalidates every active platform session for the selected operator.';
    return '';
}
