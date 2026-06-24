import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { listActiveReferenceRecords } from '@/modules/reference-data/referenceDataApi';
import type { ReferenceCatalog, ReferenceRecord } from '@/modules/reference-data/referenceDataTypes';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
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
    canManageGlobal: boolean;
    canManageSensitive: boolean;
}

type EditorTarget = ConfigurationEntry | 'create' | null;

export function ConfigurationSettingsPanel({
    permissions,
    hasOrganizationUnit,
    mode,
    canManageGlobal,
    canManageSensitive,
}: Props) {
    const { confirm, confirmDialog } = useConfirmDialog();
    const [searchParams, setSearchParams] = useSearchParams();
    const scopes = useMemo(() => availableScopes(hasOrganizationUnit, mode), [hasOrganizationUnit, mode]);
    const scope = readScope(searchParams.get('scope'), scopes);
    const search = searchParams.get('search') ?? '';
    const owner = searchParams.get('owner') ?? '';
    const page = positivePage(searchParams.get('page'));
    const debouncedSearch = useDebounce(search);
    const [editing, setEditing] = useState<EditorTarget>(null);
    const [editorDirty, setEditorDirty] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [working, setWorking] = useState(false);
    const [success, setSuccess] = useState<string | null>(null);

    const definitions = useApi(
        (signal) => listConfigurationDefinitions(scope, signal),
        [scope],
        true,
        false,
    );
    const entries = useApi(
        (signal) => listConfigurationEntries(scope, {
            search: debouncedSearch || undefined,
            owner: owner || undefined,
            page,
        }, signal),
        [scope, debouncedSearch, owner, page],
        true,
        false,
    );

    const canManage = scope === 'global'
        ? canManageGlobal
        : scope === 'organization_unit'
            ? permissions.includes('configuration.entries.manage_organization')
            : permissions.includes('configuration.entries.manage_tenant');
    const owners = useMemo(() => {
        const values = (definitions.data ?? [])
            .filter((definition) => definition.allowed_scopes.includes(scope))
            .map((definition) => definition.owner);
        return [...new Set(values)].sort((left, right) => left.localeCompare(right));
    }, [definitions.data, scope]);
    const existingKeys = entries.data?.existing_keys ?? [];
    const addableDefinitions = (definitions.data ?? []).filter((definition) =>
        definition.runtime_mutable
        && definition.allowed_scopes.includes(scope)
        && !existingKeys.includes(definition.key)
        && (!definition.sensitive || canManageSensitive),
    );

    function updateQuery(updates: Record<string, string | null>) {
        const next = new URLSearchParams(searchParams);
        for (const [key, value] of Object.entries(updates)) {
            if (value === null || value === '') next.delete(key);
            else next.set(key, value);
        }
        setSearchParams(next, { replace: true });
    }

    async function requestCloseEditor() {
        if (working) return;
        if (editorDirty && !await confirm({
            title: 'Discard configuration changes',
            message: 'Close this editor and discard the unsaved value?',
            confirmLabel: 'Discard changes',
            danger: true,
        })) return;
        setEditing(null);
        setEditorDirty(false);
        setActionError(null);
    }

    async function remove(entry: ConfigurationEntry) {
        const inherited = formatConfigurationValue(
            entry.inherited_value,
            entry.inherited_display_value,
            entry.sensitive,
        );
        if (!await confirm({
            title: 'Remove configuration override',
            message: `Remove the ${entry.label} override? ${scopeLabel(entry.inherited_source_scope)} will take effect with value “${inherited}”.`,
            confirmLabel: 'Remove override',
            danger: true,
        })) return;

        setWorking(true);
        setActionError(null);
        setSuccess(null);
        try {
            await deleteConfigurationEntry(scope, entry);
            entries.reload();
            setSuccess(`${entry.label} override was removed. ${scopeLabel(entry.inherited_source_scope)} is now effective.`);
        } catch (requestError: unknown) {
            const nextError = toApiError(requestError);
            setActionError(nextError);
            if (nextError.status === 409) entries.reload();
        } finally {
            setWorking(false);
        }
    }

    const actions = (row: ConfigurationEntry) => {
        if (!canManage) return null;
        const sensitiveDenied = row.sensitive && !canManageSensitive;
        return (
            <div className="flex flex-wrap justify-end gap-2">
                <Button
                    variant="secondary"
                    disabled={working || sensitiveDenied}
                    title={sensitiveDenied ? 'Sensitive settings require the platform secret-management permission.' : undefined}
                    onClick={() => { setActionError(null); setSuccess(null); setEditorDirty(false); setEditing(row); }}
                >
                    Replace
                </Button>
                <Button
                    variant="danger"
                    disabled={working || sensitiveDenied}
                    title={sensitiveDenied ? 'Sensitive settings require the platform secret-management permission.' : undefined}
                    onClick={() => void remove(row)}
                >
                    Remove
                </Button>
            </div>
        );
    };

    const columns: DataColumn<ConfigurationEntry>[] = [
        {
            key: 'setting',
            header: 'Setting',
            render: (row) => <SettingSummary row={row} />,
        },
        {
            key: 'override',
            header: 'Override',
            render: (row) => (
                <div>
                    <p className="font-medium text-slate-900">{formatEntryValue(row)}</p>
                    <p className="mt-1 text-xs text-slate-500">Stored in {scopeLabel(row.scope)}</p>
                </div>
            ),
        },
        {
            key: 'inheritance',
            header: 'After removal',
            render: (row) => (
                <div>
                    <p className="font-medium text-slate-900">{formatConfigurationValue(row.inherited_value, row.inherited_display_value, row.sensitive)}</p>
                    <p className="mt-1 text-xs text-slate-500">From {scopeLabel(row.inherited_source_scope)}</p>
                </div>
            ),
        },
        {
            key: 'updated',
            header: 'Updated',
            render: (row) => formatBusinessDateTime(row.updated_at),
        },
        { key: 'actions', header: '', mobile: false, render: actions },
    ];

    const rows = entries.data?.data ?? [];
    const loadingInitial = entries.loading && !entries.data;
    const refreshing = entries.loading && Boolean(entries.data);
    const loadError = definitions.error ?? entries.error;

    return (
        <section className="space-y-5">
            <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                {mode === 'platform' ? (
                    <div className="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                        <p className="font-semibold">Global defaults</p>
                        <p className="mt-1">These values apply only when a tenant or organization unit has no more specific override. Protected values are replacement-only and never displayed.</p>
                    </div>
                ) : null}
                <div className={`grid gap-4 ${mode === 'platform' ? 'lg:grid-cols-[minmax(260px,1fr)_minmax(220px,0.6fr)_auto]' : 'lg:grid-cols-[minmax(220px,0.6fr)_minmax(260px,1fr)_minmax(220px,0.6fr)_auto]'} lg:items-end`}>
                    {mode === 'tenant' ? (
                        <Select
                            label="Settings scope"
                            value={scope}
                            options={scopes}
                            onChange={(event) => updateQuery({ scope: event.target.value, page: null })}
                        />
                    ) : null}
                    <Input
                        label="Search settings"
                        value={search}
                        placeholder="Name, description, or setting key"
                        onChange={(event) => updateQuery({ search: event.target.value, page: null })}
                    />
                    <Select
                        label="Owner"
                        value={owner}
                        options={owners.map((value) => ({ value, label: value }))}
                        placeholder="All owners"
                        onChange={(event) => updateQuery({ owner: event.target.value, page: null })}
                    />
                    <div>
                        {canManage ? (
                            <Button
                                disabled={working || definitions.loading || Boolean(definitions.error) || addableDefinitions.length === 0}
                                onClick={() => { setActionError(null); setSuccess(null); setEditorDirty(false); setEditing('create'); }}
                            >
                                Add override
                            </Button>
                        ) : null}
                    </div>
                </div>
                <p className="mt-3 text-sm text-slate-500">Only registered, runtime-mutable settings are available. The table shows exactly what becomes effective after an override is removed.</p>
            </div>

            <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
            <ErrorAlert error={loadError} title="Unable to load configuration" />
            <ErrorAlert error={actionError} title="Configuration action failed" />

            {loadingInitial ? <LoadingState label="Loading settings..." /> : (
                <div className="relative">
                    {refreshing ? <div className="absolute right-3 top-3 z-10 rounded bg-white/90 px-3 py-1 text-xs font-medium text-slate-600 shadow">Refreshing…</div> : null}
                    <DataTable
                        rows={rows}
                        columns={columns}
                        rowKey={(row) => row.key}
                        emptyMessage="No overrides match the current filters."
                        mobileSummary={(row) => row.label}
                        rowBadge={(row) => row.sensitive ? <MetadataBadge label="Protected" tone="amber" /> : null}
                        mobileDetails={(row) => (
                            <div className="space-y-2">
                                <SettingSummary row={row} compact />
                                <MobileValue label="Override" value={formatEntryValue(row)} source={scopeLabel(row.scope)} />
                                <MobileValue label="After removal" value={formatConfigurationValue(row.inherited_value, row.inherited_display_value, row.sensitive)} source={scopeLabel(row.inherited_source_scope)} />
                                <p className="text-xs text-slate-500">Updated {formatBusinessDateTime(row.updated_at)}</p>
                            </div>
                        )}
                        mobileActions={actions}
                    />
                </div>
            )}
            <Pagination meta={entries.data?.meta} onPageChange={(nextPage) => updateQuery({ page: nextPage === 1 ? null : String(nextPage) })} />

            <Modal
                open={editing !== null}
                title={editing === 'create' ? 'Add configuration override' : 'Replace configuration override'}
                onClose={() => void requestCloseEditor()}
                closeDisabled={working}
            >
                {definitions.loading && !definitions.data ? <LoadingState label="Loading registered settings..." /> : null}
                <ErrorAlert error={definitions.error} title="Unable to load registered settings" />
                {editing && definitions.data ? (
                    <ConfigurationForm
                        key={editing === 'create' ? `create-${scope}` : `${editing.key}-${editing.row_version}`}
                        scope={scope}
                        definitions={definitions.data}
                        existingKeys={existingKeys}
                        entry={editing === 'create' ? null : editing}
                        submitting={working}
                        canManageSensitive={canManageSensitive}
                        error={actionError}
                        onDirtyChange={setEditorDirty}
                        onCancel={() => void requestCloseEditor()}
                        onSubmit={async (definition, value) => {
                            setWorking(true);
                            setActionError(null);
                            setSuccess(null);
                            try {
                                const saved = editing === 'create'
                                    ? await createConfigurationEntry(scope, definition.key, value)
                                    : await updateConfigurationEntry(scope, editing, value);
                                setEditing(null);
                                setEditorDirty(false);
                                entries.reload();
                                setSuccess(`${saved.label} was saved for ${scopeLabel(scope)}.`);
                            } catch (requestError: unknown) {
                                const nextError = toApiError(requestError);
                                setActionError(nextError);
                                if (nextError.status === 409) {
                                    entries.reload();
                                    setEditing(null);
                                    setEditorDirty(false);
                                }
                            } finally {
                                setWorking(false);
                            }
                        }}
                    />
                ) : null}
            </Modal>
            {confirmDialog}
        </section>
    );
}

