import { useMemo, useState } from 'react';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import {
    createAccountAssignment,
    createAccountRole,
    createPostingProfile,
    endAccountAssignment,
    getFinanceLookups,
    listAccountAssignments,
    listAccountRoles,
    listPostingProfiles,
    updateAccountRole,
    updatePostingProfile,
    type AccountAssignmentPayload,
    type AccountRolePayload,
    type FinanceAccountAssignment,
    type FinanceAccountRole,
    type PostingProfile,
    type PostingProfilePayload,
} from '../financeApi';
import { financePermissions } from '../financePermissions';

const emptyProfile = (): PostingProfilePayload => ({
    code: '',
    name: '',
    description: null,
    is_active: true,
    rules: [emptyProfileRule(), emptyProfileRule()],
});

const emptyRole = (): AccountRolePayload => ({
    code: '',
    name: '',
    description: null,
    is_active: true,
});

const emptyAssignment = (): AccountAssignmentPayload => ({
    account_role_id: 0,
    account_id: 0,
    effective_from: todayDate(),
    effective_to: null,
});

function emptyProfileRule(): PostingProfilePayload['rules'][number] {
    return { line_key: '', account_role_id: 0, description: null };
}

export default function PostingProfilePage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, financePermissions.postingProfilesManage);
    const canViewAccounts = hasPermission(auth, financePermissions.accountsView);
    const hasOrganizationContext = auth.organizationUnit !== null;
    const [selectedProfile, setSelectedProfile] = useState<PostingProfile | null>(null);
    const [profileForm, setProfileForm] = useState<PostingProfilePayload>(emptyProfile);
    const [selectedRole, setSelectedRole] = useState<FinanceAccountRole | null>(null);
    const [roleForm, setRoleForm] = useState<AccountRolePayload>(emptyRole);
    const [assignmentForm, setAssignmentForm] = useState<AccountAssignmentPayload>(emptyAssignment);
    const [endingAssignmentId, setEndingAssignmentId] = useState<number | null>(null);
    const [endingDate, setEndingDate] = useState(todayDate());
    const [savingProfile, setSavingProfile] = useState(false);
    const [savingRole, setSavingRole] = useState(false);
    const [savingAssignment, setSavingAssignment] = useState(false);
    const [profileError, setProfileError] = useState<ApiError | null>(null);
    const [roleError, setRoleError] = useState<ApiError | null>(null);
    const [assignmentError, setAssignmentError] = useState<ApiError | null>(null);

    const profiles = useApi((signal) => listPostingProfiles({ per_page: 100 }, signal), []);
    const roles = useApi((signal) => listAccountRoles({ per_page: 100 }, signal), []);
    const assignments = useApi((signal) => listAccountAssignments({ per_page: 100 }, signal), []);
    const lookups = useApi((signal) => getFinanceLookups(signal), [], canViewAccounts);

    const activeRoles = useMemo(
        () => (roles.data?.data ?? lookups.data?.account_roles ?? []).filter((role) => role.is_active),
        [lookups.data?.account_roles, roles.data?.data],
    );
    const activeAccounts = useMemo(
        () => (lookups.data?.accounts ?? []).filter((account) => account.is_active && account.is_posting_account),
        [lookups.data?.accounts],
    );
    const isInherited = (organizationUnitId?: number | null) => hasOrganizationContext && organizationUnitId == null;
    const scopeLabel = (organizationUnitId?: number | null) => organizationUnitId
        ? `Organization ${organizationUnitId}`
        : hasOrganizationContext ? 'Tenant fallback' : 'Tenant default';

    const profileColumns: DataColumn<PostingProfile>[] = [
        {
            key: 'code',
            header: 'Profile',
            render: (row) => canManage && !isInherited(row.organization_unit_id) ? (
                <button type="button" className="font-semibold text-sky-700 hover:underline" onClick={() => editProfile(row)}>
                    {row.code} - {row.name}
                </button>
            ) : <span className="font-semibold text-slate-700">{row.code} - {row.name}</span>,
        },
        { key: 'scope', header: 'Scope', render: (row) => scopeLabel(row.organization_unit_id) },
        { key: 'lines', header: 'Semantic mappings', render: (row) => row.rules?.length ?? 0 },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
    ];

    const roleColumns: DataColumn<FinanceAccountRole>[] = [
        {
            key: 'code',
            header: 'Role',
            render: (row) => canManage ? (
                <button type="button" className="font-semibold text-sky-700 hover:underline" onClick={() => editRole(row)}>
                    {row.code} - {row.name}
                </button>
            ) : <span className="font-semibold text-slate-700">{row.code} - {row.name}</span>,
        },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
    ];

    const assignmentColumns: DataColumn<FinanceAccountAssignment>[] = [
        { key: 'role', header: 'Role', render: (row) => row.role ? `${row.role.code} - ${row.role.name}` : String(row.account_role_id) },
        { key: 'account', header: 'Account', render: (row) => row.account ? `${row.account.code} - ${row.account.name}` : String(row.account_id) },
        { key: 'scope', header: 'Scope', render: (row) => scopeLabel(row.organization_unit_id) },
        { key: 'effective_from', header: 'From', render: (row) => row.effective_from },
        { key: 'effective_to', header: 'To', render: (row) => row.effective_to ?? 'Open ended' },
        {
            key: 'actions',
            header: 'Actions',
            className: 'text-right',
            render: (row) => row.effective_to || !canManage || isInherited(row.organization_unit_id)
                ? null
                : <Button type="button" variant="secondary" onClick={() => { setEndingAssignmentId(row.id); setEndingDate(todayDate()); }}>End</Button>,
        },
    ];

    async function saveProfile() {
        setSavingProfile(true);
        setProfileError(null);
        try {
            const rules = profileForm.rules.filter((rule) => rule.line_key.trim() && rule.account_role_id > 0);
            const payload = { ...profileForm, rules };
            if (selectedProfile) {
                await updatePostingProfile(selectedProfile.id, payload);
            } else {
                await createPostingProfile(payload);
            }
            resetProfile();
            profiles.reload();
            lookups.reload();
        } catch (requestError) {
            setProfileError(toApiError(requestError));
        } finally {
            setSavingProfile(false);
        }
    }

    async function saveRole() {
        setSavingRole(true);
        setRoleError(null);
        try {
            if (selectedRole) {
                await updateAccountRole(selectedRole.id, roleForm);
            } else {
                await createAccountRole(roleForm);
            }
            resetRole();
            roles.reload();
            lookups.reload();
        } catch (requestError) {
            setRoleError(toApiError(requestError));
        } finally {
            setSavingRole(false);
        }
    }

    async function saveAssignment() {
        setSavingAssignment(true);
        setAssignmentError(null);
        try {
            await createAccountAssignment({
                ...assignmentForm,
                effective_to: assignmentForm.effective_to || null,
            });
            setAssignmentForm(emptyAssignment());
            assignments.reload();
            lookups.reload();
        } catch (requestError) {
            setAssignmentError(toApiError(requestError));
        } finally {
            setSavingAssignment(false);
        }
    }

    async function endAssignment() {
        if (endingAssignmentId === null) return;
        setSavingAssignment(true);
        setAssignmentError(null);
        try {
            await endAccountAssignment(endingAssignmentId, endingDate);
            setEndingAssignmentId(null);
            assignments.reload();
            lookups.reload();
        } catch (requestError) {
            setAssignmentError(toApiError(requestError));
        } finally {
            setSavingAssignment(false);
        }
    }

    function editProfile(profile: PostingProfile) {
        if (isInherited(profile.organization_unit_id)) return;
        setSelectedProfile(profile);
        setProfileForm({
            code: profile.code,
            name: profile.name,
            description: typeof profile.description === 'string' ? profile.description : null,
            is_active: Boolean(profile.is_active),
            rules: (profile.rules ?? []).map((rule) => ({
                line_key: rule.line_key,
                account_role_id: Number(rule.role?.id ?? rule.account_role_id),
                effective_from: rule.effective_from,
                effective_to: rule.effective_to ?? null,
                is_active: rule.is_active,
                description: typeof rule.description === 'string' ? rule.description : null,
            })),
        });
        setProfileError(null);
    }

    function editRole(role: FinanceAccountRole) {
        setSelectedRole(role);
        setRoleForm({
            code: role.code,
            name: role.name,
            description: typeof role.description === 'string' ? role.description : null,
            is_active: role.is_active,
        });
        setRoleError(null);
    }

    function resetProfile() {
        setSelectedProfile(null);
        setProfileForm(emptyProfile());
        setProfileError(null);
    }

    function resetRole() {
        setSelectedRole(null);
        setRoleForm(emptyRole());
        setRoleError(null);
    }

    function updateRule(index: number, patch: Partial<PostingProfilePayload['rules'][number]>) {
        setProfileForm((current) => ({
            ...current,
            rules: current.rules.map((rule, ruleIndex) => ruleIndex === index ? { ...rule, ...patch } : rule),
        }));
    }

    function removeRule(index: number) {
        setProfileForm((current) => ({
            ...current,
            rules: current.rules.filter((_, ruleIndex) => ruleIndex !== index),
        }));
    }

    return (
        <>
            <ContentHeader
                title="Finance posting configuration"
                description="Map business posting keys to semantic account roles, then assign effective accounts by scope and date. Tenant fallbacks are read-only while an organization context is active."
            />

            <div className="space-y-6">
                <div className={canManage ? 'grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(28rem,34rem)]' : ''}>
                    <Panel title="Posting profiles">
                        <ErrorAlert error={profiles.error} />
                        {profiles.loading
                            ? <LoadingState />
                            : <DataTable rows={profiles.data?.data ?? []} rowKey={(row) => row.id} columns={profileColumns} />}
                    </Panel>
                    {canManage && (
                        <Panel title={selectedProfile ? 'Edit posting profile' : 'New posting profile'}>
                            <ErrorAlert error={profileError} />
                            <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void saveProfile(); }}>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Input label="Code" value={profileForm.code} onChange={(event) => setProfileForm({ ...profileForm, code: event.target.value })} error={fieldError(profileError, 'code')} required />
                                    <Input label="Name" value={profileForm.name} onChange={(event) => setProfileForm({ ...profileForm, name: event.target.value })} error={fieldError(profileError, 'name')} required />
                                </div>
                                <Input label="Description" value={profileForm.description ?? ''} onChange={(event) => setProfileForm({ ...profileForm, description: event.target.value || null })} error={fieldError(profileError, 'description')} />
                                <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="checkbox" checked={profileForm.is_active} onChange={(event) => setProfileForm({ ...profileForm, is_active: event.target.checked })} />
                                    Active
                                </label>
                                <div className="space-y-3">
                                    {profileForm.rules.map((rule, index) => (
                                        <div key={index} className="grid gap-3 sm:grid-cols-[1fr_1.5fr_auto]">
                                            <Input label="Line key" value={rule.line_key} onChange={(event) => updateRule(index, { line_key: event.target.value })} error={fieldError(profileError, `rules.${index}.line_key`)} />
                                            <Select
                                                label="Account role"
                                                value={rule.account_role_id || ''}
                                                onChange={(event) => updateRule(index, { account_role_id: Number(event.target.value) || 0 })}
                                                options={activeRoles.map((role) => ({ value: String(role.id), label: `${role.code} - ${role.name}` }))}
                                                error={fieldError(profileError, `rules.${index}.account_role_id`)}
                                            />
                                            <div className="flex items-end">
                                                <Button type="button" variant="danger" disabled={profileForm.rules.length <= 1} onClick={() => removeRule(index)}>Remove</Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <div className="flex flex-wrap justify-between gap-3">
                                    <Button type="button" variant="secondary" onClick={() => setProfileForm({ ...profileForm, rules: [...profileForm.rules, emptyProfileRule()] })}>Add line</Button>
                                    <div className="flex gap-2">
                                        {selectedProfile && <Button type="button" variant="secondary" onClick={resetProfile}>New</Button>}
                                        <Button type="submit" loading={savingProfile}>{selectedProfile ? 'Update profile' : 'Create profile'}</Button>
                                    </div>
                                </div>
                            </form>
                        </Panel>
                    )}
                </div>

                <div className={canManage ? 'grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(28rem,34rem)]' : ''}>
                    <Panel title="Account roles">
                        <ErrorAlert error={roles.error} />
                        {roles.loading
                            ? <LoadingState />
                            : <DataTable rows={roles.data?.data ?? []} rowKey={(row) => row.id} columns={roleColumns} />}
                    </Panel>
                    {canManage && (
                        <Panel title={selectedRole ? 'Edit account role' : 'New account role'}>
                            <ErrorAlert error={roleError} />
                            <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void saveRole(); }}>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Input label="Code" value={roleForm.code} onChange={(event) => setRoleForm({ ...roleForm, code: event.target.value })} error={fieldError(roleError, 'code')} required />
                                    <Input label="Name" value={roleForm.name} onChange={(event) => setRoleForm({ ...roleForm, name: event.target.value })} error={fieldError(roleError, 'name')} required />
                                </div>
                                <Input label="Description" value={roleForm.description ?? ''} onChange={(event) => setRoleForm({ ...roleForm, description: event.target.value || null })} error={fieldError(roleError, 'description')} />
                                <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="checkbox" checked={roleForm.is_active} onChange={(event) => setRoleForm({ ...roleForm, is_active: event.target.checked })} />
                                    Active
                                </label>
                                <div className="flex justify-end gap-2">
                                    {selectedRole && <Button type="button" variant="secondary" onClick={resetRole}>New</Button>}
                                    <Button type="submit" loading={savingRole}>{selectedRole ? 'Update role' : 'Create role'}</Button>
                                </div>
                            </form>
                        </Panel>
                    )}
                </div>

                <div className={canManage ? 'grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(25rem,30rem)]' : ''}>
                    <Panel title="Effective account assignments">
                        <ErrorAlert error={assignments.error ?? assignmentError} />
                        {assignments.loading
                            ? <LoadingState />
                            : <DataTable rows={assignments.data?.data ?? []} rowKey={(row) => row.id} columns={assignmentColumns} />}
                    </Panel>
                    {canManage && (
                        <Panel title={endingAssignmentId === null ? 'New effective assignment' : 'End assignment'}>
                            {!canViewAccounts ? (
                                <p className="text-sm text-slate-600">Finance account view permission is required before account assignments can be created or changed.</p>
                            ) : endingAssignmentId === null ? (
                                <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void saveAssignment(); }}>
                                    <Select
                                        label="Account role"
                                        value={assignmentForm.account_role_id || ''}
                                        onChange={(event) => setAssignmentForm({ ...assignmentForm, account_role_id: Number(event.target.value) || 0 })}
                                        options={activeRoles.map((role) => ({ value: String(role.id), label: `${role.code} - ${role.name}` }))}
                                        error={fieldError(assignmentError, 'account_role_id')}
                                    />
                                    <Select
                                        label="Finance account"
                                        value={assignmentForm.account_id || ''}
                                        onChange={(event) => setAssignmentForm({ ...assignmentForm, account_id: Number(event.target.value) || 0 })}
                                        options={activeAccounts.map((account) => ({ value: String(account.id), label: `${account.code} - ${account.name}` }))}
                                        error={fieldError(assignmentError, 'account_id')}
                                    />
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Input label="Effective from" type="date" value={assignmentForm.effective_from} onChange={(event) => setAssignmentForm({ ...assignmentForm, effective_from: event.target.value })} error={fieldError(assignmentError, 'effective_from')} required />
                                        <Input label="Effective to" type="date" value={assignmentForm.effective_to ?? ''} onChange={(event) => setAssignmentForm({ ...assignmentForm, effective_to: event.target.value || null })} error={fieldError(assignmentError, 'effective_to')} />
                                    </div>
                                    <div className="flex justify-end">
                                        <Button type="submit" loading={savingAssignment}>Create assignment</Button>
                                    </div>
                                </form>
                            ) : (
                                <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void endAssignment(); }}>
                                    <Input label="Effective end date" type="date" value={endingDate} onChange={(event) => setEndingDate(event.target.value)} error={fieldError(assignmentError, 'effective_to')} required />
                                    <div className="flex justify-end gap-2">
                                        <Button type="button" variant="secondary" onClick={() => setEndingAssignmentId(null)}>Cancel</Button>
                                        <Button type="submit" loading={savingAssignment}>End assignment</Button>
                                    </div>
                                </form>
                            )}
                        </Panel>
                    )}
                </div>
            </div>
        </>
    );
}

function todayDate(): string {
    return new Date().toISOString().slice(0, 10);
}
