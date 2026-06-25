import { useEffect, useMemo, useState } from 'react';
import { toApiError, type ApiError, fieldError } from '@/shared/api/apiError';
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
import { createPostingProfile, getFinanceLookups, listPostingProfiles, updatePostingProfile, type PostingProfile, type PostingProfilePayload } from '../financeApi';

const emptyProfile: PostingProfilePayload = {
    code: '',
    name: '',
    description: null,
    is_active: true,
    rules: [
        { line_key: '', account_id: null },
        { line_key: '', account_id: null },
    ],
};

export default function PostingProfilePage() {
    const [selected, setSelected] = useState<PostingProfile | null>(null);
    const [form, setForm] = useState<PostingProfilePayload>(emptyProfile);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const profiles = useApi((signal) => listPostingProfiles({ per_page: 100 }, signal), []);
    const lookups = useApi((signal) => getFinanceLookups(signal), []);
    const accounts = useMemo(() => (lookups.data?.accounts ?? []).filter((account) => account.is_active && account.is_posting_account), [lookups.data?.accounts]);
    const columns: DataColumn<PostingProfile>[] = [
        { key: 'code', header: 'Profile', render: (row) => <button type="button" className="font-semibold text-sky-700 hover:underline" onClick={() => edit(row)}>{row.code} - {row.name}</button> },
        { key: 'lines', header: 'Lines', render: (row) => row.rules?.length ?? 0 },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
    ];

    useEffect(() => {
        if (!selected) return;
        setForm({
            code: selected.code,
            name: selected.name,
            description: typeof selected.description === 'string' ? selected.description : null,
            is_active: Boolean(selected.is_active),
            rules: (selected.rules ?? []).map((rule) => ({
                line_key: rule.line_key,
                account_id: rule.account?.id ? Number(rule.account.id) : Number(rule.account_id ?? 0) || null,
                description: typeof rule.description === 'string' ? rule.description : null,
            })),
        });
    }, [selected]);

    async function save() {
        setSaving(true);
        setError(null);
        try {
            const payload = { ...form, rules: form.rules.filter((rule) => rule.line_key.trim() && rule.account_id) };
            if (selected) {
                await updatePostingProfile(selected.id, payload);
            } else {
                await createPostingProfile(payload);
            }
            setSelected(null);
            setForm(emptyProfile);
            profiles.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    function edit(profile: PostingProfile) {
        setSelected(profile);
        setError(null);
    }

    return <>
        <ContentHeader title="Posting profiles" description="Configurable account mappings for accounting postings." />
        <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(28rem,34rem)]">
            <Panel title="Profiles">
                <ErrorAlert error={profiles.error} />
                {profiles.loading ? <LoadingState /> : <DataTable rows={profiles.data?.data ?? []} rowKey={(row) => row.id} columns={columns} />}
            </Panel>
            <Panel title={selected ? 'Edit profile' : 'New profile'}>
                <ErrorAlert error={error} />
                <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input label="Code" value={form.code} onChange={(event) => setForm({ ...form, code: event.target.value })} error={fieldError(error, 'code')} required />
                        <Input label="Name" value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} error={fieldError(error, 'name')} required />
                    </div>
                    <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} />
                        Active
                    </label>
                    <div className="space-y-3">
                        {form.rules.map((rule, index) => (
                            <div key={index} className="grid gap-3 sm:grid-cols-[1fr_1.5fr_auto]">
                                <Input label="Line key" value={rule.line_key} onChange={(event) => updateRule(index, { line_key: event.target.value })} error={fieldError(error, `rules.${index}.line_key`)} />
                                <Select label="Account" value={rule.account_id ?? ''} onChange={(event) => updateRule(index, { account_id: event.target.value ? Number(event.target.value) : null })} options={accounts.map((account) => ({ value: String(account.id), label: `${account.code} - ${account.name}` }))} error={fieldError(error, `rules.${index}.account_id`)} />
                                <div className="flex items-end">
                                    <Button type="button" variant="danger" disabled={form.rules.length <= 1} onClick={() => removeRule(index)}>Remove</Button>
                                </div>
                            </div>
                        ))}
                    </div>
                    <div className="flex flex-wrap justify-between gap-3">
                        <Button type="button" variant="secondary" onClick={() => setForm({ ...form, rules: [...form.rules, { line_key: '', account_id: null }] })}>Add line</Button>
                        <div className="flex gap-2">
                            {selected && <Button type="button" variant="secondary" onClick={() => { setSelected(null); setForm(emptyProfile); }}>New</Button>}
                            <Button type="submit" loading={saving}>{selected ? 'Update profile' : 'Create profile'}</Button>
                        </div>
                    </div>
                </form>
            </Panel>
        </div>
    </>;

    function updateRule(index: number, patch: Partial<PostingProfilePayload['rules'][number]>) {
        setForm({ ...form, rules: form.rules.map((rule, ruleIndex) => ruleIndex === index ? { ...rule, ...patch } : rule) });
    }

    function removeRule(index: number) {
        setForm({ ...form, rules: form.rules.filter((_, ruleIndex) => ruleIndex !== index) });
    }
}