function ConfigurationForm({
    scope,
    definitions,
    existingKeys,
    entry,
    submitting,
    canManageSensitive,
    error,
    onDirtyChange,
    onCancel,
    onSubmit,
}: {
    scope: ConfigurationScope;
    definitions: ConfigurationDefinition[];
    existingKeys: string[];
    entry: ConfigurationEntry | null;
    submitting: boolean;
    canManageSensitive: boolean;
    error: ApiError | null;
    onDirtyChange: (dirty: boolean) => void;
    onCancel: () => void;
    onSubmit: (definition: ConfigurationDefinition, value: unknown) => Promise<void>;
}) {
    const candidates = definitions.filter((definition) => definition.runtime_mutable
        && definition.allowed_scopes.includes(scope)
        && (entry !== null || !existingKeys.includes(definition.key))
        && (!definition.sensitive || canManageSensitive));
    const [key, setKey] = useState(entry?.key ?? candidates[0]?.key ?? '');
    const definition = definitions.find((item) => item.key === key) ?? null;
    const initialValue = toEditorValue(entry?.value ?? definition?.default_value, definition);
    const [rawValue, setRawValue] = useState(initialValue);
    const [fieldError, setFieldError] = useState('');
    const dirty = rawValue !== initialValue || (entry === null && key !== (candidates[0]?.key ?? ''));
    useUnsavedChanges(dirty && !submitting, 'You have an unsaved configuration value. Leave and discard it?');
    useEffect(() => onDirtyChange(dirty), [dirty, onDirtyChange]);

    const lookup = useApi(
        (signal) => listActiveReferenceRecords(
            (definition?.lookup ?? 'timezones') as ReferenceCatalog,
            signal,
        ),
        [definition?.lookup],
        Boolean(definition?.lookup),
        false,
    );

    if (candidates.length === 0 && entry === null) {
        return <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">No additional settings are available for this scope and permission set.</p>;
    }

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
            <ErrorAlert error={lookup.error} title="Unable to load setting options" />
            <ErrorAlert error={error} title="Configuration action failed" />
            <Select
                label="Setting"
                value={key}
                disabled={entry !== null || submitting}
                options={candidates.map((candidate) => ({ value: candidate.key, label: `${candidate.label} · ${candidate.owner}` }))}
                onChange={(event) => {
                    const selected = definitions.find((item) => item.key === event.target.value) ?? null;
                    setKey(event.target.value);
                    setRawValue(toEditorValue(selected?.default_value, selected));
                    setFieldError('');
                }}
            />
            {definition ? (
                <div className="rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                    <div className="flex flex-wrap gap-2">
                        <MetadataBadge label={definition.owner} />
                        <MetadataBadge label={definition.runtime_mutable ? 'Runtime mutable' : 'Restart required'} tone={definition.runtime_mutable ? 'emerald' : 'slate'} />
                        {definition.sensitive ? <MetadataBadge label="Protected" tone="amber" /> : null}
                    </div>
                    <p className="mt-3">{definition.description}</p>
                    <p className="mt-1 text-xs">This override applies to {scopeLabel(scope)}.</p>
                    {definition.sensitive ? <p className="mt-2 font-medium text-amber-800">The existing value is protected. Enter a complete replacement; leaving the field empty does not preserve the old value.</p> : null}
                </div>
            ) : null}
            {definition ? (
                <ValueEditor
                    definition={definition}
                    value={rawValue}
                    error={fieldError}
                    lookup={lookup.data ?? []}
                    disabled={submitting}
                    onChange={setRawValue}
                />
            ) : null}
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" disabled={submitting} onClick={onCancel}>Cancel</Button>
                <Button type="submit" loading={submitting} disabled={!definition || !dirty}>Save override</Button>
            </div>
        </form>
    );
}

