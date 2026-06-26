import { useMemo } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import type { AccessPermission } from './accessApi';

export interface RoleFormState {
    name: string;
    description: string;
    row_version?: number;
}

export function emptyRoleForm(): RoleFormState {
    return { name: '', description: '' };
}

export function RoleForm({
    value,
    error,
    onChange,
}: {
    value: RoleFormState;
    error: ApiError | null;
    onChange: (value: RoleFormState) => void;
}) {
    const set = (patch: Partial<RoleFormState>) => onChange({ ...value, ...patch });

    return (
        <Panel title="Role details">
            <div className="grid gap-4 md:grid-cols-2">
                <Input label="Role name" required value={value.name} error={fieldError(error, 'name')} onChange={(event) => set({ name: event.target.value })} />
            </div>
            <div className="mt-4">
                <Textarea label="Description" value={value.description} error={fieldError(error, 'description')} onChange={(event) => set({ description: event.target.value })} />
            </div>
        </Panel>
    );
}

export function PermissionSelector({
    permissions,
    selectedIds,
    search,
    error,
    readOnly = false,
    onSearchChange,
    onChange,
}: {
    permissions: AccessPermission[];
    selectedIds: number[];
    search: string;
    error?: string;
    readOnly?: boolean;
    onSearchChange: (value: string) => void;
    onChange?: (selectedIds: number[]) => void;
}) {
    const selected = new Set(selectedIds);
    const groups = useMemo(() => groupedPermissions(permissions, search), [permissions, search]);
    const toggle = (permissionId: number) => {
        if (readOnly || !onChange) return;
        onChange(selected.has(permissionId)
            ? selectedIds.filter((id) => id !== permissionId)
            : [...selectedIds, permissionId]);
    };
    const setGroup = (permissionIds: number[], checked: boolean) => {
        if (readOnly || !onChange) return;
        const next = new Set(selectedIds);
        permissionIds.forEach((id) => {
            if (checked) next.add(id);
            else next.delete(id);
        });
        onChange([...next].sort((left, right) => left - right));
    };

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div className="w-full max-w-md">
                    <Input label="Search permissions" type="search" value={search} onChange={(event) => onSearchChange(event.target.value)} />
                </div>
                <p className="text-sm font-medium text-slate-600">{selectedIds.length} selected</p>
            </div>
            {error && <p className="text-xs text-rose-600">{error}</p>}
            {groups.length === 0 ? <p className="text-sm text-slate-500">No permissions match the current search.</p> : (
                <div className="space-y-3">
                    {groups.map((group) => {
                        const ids = group.permissions.map((permission) => permission.id);
                        const allSelected = ids.every((id) => selected.has(id));
                        return (
                            <section key={group.key} className="rounded-lg border border-slate-200 bg-white">
                                <div className="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                                    <div>
                                        <h3 className="text-sm font-semibold text-slate-900">{group.label}</h3>
                                        <p className="text-xs text-slate-500">{group.permissions.length} permissions</p>
                                    </div>
                                    {!readOnly && (
                                        <Button type="button" variant="secondary" className="min-h-8 px-3 py-1 text-xs" onClick={() => setGroup(ids, !allSelected)}>
                                            {allSelected ? 'Clear' : 'Select All'}
                                        </Button>
                                    )}
                                </div>
                                <div className="grid gap-2 p-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {group.permissions.map((permission) => (
                                        <label key={permission.id} className="flex min-h-11 items-start gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                            <input
                                                type="checkbox"
                                                className="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                checked={selected.has(permission.id)}
                                                disabled={readOnly}
                                                onChange={() => toggle(permission.id)}
                                            />
                                            <span className="min-w-0">
                                                <span className="block font-medium text-slate-900">{permission.action ?? humanizePermission(permission.name)}</span>
                                                <span className="block break-words font-mono text-xs text-slate-500">{permission.name}</span>
                                                {permission.description ? <span className="mt-1 block text-xs text-slate-500">{permission.description}</span> : null}
                                            </span>
                                        </label>
                                    ))}
                                </div>
                            </section>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

function groupedPermissions(permissions: AccessPermission[], search: string) {
    const needle = search.trim().toLowerCase();
    const filtered = needle
        ? permissions.filter((permission) => [
            permission.name,
            permission.module,
            permission.resource,
            permission.action,
            permission.description,
        ].some((value) => String(value ?? '').toLowerCase().includes(needle)))
        : permissions;
    const map = new Map<string, { key: string; label: string; permissions: AccessPermission[] }>();

    filtered.forEach((permission) => {
        const module = permission.module || permission.resource || 'Other';
        const key = module.toLowerCase();
        const group = map.get(key) ?? { key, label: humanizePermission(module), permissions: [] };
        group.permissions.push(permission);
        map.set(key, group);
    });

    return [...map.values()].map((group) => ({
        ...group,
        permissions: group.permissions.sort((left, right) => left.name.localeCompare(right.name)),
    })).sort((left, right) => left.label.localeCompare(right.label));
}

function humanizePermission(value: string): string {
    return value.replace(/[._-]+/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}
