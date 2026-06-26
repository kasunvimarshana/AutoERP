import { firstFieldError, fieldError, type ApiError } from '@/shared/api/apiError';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import type { AccessOrganizationUnitSummary, AccessRoleSummary } from './accessApi';

export interface UserAccessState {
    role_ids: number[];
    organization_unit_ids: number[];
    default_organization_unit_id: number | null;
}

export function emptyUserAccess(): UserAccessState {
    return { role_ids: [], organization_unit_ids: [], default_organization_unit_id: null };
}

export function UserAccessFields({
    value,
    roles,
    organizationUnits,
    error,
    canAssignRoles,
    canManageOrganizationAccess,
    onChange,
}: {
    value: UserAccessState;
    roles: AccessRoleSummary[];
    organizationUnits: AccessOrganizationUnitSummary[];
    error: ApiError | null;
    canAssignRoles: boolean;
    canManageOrganizationAccess: boolean;
    onChange: (value: UserAccessState) => void;
}) {
    const set = (patch: Partial<UserAccessState>) => onChange({ ...value, ...patch });
    const toggleRole = (roleId: number) => set({
        role_ids: toggleId(value.role_ids, roleId),
    });
    const toggleOrganizationUnit = (organizationUnitId: number) => {
        const nextIds = toggleId(value.organization_unit_ids, organizationUnitId);
        const nextDefault = nextIds.includes(value.default_organization_unit_id ?? 0)
            ? value.default_organization_unit_id
            : nextIds[0] ?? null;
        set({ organization_unit_ids: nextIds, default_organization_unit_id: nextDefault });
    };

    return (
        <>
            <Panel title="Initial roles">
                <p className="mb-3 text-sm text-slate-600">Roles define the user’s normal capabilities. Direct permissions can be managed separately after the invitation is created.</p>
                {canAssignRoles ? (
                    <CheckboxList
                        items={roles}
                        selectedIds={value.role_ids}
                        empty="No roles are available."
                        error={firstFieldError(error, ['role_ids', 'role_ids.0'])}
                        onToggle={toggleRole}
                    />
                ) : (
                    <p className="text-sm text-slate-500">You cannot assign roles. The user will be invited without a role.</p>
                )}
            </Panel>

            <Panel title="Organization access">
                <p className="mb-3 text-sm text-slate-600">Choose where this user may work and select one default organization unit. The default is used when the user signs in.</p>
                {canManageOrganizationAccess ? (
                    <div className="space-y-4">
                        <CheckboxList
                            items={organizationUnits}
                            selectedIds={value.organization_unit_ids}
                            empty="No active organization units are available."
                            error={firstFieldError(error, ['organization_unit_ids', 'organization_unit_ids.0'])}
                            onToggle={toggleOrganizationUnit}
                        />
                        <Select
                            label="Default organization unit"
                            required
                            value={value.default_organization_unit_id ? String(value.default_organization_unit_id) : ''}
                            options={organizationUnits
                                .filter((unit) => value.organization_unit_ids.includes(unit.id))
                                .map((unit) => ({ value: String(unit.id), label: unit.code ? `${unit.name} (${unit.code})` : unit.name }))}
                            error={fieldError(error, 'default_organization_unit_id')}
                            onChange={(event) => set({ default_organization_unit_id: event.target.value ? Number(event.target.value) : null })}
                        />
                    </div>
                ) : (
                    <p className="text-sm text-rose-700">Organization access permission is required because every tenant user must have an active default organization unit.</p>
                )}
            </Panel>
        </>
    );
}

export function CheckboxList<T extends { id: number; name: string; code?: string | null }>({
    items,
    selectedIds,
    empty,
    error,
    disabled = false,
    onToggle,
}: {
    items: T[];
    selectedIds: number[];
    empty: string;
    error?: string;
    disabled?: boolean;
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
                        <input
                            type="checkbox"
                            className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            checked={selectedIds.includes(item.id)}
                            disabled={disabled}
                            onChange={() => onToggle(item.id)}
                        />
                        <span>
                            <span className="block font-medium text-slate-900">{item.name}</span>
                            {item.code ? <span className="block text-xs text-slate-500">{item.code}</span> : null}
                        </span>
                    </label>
                ))}
            </div>
            {error && <p className="mt-2 text-xs text-rose-600">{error}</p>}
        </div>
    );
}

function toggleId(ids: number[], id: number): number[] {
    return ids.includes(id) ? ids.filter((candidate) => candidate !== id) : [...ids, id];
}