function ValueEditor({ definition, value, error, lookup, disabled, onChange }: {
    definition: ConfigurationDefinition;
    value: string;
    error: string;
    lookup: ReferenceRecord[];
    disabled: boolean;
    onChange: (value: string) => void;
}) {
    if (definition.value_type === 'boolean') {
        return <Select label={definition.label} error={error || undefined} value={value} disabled={disabled} options={[{ value: 'true', label: 'Enabled' }, { value: 'false', label: 'Disabled' }]} onChange={(event) => onChange(event.target.value)} />;
    }
    if (definition.options.length > 0) {
        return <Select label={definition.label} error={error || undefined} value={value} disabled={disabled} options={definition.options.map((option) => ({ value: String(option), label: humanize(String(option)) }))} onChange={(event) => onChange(event.target.value)} />;
    }
    if (definition.lookup) {
        const catalog = definition.lookup as ReferenceCatalog;
        return (
            <Select
                label={definition.label}
                error={error || undefined}
                value={value}
                disabled={disabled}
                options={lookup.map((option) => ({ value: referenceValue(catalog, option), label: referenceLabel(option) }))}
                onChange={(event) => onChange(event.target.value)}
            />
        );
    }
    if (definition.value_type === 'json' && isStringListDefinition(definition)) {
        return <Textarea label={`${definition.label} (one value per line)`} error={error || undefined} value={value} disabled={disabled} onChange={(event) => onChange(event.target.value)} />;
    }
    if (definition.value_type === 'json') {
        return <Textarea label={`${definition.label} · advanced JSON`} error={error || undefined} value={value} disabled={disabled} onChange={(event) => onChange(event.target.value)} />;
    }
    return (
        <Input
            label={definition.label}
            error={error || undefined}
            value={value}
            disabled={disabled}
            type={definition.sensitive ? 'password' : 'text'}
            inputMode={definition.value_type === 'integer' ? 'numeric' : definition.value_type === 'decimal' ? 'decimal' : undefined}
            onChange={(event) => onChange(event.target.value)}
        />
    );
}

