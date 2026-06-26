import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormActions } from '@/shared/components/FormActions';
import { Panel } from '@/shared/components/Panel';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import { useAuth } from '@/modules/auth/AuthProvider';
import { accessApi } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';
import { emptyRoleForm, RoleForm, type RoleFormState } from './RoleForm';

export default function RoleCreatePage() {
    const navigate = useNavigate();
    const auth = useAuth();
    const [form, setForm] = useState<RoleFormState>(() => emptyRoleForm());
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const canCreate = hasAccessPermission(auth, accessPermissions.rolesCreate);
    const dirty = useMemo(() => JSON.stringify(form) !== JSON.stringify(emptyRoleForm()), [form]);
    const confirmDiscard = useUnsavedChanges(dirty && !submitting);

    const save = async () => {
        if (submitting) return;
        setSubmitting(true);
        setError(null);
        try {
            const created = await accessApi.createRole({
                name: form.name,
                description: form.description || null,
            });
            navigate(`/access/roles/${created.id}/edit`);
        } catch (caught) {
            setError(toApiError(caught));
        } finally {
            setSubmitting(false);
        }
    };

    if (!canCreate) {
        return (
            <>
                <ContentHeader title="Create Role" description="Create a tenant role." />
                <CapabilityNotice>You do not have permission to create roles.</CapabilityNotice>
            </>
        );
    }

    return (
        <>
            <ContentHeader title="Create Role" description="Create the role first, then assign system-defined permissions in the guided management screen." />
            <ErrorAlert error={error} />
            <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                <RoleForm value={form} error={error} onChange={setForm} />
                <Panel title="Permission assignment">
                    <p className="text-sm text-slate-600">Permission assignment is a separate privileged action. After creation, authorized administrators can assign permissions without coupling that action to role-profile editing.</p>
                </Panel>
                <FormActions>
                    <Button type="button" variant="secondary" onClick={() => confirmDiscard() && navigate('/access/roles')}>Cancel</Button>
                    <Button type="submit" loading={submitting}>Create Role</Button>
                </FormActions>
            </form>
        </>
    );
}
