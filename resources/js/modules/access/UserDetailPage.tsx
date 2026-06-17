import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { useAuth } from '@/modules/auth/AuthProvider';
import { accessApi, type AccessUser } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';

export default function UserDetailPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const auth = useAuth();
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [statusLoading, setStatusLoading] = useState(false);
    const user = useApi((signal) => accessApi.getUser(String(id), signal), [id], Boolean(id));
    const canUpdate = hasAccessPermission(auth.permissions, auth.roles, accessPermissions.usersUpdate);
    const canActivate = hasAccessPermission(auth.permissions, auth.roles, accessPermissions.usersActivate);
    const canDeactivate = hasAccessPermission(auth.permissions, auth.roles, accessPermissions.usersDeactivate);

    const changeStatus = async (record: AccessUser) => {
        setStatusLoading(true);
        setActionError(null);
        try {
            if (record.status === 'active') await accessApi.deactivateUser(record.id);
            else await accessApi.activateUser(record.id);
            user.reload();
        } catch (caught) {
            setActionError(toApiError(caught));
        } finally {
            setStatusLoading(false);
        }
    };

    const record = user.data;
    return (
        <>
            <ContentHeader
                title={record?.name ?? 'User Detail'}
                description="Read-only user account, access, and activity summary."
                actions={(
                    <>
                        <Button variant="secondary" onClick={() => navigate('/access/users')}>Back</Button>
                        {record && canUpdate && <LinkButton variant="secondary" to={`/access/users/${record.id}/edit`}>Edit</LinkButton>}
                        {record && ((record.status === 'active' && canDeactivate) || (record.status !== 'active' && canActivate)) && (
                            <Button variant={record.status === 'active' ? 'danger' : 'secondary'} loading={statusLoading} onClick={() => void changeStatus(record)}>
                                {record.status === 'active' ? 'Deactivate' : 'Activate'}
                            </Button>
                        )}
                    </>
                )}
            />
            <ErrorAlert error={actionError ?? user.error} />
            {user.loading || !record ? <LoadingState label="Loading user..." /> : (
                <div className="space-y-5">
                    <Panel title="Summary">
                        <DetailGrid items={[
                            { label: 'Name', value: record.name },
                            { label: 'Username', value: record.username || '-' },
                            { label: 'Email', value: record.email },
                            { label: 'Phone', value: record.phone || '-' },
                            { label: 'Status', value: <StatusBadge status={record.status} /> },
                            { label: 'Last Login', value: formatDate(record.last_login_at) },
                        ]} />
                    </Panel>
                    <Panel title="Roles & Permissions">
                        <div className="grid gap-5 lg:grid-cols-2">
                            <ReadonlyList title="Roles" values={(record.roles ?? []).map((role) => role.name)} />
                            <ReadonlyList title="Permissions" values={(record.permissions ?? []).map((permission) => permission.name)} monospace />
                        </div>
                    </Panel>
                    <Panel title="Organization Access">
                        <ReadonlyList
                            title="Organization Units"
                            values={(record.organization_units ?? []).map((unit) => `${unit.name}${unit.is_default ? ' (default)' : ''}`)}
                        />
                    </Panel>
                    <Panel title="Activity">
                        <DetailGrid items={[
                            { label: 'Last Login', value: formatDate(record.last_login_at) },
                            { label: 'Version', value: record.row_version ?? '-' },
                        ]} />
                    </Panel>
                </div>
            )}
        </>
    );
}

function ReadonlyList({ title, values, monospace = false }: { title: string; values: string[]; monospace?: boolean }) {
    return (
        <section>
            <h3 className="mb-2 text-sm font-semibold text-slate-900">{title}</h3>
            {values.length === 0 ? <p className="text-sm text-slate-500">None assigned.</p> : (
                <div className="flex flex-wrap gap-2">
                    {values.map((value) => (
                        <span key={value} className={`rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ${monospace ? 'font-mono' : ''}`}>{value}</span>
                    ))}
                </div>
            )}
        </section>
    );
}