function availableScopes(hasOrganizationUnit: boolean, mode: 'tenant' | 'platform'): Array<{ value: ConfigurationScope; label: string }> {
    if (mode === 'platform') return [{ value: 'global', label: 'Global defaults' }];
    const scopes: Array<{ value: ConfigurationScope; label: string }> = [{ value: 'tenant', label: 'Active tenant' }];
    if (hasOrganizationUnit) scopes.push({ value: 'organization_unit', label: 'Active organization unit' });
    return scopes;
}

function readScope(value: string | null, scopes: Array<{ value: ConfigurationScope }>): ConfigurationScope {
    return scopes.some((scope) => scope.value === value) ? value as ConfigurationScope : scopes[0]?.value ?? 'tenant';
}

function positivePage(value: string | null): number {
    const parsed = Number(value);
    return Number.isSafeInteger(parsed) && parsed > 0 ? parsed : 1;
}

function parseEditorValue(raw: string, definition: ConfigurationDefinition): unknown {
    if (definition.nullable && raw.trim() === '') return null;
    if (definition.value_type === 'boolean') return raw === 'true';
    if (definition.value_type === 'integer') {
        if (!/^-?\d+$/.test(raw.trim())) throw new Error('Enter a whole number.');
        return raw.trim();
    }
    if (definition.value_type === 'decimal') {
        if (!/^-?\d+(?:\.\d+)?$/.test(raw.trim())) throw new Error('Enter a plain decimal number.');
        return raw.trim();
    }
    if (definition.value_type === 'json' && isStringListDefinition(definition)) {
        return raw.split(/\r?\n/).map((value) => value.trim()).filter(Boolean);
    }
    if (definition.value_type === 'json') {
        try { return JSON.parse(raw); } catch { throw new Error('Enter valid JSON.'); }
    }
    if (raw.trim() === '') throw new Error('Enter a value.');
    return raw;
}

