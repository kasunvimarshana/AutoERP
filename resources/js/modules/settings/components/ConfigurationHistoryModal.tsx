import { useEffect, useState } from 'react';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { listConfigurationHistory } from '../settingsApi';
import type { ConfigurationDefinition, ConfigurationRevision, ConfigurationScope } from '../settingsTypes';

export function ConfigurationHistoryModal({
    definitions,
    selectedKey,
    scope,
    onSelectKey,
    onClose,
}: {
    definitions: ConfigurationDefinition[];
    selectedKey: string | null;
    scope: ConfigurationScope;
    onSelectKey: (key: string) => void;
    onClose: () => void;
}) {
    const [page, setPage] = useState(1);
    const selected = definitions.find((definition) => definition.key === selectedKey) ?? null;

    useEffect(() => {
        setPage(1);
    }, [selectedKey, scope]);

    const history = useApi(
        (signal) => listConfigurationHistory(scope, selectedKey ?? '', page, signal),
        [scope, selectedKey, page],
        selectedKey !== null,
    );

    return (
        <Modal
            open={selectedKey !== null}
            title={selected ? `${selected.label} history` : 'Configuration history'}
            onClose={onClose}
        >
            <div className="space-y-4">
                <Select
                    label="Setting"
                    value={selectedKey ?? ''}
                    options={definitions.map((definition) => ({
                        value: definition.key,
                        label: `${definition.label} · ${definition.owner}`,
                    }))}
                    onChange={(event) => onSelectKey(event.target.value)}
                />
                <p className="text-sm text-slate-500">
                    Immutable change history for this scope. Removed overrides remain available here.
                    Protected values are never stored or displayed.
                </p>
                <ErrorAlert error={history.error} />
                {history.loading && !history.data ? (
                    <LoadingState label="Loading history..." />
                ) : (
                    <div className="space-y-3">
                        {(history.data?.data ?? []).map((revision) => (
                            <HistoryItem key={revision.id} revision={revision} />
                        ))}
                        {(history.data?.data ?? []).length === 0 && (
                            <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">
                                No recorded changes exist for this setting.
                            </p>
                        )}
                    </div>
                )}
                <Pagination meta={history.data?.meta} onPageChange={setPage} />
            </div>
        </Modal>
    );
}

function HistoryItem({ revision }: { revision: ConfigurationRevision }) {
    return (
        <article className="rounded-lg border border-slate-200 p-4">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p className="font-medium text-slate-900">{humanize(revision.action)}</p>
                    <p className="text-xs text-slate-500">
                        {revision.changed_by_name ?? 'System'} · {new Date(revision.created_at).toLocaleString()}
                    </p>
                </div>
                <span className="rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-600">
                    Version {revision.entry_row_version}
                </span>
            </div>
            <div className="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                <HistoryValue
                    label="Before"
                    exists={revision.before_exists}
                    value={revision.before_value}
                    protectedValue={revision.sensitive}
                />
                <HistoryValue
                    label="After"
                    exists={revision.after_exists}
                    value={revision.after_value}
                    protectedValue={revision.sensitive}
                />
            </div>
        </article>
    );
}

function HistoryValue({ label, exists, value, protectedValue }: {
    label: string;
    exists: boolean;
    value: unknown;
    protectedValue: boolean;
}) {
    return (
        <div className="rounded-md bg-slate-50 p-3">
            <p className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</p>
            <p className="mt-1 break-words text-slate-800">
                {!exists ? 'No override' : protectedValue ? 'Protected value' : formatHistoryValue(value)}
            </p>
        </div>
    );
}

function formatHistoryValue(value: unknown): string {
    if (value === null || value === undefined) return 'Null value';
    if (typeof value === 'boolean') return value ? 'Enabled' : 'Disabled';
    if (typeof value === 'string') return value;
    return JSON.stringify(value);
}

function humanize(value: string): string {
    return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}
