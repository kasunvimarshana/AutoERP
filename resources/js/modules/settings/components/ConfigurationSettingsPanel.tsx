import { useMemo, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { listActiveReferenceRecords } from '@/modules/reference-data/referenceDataApi';
import type { ReferenceCatalog, ReferenceRecord } from '@/modules/reference-data/referenceDataTypes';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { formatBusinessDateTime } from '@/shared/utils/businessDate';
import {
    createConfigurationEntry,
    deleteConfigurationEntry,
    listConfigurationDefinitions,
    listConfigurationEntries,
    updateConfigurationEntry,
} from '../settingsApi';
import type {
    ConfigurationDefinition,
    ConfigurationEntry,
    ConfigurationScope,
} from '../settingsTypes';

interface Props {
    permissions: string[];
    hasOrganizationUnit: boolean;
    mode: 'tenant' | 'platform';
}

export function ConfigurationSettingsPanel({ permissions, hasOrganizationUnit, mode }: Props) {
    const scopes = useMemo(() => availableScopes(hasOrganizationUnit, mode), [hasOrganizationUnit, mode]);
    const [scope, setScope] = useState<ConfigurationScope>(scopes[0]?.value ?? 'tenant');
    const [prefix, setPrefix] = useState('');
    const [page, setPage] = useState(1);
    const [editing, setEditing] = useState<ConfigurationEntry | 'create' | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [working, setWorking] = useState(false);
    const definitions = useApi((signal) => listConfigurationDefinitions(scope, signal), [scope]);
    const entries = useApi(
        (signal) => listConfigurationEntries(scope, prefix, page, signal),
        [scope, prefix, page],
    );
    const canManage = scope === 'global'
        ? mode === 'platform'
        : scope === 'organization_unit'
            ? permissions.includes('configuration.entries.manage_organization')
            : permissions.includes('configuration.entries.manage_tenant');

    const columns: DataColumn<ConfigurationEntry>[] = [
        {
            key: 'setting',
            header: 'Setting',
            render: (row) => (
                <div>
                    <p className="font-semibold text-slate-900">{row.label}</p>
                    <p className="text-xs text-slate-500">{row.description}</p>
                </div>
            ),
        },
        { key: 'owner', header: 'Owner', render: (row) => row.owner },
        { key: 'value', header: 'Current override', render: (row) => formatValue(row) },
        { key: 'updated', header: 'Updated', render: (row) => formatBusinessDateTime(row.updated_at) },
        {
            key: 'actions',
            header: '',
            render: (row) => (
                <div className="flex justify-end gap-2">
                    {canManage && <>
                        <Button variant="secondary" onClick={() => setEditing(row)}>Edit</Button>
                        <Button variant="danger" onClick={() => void remove(row)}>Remove</Button>
                    </>}
                </div>
            ),
        },
    ];

    async function remove(entry: ConfigurationEntry) {
        if (!window.confirm(`Remove the ${entry.label} override from this scope? The inherited value will take effect.`)) return;
        setWorking(true);
        setActionError(null);
        try {
            await deleteConfigurationEntry(scope, entry);
            entries.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setWorking(false);
        }
    }

    return (
        <section className="space-y-5">
            <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="grid gap-4 lg:grid-cols-[minmax(220px,0.7fr)_minmax(260px,1fr)_auto] lg:items-end">
                    <Select
                        label="Settings scope"
                        value={scope}
                        options={scopes}
                        onChange={(event) => { setScope(event.target.value as ConfigurationScope); setPage(1); }}
                    />
                    <Input
                        label="Filter by area"
                        value={prefix}
                        placeholder="Example: inventory."
                        onChange={(event) => { setPrefix(event.target.value); setPage(1); }}
                    />
                    <div>
                        {canManage && (
                            <Button onClick={() => { setActionError(null); setEditing('create'); }}>
                                Add override
                            </Button>
                        )}
                    </div>
                </div>
                <p className="mt-3 text-sm text-slate-500">Only registered settings can be changed. Removing an override restores the next inherited value.</p>
            </div>

            <ErrorAlert error={actionError ?? definitions.error ?? entries.error} />
            {entries.loading ? <LoadingState label="Loading settings..." /> : (
                <DataTable rows={entries.data?.data ?? []} columns={columns} rowKey={(row) => row.key} emptyMessage="No overrides exist in this scope." />
            )}
            <Pagination meta={entries.data?.meta} onPageChange={setPage} />

            <Modal
                open={editing !== null}
                title={editing === 'create' ? 'Add configuration override' : 'Edit configuration override'}
                onClose={() => !working && setEditing(null)}
            >
                {editing && definitions.data && (
                    <ConfigurationForm
                        scope={scope}
                        definitions={definitions.data}
                        existing={entries.data?.data ?? []}
                        entry={editing === 'create' ? null : editing}
                        submitting={working}
                        error={actionError}
                        onCancel={() => setEditing(null)}
                        onSubmit={async (definition, value) => {
                            setWorking(true);
                            setActionError(null);
                            try {
                                if (editing === 'create') await createConfigurationEntry(scope, definition.key, value);
                                else await updateConfigurationEntry(scope, editing, value);
                                setEditing(null);
                                entries.reload();
                            } catch (error) {
                                setActionError(toApiError(error));
                            } finally {
                                setWorking(false);
                            }
                        }}
                    />
                )}
            </Modal>
        </section>
    );
}

function ConfigurationForm({ scope, definitions, existing, entry, submitting, error, onCancel, onSubmit }: {
    scope: ConfigurationScope;
    definitions: ConfigurationDefinition[];
    existing: ConfigurationEntry[];
    entry: ConfigurationEntry | null;
    submitting: boolean;
    error: ApiError | null;
    onCancel: () => void;
    onSubmit: (definition: ConfigurationDefinition, value: unknown) => Promise<void>;
}) {
    const candidates = definitions.filter((definition) => definition.runtime_mutable
        && definition.allowed_scopes.includes(scope)
        && (entry !== null || !existing.some((current) => current.key === definition.key)));
    const [key, setKey] = useState(entry?.key ?? candidates[0]?.key ?? '');
    const definition = definitions.find((item) => item.key === key) ?? null;
    const [rawValue, setRawValue] = useState(() => toEditorValue(entry?.value ?? definition?.default_value));
    const [fieldError, setFieldError] = useState('');
    const lookup = useApi(
        (signal) => listActiveReferenceRecords(
            (definition?.lookup ?? 'timezones') as ReferenceCatalog,
            signal,
        ),
        [definition?.lookup],
        Boolean(definition?.lookup),
    );

    return (
        <form className="space-y-4" onSubmit={(event) => {
            event.preventDefault();
            if (!definition) return;
            try {
                const value = parseEditorValue(rawValue, definition);
                setFieldError('');
                void onSubmit(definition, value);
            } catch (parseError) {
                setFieldError(parseError instanceof Error ? parseError.message : 'Enter a valid value.');
            }
        }}>
            <ErrorAlert error={error ?? lookup.error} />
            <Select
                label="Setting"
                value={key}
                disabled={entry !== null}
                options={candidates.map((candidate) => ({ value: candidate.key, label: `${candidate.label} · ${candidate.owner}` }))}
                onChange={(event) => {
                    const selected = definitions.find((item) => item.key === event.target.value);
                    setKey(event.target.value);
                    setRawValue(toEditorValue(selected?.default_value));
                    setFieldError('');
                }}
            />
            {definition && (
                <div className="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">
                    <p>{definition.description}</p>
                    <p className="mt-1 text-xs">This override applies to the selected {scope.replace('_', ' ')} scope.</p>
                </div>
            )}
            {definition && (
                <ValueEditor
                    definition={definition}
                    value={rawValue}
                    error={fieldError}
                    lookup={lookup.data ?? []}
                    onChange={setRawValue}
                />
            )}
            <div className="flex justify-end gap-2">
                <Button variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit" loading={submitting} disabled={!definition}>Save override</Button>
            </div>
        </form>
    );
}

function ValueEditor({ definition, value, error, lookup, onChange }: {
    definition: ConfigurationDefinition;
    value: string;
    error: string;
    lookup: ReferenceRecord[];
    onChange: (value: string) => void;
}) {
    if (definition.value_type === 'boolean') {
        return <Select label={definition.label} error={error || undefined} value={value} options={[{ value: 'true', label: 'Enabled' }, { value: 'false', label: 'Disabled' }]} onChange={(event) => onChange(event.target.value)} />;
    }
    if (definition.options.length > 0) {
        return <Select label={definition.label} error={error || undefined} value={value} options={definition.options.map((option) => ({ value: String(option), label: humanize(String(option)) }))} onChange={(event) => onChange(event.target.value)} />;
    }
    if (definition.lookup) {
        const catalog = definition.lookup as ReferenceCatalog;

        return (
            <Select
                label={definition.label}
                error={error || undefined}
                value={value}
                options={lookup.map((option) => ({
                    value: referenceValue(catalog, option),
                    label: referenceLabel(option),
                }))}
                onChange={(event) => onChange(event.target.value)}
            />
        );
    }
    if (definition.value_type === 'json') {
        return <Textarea label={definition.label} error={error || undefined} value={value} onChange={(event) => onChange(event.target.value)} />;
    }
    return <Input label={definition.label} error={error || undefined} value={value} type={['integer', 'decimal'].includes(definition.value_type) ? 'number' : definition.sensitive ? 'password' : 'text'} min={definition.minimum ?? undefined} max={definition.maximum ?? undefined} onChange={(event) => onChange(event.target.value)} />;
}

function availableScopes(
    hasOrganizationUnit: boolean,
    mode: 'tenant' | 'platform',
): Array<{ value: ConfigurationScope; label: string }> {
    if (mode === 'platform') {
        return [{ value: 'global', label: 'Global defaults' }];
    }

    const scopes: Array<{ value: ConfigurationScope; label: string }> = [
        { value: 'tenant', label: 'Active tenant' },
    ];

    if (hasOrganizationUnit) {
        scopes.push({ value: 'organization_unit', label: 'Active organization unit' });
    }

    return scopes;
}

function parseEditorValue(raw: string, definition: ConfigurationDefinition): unknown {
    if (definition.nullable && raw.trim() === '') return null;
    if (definition.value_type === 'boolean') return raw === 'true';
    if (definition.value_type === 'integer') {
        const value = Number(raw);
        if (!Number.isInteger(value)) throw new Error('Enter a whole number.');
        return value;
    }
    if (definition.value_type === 'decimal') {
        const value = Number(raw);
        if (!Number.isFinite(value)) throw new Error('Enter a valid number.');
        return value;
    }
    if (definition.value_type === 'json') {
        try { return JSON.parse(raw); } catch { throw new Error('Enter valid JSON.'); }
    }
    if (raw.trim() === '') throw new Error('Enter a value.');
    return raw;
}

function toEditorValue(value: unknown): string {
    if (value === null || value === undefined) return '';
    return typeof value === 'string' ? value : typeof value === 'object' ? JSON.stringify(value, null, 2) : String(value);
}

function formatValue(entry: ConfigurationEntry): string {
    if (entry.sensitive) return entry.display_value ?? 'Protected value';
    if (typeof entry.value === 'boolean') return entry.value ? 'Enabled' : 'Disabled';
    if (typeof entry.value === 'string') return humanize(entry.value);
    return JSON.stringify(entry.value);
}

function humanize(value: string): string {
    return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function referenceValue(catalog: ReferenceCatalog, record: ReferenceRecord): string {
    return catalog === 'timezones' ? record.name : record.code ?? record.name;
}

function referenceLabel(record: ReferenceRecord): string {
    const name = record.display_name ?? record.name;
    return record.code ? `${record.code} · ${name}` : name;
}