function toEditorValue(value: unknown, definition?: ConfigurationDefinition | null): string {
    if (value === null || value === undefined) return '';
    if (definition?.value_type === 'json' && isStringListDefinition(definition) && Array.isArray(value)) {
        return value.map(String).join('\n');
    }
    return typeof value === 'string' ? value : typeof value === 'object' ? JSON.stringify(value, null, 2) : String(value);
}

function isStringListDefinition(definition: ConfigurationDefinition): boolean {
    return definition.value_type === 'json'
        && Array.isArray(definition.default_value)
        && definition.default_value.every((value) => typeof value === 'string');
}

function formatEntryValue(entry: ConfigurationEntry): string {
    return formatConfigurationValue(entry.value, entry.display_value, entry.sensitive);
}

function formatConfigurationValue(value: unknown, displayValue: string | null, sensitive: boolean): string {
    if (sensitive) return displayValue ?? 'Configured (protected)';
    if (value === null || value === undefined) return 'Not configured';
    if (typeof value === 'boolean') return value ? 'Enabled' : 'Disabled';
    if (typeof value === 'string') return value;
    if (Array.isArray(value)) return value.map(String).join(', ');
    return JSON.stringify(value);
}

function SettingSummary({ row, compact = false }: { row: ConfigurationEntry; compact?: boolean }) {
    return (
        <div>
            <p className="font-semibold text-slate-900">{row.label}</p>
            {!compact ? <p className="text-xs text-slate-500">{row.description}</p> : null}
            <div className="mt-2 flex flex-wrap gap-1.5">
                <MetadataBadge label={row.owner} />
                <MetadataBadge label={row.runtime_mutable ? 'Runtime mutable' : 'Restart required'} tone={row.runtime_mutable ? 'emerald' : 'slate'} />
                {row.sensitive ? <MetadataBadge label="Protected" tone="amber" /> : null}
            </div>
        </div>
    );
}

function MetadataBadge({ label, tone = 'blue' }: { label: string; tone?: 'blue' | 'emerald' | 'amber' | 'slate' }) {
    const className = {
        blue: 'bg-blue-50 text-blue-700',
        emerald: 'bg-emerald-50 text-emerald-700',
        amber: 'bg-amber-50 text-amber-800',
        slate: 'bg-slate-100 text-slate-600',
    }[tone];
    return <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${className}`}>{label}</span>;
}

function MobileValue({ label, value, source }: { label: string; value: string; source: string }) {
    return <div><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p><p className="font-medium text-slate-900">{value}</p><p className="text-xs text-slate-500">{source}</p></div>;
}

function scopeLabel(scope: ConfigurationScope | 'default'): string {
    return ({
        global: 'global default',
        tenant: 'tenant override',
        organization_unit: 'organization-unit override',
        default: 'definition default',
    } as const)[scope];
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
