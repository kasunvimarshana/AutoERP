import { useState } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { createConfigurationEntry, listConfigurationEntries, type ConfigurationEntry } from './settingsApi';

export default function SettingsPage() {
    const auth = useAuth();
    const tenantId = Number(auth.tenant?.id);
    const [prefix, setPrefix] = useState('');
    const [page, setPage] = useState(1);
    const [open, setOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const result = useApi((signal) => listConfigurationEntries({
        tenant_id: tenantId,
        scope: 'tenant',
        prefix: prefix || undefined,
        page,
        per_page: 25,
    }, signal), [page, prefix, tenantId]);
    const columns: DataColumn<ConfigurationEntry>[] = [
        { key: 'key', header: 'Setting', render: (row) => <span className="font-mono text-xs font-semibold text-slate-900">{row.key}</span> },
        { key: 'value', header: 'Value', render: (row) => formatValue(row.value) },
        { key: 'source', header: 'Source', render: (row) => row.source ?? '-' },
        { key: 'description', header: 'Description', render: (row) => row.description ?? '-' },
    ];

    return (
        <>
            <ContentHeader title="Settings" description="Tenant configuration entries used by existing AutoERP modules." actions={<Button onClick={() => { setActionError(null); setOpen(true); }}>New setting</Button>} />
            <div className="mb-5 max-w-md">
                <Input label="Key prefix" value={prefix} placeholder="Example: finance." onChange={(event) => { setPrefix(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={actionError ?? result.error} />
            {result.loading
                ? <LoadingState label="Loading settings..." />
                : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.key} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
            <Modal open={open} title="New setting" onClose={() => !submitting && setOpen(false)}>
                <SettingForm
                    submitting={submitting}
                    error={actionError}
                    onCancel={() => setOpen(false)}
                    onSubmit={async (payload) => {
                        setSubmitting(true);
                        setActionError(null);
                        try {
                            await createConfigurationEntry({ ...payload, tenant_id: tenantId, scope: 'tenant', source: 'database' });
                            setOpen(false);
                            result.reload();
                        } catch (error) {
                            setActionError(toApiError(error));
                        } finally {
                            setSubmitting(false);
                        }
                    }}
                />
            </Modal>
        </>
    );
}

function SettingForm({
    submitting,
    error,
    onCancel,
    onSubmit,
}: {
    submitting: boolean;
    error: ApiError | null;
    onCancel: () => void;
    onSubmit: (payload: Record<string, unknown>) => Promise<void>;
}) {
    const [key, setKey] = useState('');
    const [value, setValue] = useState('');
    const [valueType, setValueType] = useState('string');
    const [description, setDescription] = useState('');

    return (
        <form className="space-y-4" onSubmit={(event) => {
            event.preventDefault();
            void onSubmit({
                key,
                value: parseValue(value, valueType),
                description: description || undefined,
            });
        }}>
            <ErrorAlert error={error} />
            <Input label="Key" required value={key} placeholder="module.setting-name" onChange={(event) => setKey(event.target.value)} />
            <Select label="Value type" value={valueType} options={['string', 'number', 'boolean', 'json'].map((entry) => ({ value: entry, label: entry }))} onChange={(event) => setValueType(event.target.value)} />
            <Textarea label="Value" required value={value} onChange={(event) => setValue(event.target.value)} />
            <Textarea label="Description" value={description} onChange={(event) => setDescription(event.target.value)} />
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit" loading={submitting}>Create setting</Button>
            </div>
        </form>
    );
}

function parseValue(value: string, type: string): unknown {
    if (type === 'number') return Number(value);
    if (type === 'boolean') return value.trim().toLowerCase() === 'true';
    if (type === 'json') return JSON.parse(value);
    return value;
}

function formatValue(value: unknown): string {
    if (typeof value === 'string') return value;
    return JSON.stringify(value);
}
