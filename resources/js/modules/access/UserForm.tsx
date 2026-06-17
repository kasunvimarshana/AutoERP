import { fieldError, firstFieldError, type ApiError } from '@/shared/api/apiError';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import type { AccessOrganizationUnitSummary, AccessRoleSummary } from './accessApi';

export interface UserFormState {
    first_name: string;
    last_name: string;
    username: string;
    email: string;
    phone: string;
    status: string;
    password: string;
    role_ids: number[];
    organization_unit_ids: number[];
    default_organization_unit_id: number | null;
    row_version?: number;
}

export function emptyUserForm(): UserFormState {
    return {
        first_name: '',
        last_name: '',
        username: '',
        email: '',
        phone: '',
        status: 'active',
        password: '',
        role_ids: [],
        organization_unit_ids: [],
        default_organization_unit_id: null,
    };
}

export function UserForm({
    value,
    roles,
    organizationUnits,
    error,
    includePassword,
    canAssignRoles,
    canManageOrganizationAccess,
    onChange,
}: {
    value: UserFormState;
    roles: AccessRoleSummary[];
    organizationUnits: AccessOrganizationUnitSummary[];
    error: ApiError | null;
    includePassword: boolean;
    canAssignRoles: boolean;
    canManageOrganizationAccess: boolean;
    onChange: (value: UserFormState) => void;
}) {
    const set = (patch: Partial<UserFormState>) => onChange({ ...value, ...patch });
    const toggleRole = (roleId: number) => set({
        role_ids: value.role_ids.includes(roleId)
            ? value.role_ids.filter((id) => id !== roleId)
            : [...value.role_ids, roleId],
    });
    const toggleOrganizationUnit = (organizationUnitId: number) => {
        const nextIds = value.organization_unit_ids.includes(organizationUnitId)
            ? value.organization_unit_ids.filter((id) => id !== organizationUnitId)
            : [...value.organization_unit_ids, organizationUnitId];
        const defaultId = nextIds.includes(value.default_organization_unit_id ?? 0)
            ? value.default_organization_unit_id
            : nextIds[0] ?? null;
        set({ organization_unit_ids: nextIds, default_organization_unit_id: defaultId });
    };

    return (
        <div className="space-y-5">
            <Panel title="Basic">
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="First name" required value={value.first_name} error={fieldError(error, 'first_name')} onChange={(event) => set({ first_name: event.target.value })} />
                    <Input label="Last name" value={value.last_name} error={fieldError(error, 'last_name')} onChange={(event) => set({ last_name: event.target.value })} />
                    <Input label="Username" value={value.username} error={fieldError(error, 'username')} onChange={(event) => set({ username: event.target.value })} />
                    <Input label="Email" type="email" required value={value.email} error={fieldError(error, 'email')} onChange={(event) => set({ email: event.target.value })} />
                    <Input label="Phone" value={value.phone} error={fieldError(error, 'phone')} onChange={(event) => set({ phone: event.target.value })} />
                    <Select label="Account status" value={value.status} options={statusOptions} error={fieldError(error, 'status')} onChange={(event) => set({ status: event.target.value })} />
                </div>
                {includePassword && (
                    <div className="mt-4 max-w-md">
                        <Input label="Temporary password" type="password" required value={value.password} error={fieldError(error, 'password')} onChange={(event) => set({ password: event.target.value })} />
                    </div>
                )}
            </Panel>

            <Panel title="Access">
                {canAssignRoles ? (
                    <CheckboxList
                        items={roles}
                        selectedIds={value.role_ids}
                        empty="No roles are available."
                        error={firstFieldError(error, ['role_ids', 'role_ids.0'])}
                        onToggle={toggleRole}
                    />
                ) : (
                    <p className="text-sm text-slate-500">Role assignment is not available for your account.</p>
                )}
            </Panel>

            <Panel title="Organization Access">
                {canManageOrganizationAccess ? (
                    <div className="space-y-4">
                        <CheckboxList
                            items={organizationUnits}
                            selectedIds={value.organization_unit_ids}
                            empty="No organization units are available."
                            error={firstFieldError(error, ['organization_unit_ids', 'organization_unit_ids.0'])}
                            onToggle={toggleOrganizationUnit}
                        />
                        <Select
                            label="Default organization unit"
                            value={value.default_organization_unit_id ? String(value.default_organization_unit_id) : ''}
                            options={organizationUnits
                                .filter((unit) => value.organization_unit_ids.includes(unit.id))
                                .map((unit) => ({ value: String(unit.id), label: unit.name }))}
                            error={fieldError(error, 'default_organization_unit_id')}
                            onChange={(event) => set({ default_organization_unit_id: event.target.value ? Number(event.target.value) : null })}
                        />
                    </div>
                ) : (
                    <p className="text-sm text-slate-500">Organization access management is not available for your account.</p>
                )}
            </Panel>
        </div>
    );
}

function CheckboxList<T extends { id: number; name: string }>({
    items,
    selectedIds,
    empty,
    error,
    onToggle,
}: {
    items: T[];
    selectedIds: number[];
    empty: string;
    error?: string;
    onToggle: (id: number) => void;
}) {
    if (items.length === 0) {
        return <p className="text-sm text-slate-500">{empty}</p>;
    }

    return (
        <div>
            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                {items.map((item) => (
                    <label key={item.id} className="flex min-h-11 items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                        <input type="checkbox" className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked={selectedIds.includes(item.id)} onChange={() => onToggle(item.id)} />
                        <span>{item.name}</span>
                    </label>
                ))}
            </div>
            {error && <p className="mt-2 text-xs text-rose-600">{error}</p>}
        </div>
    );
}

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'suspended', label: 'Suspended' },
];
