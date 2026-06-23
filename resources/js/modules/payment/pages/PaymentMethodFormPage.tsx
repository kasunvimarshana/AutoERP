import { useEffect, useState, type FormEvent } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { createPaymentMethod, getPaymentMethod, updatePaymentMethod, type PaymentMethod } from '../paymentApi';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';

const methodTypes = [
    'cash', 'cheque', 'bank_transfer', 'card', 'mobile_wallet', 'direct_debit', 'other',
].map((value) => ({ value, label: value.replace(/_/g, ' ').replace(/\b\w/g, (match) => match.toUpperCase()) }));
const directions = [
    { value: 'both', label: 'Both' },
    { value: 'inbound', label: 'Inbound' },
    { value: 'outbound', label: 'Outbound' },
];

export default function PaymentMethodFormPage() {
    const params = useParams();
    const id = params.id ? Number(params.id) : null;
    const navigate = useNavigate();
    const existing = useApi((signal) => id ? getPaymentMethod(id, signal) : Promise.resolve(null), [id], id !== null);
    const [form, setForm] = useState<Partial<PaymentMethod>>({
        code: '',
        name: '',
        method_type: 'cash',
        direction_allowed: 'both',
        requires_reference: false,
        requires_bank_account: false,
        is_active: true,
        sort_order: 0,
    });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(busy);

    useEffect(() => {
        if (existing.data) {
            setForm({ ...existing.data });
        }
    }, [existing.data]);

    function update(patch: Partial<PaymentMethod>) {
        formGuard.markDirty();
        setForm((current) => ({ ...current, ...patch }));
    }

    async function submit(event: FormEvent) {
        event.preventDefault();
        setBusy(true);
        setError(null);
        try {
            const payload = {
                code: form.code,
                name: form.name,
                method_type: form.method_type,
                direction_allowed: form.direction_allowed,
                requires_reference: Boolean(form.requires_reference),
                requires_bank_account: Boolean(form.requires_bank_account),
                is_active: form.is_active !== false,
                sort_order: Number(form.sort_order ?? 0),
            };
            if (id) await updatePaymentMethod(id, payload);
            else await createPaymentMethod(payload);
            formGuard.markSaved();
            navigate('/payments/methods');
        } catch (caught) {
            setError(toApiError(caught));
        } finally {
            setBusy(false);
        }
    }

    if (id && existing.loading) return <LoadingState />;

    return <>
        <ContentHeader
            title={id ? 'Edit Payment Method' : 'Create Payment Method'}
            actions={<LinkButton to="/payments/methods" variant="secondary">Back</LinkButton>}
        />
        <ErrorAlert error={error ?? existing.error} />
        <Panel>
            <form className="max-w-3xl space-y-5" onSubmit={(event) => void submit(event)}>
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Code" value={form.code ?? ''} onChange={(event) => update({ code: event.target.value })} />
                    <Input label="Name" value={form.name ?? ''} onChange={(event) => update({ name: event.target.value })} />
                    <Select label="Method type" value={form.method_type ?? 'cash'} options={methodTypes} onChange={(event) => update({ method_type: event.target.value })} />
                    <Select label="Direction" value={form.direction_allowed ?? 'both'} options={directions} onChange={(event) => update({ direction_allowed: event.target.value })} />
                    <Input label="Sort order" type="number" value={String(form.sort_order ?? 0)} onChange={(event) => update({ sort_order: Number(event.target.value) })} />
                </div>
                <div className="grid gap-3 text-sm text-slate-700 sm:grid-cols-3">
                    <label className="flex items-center gap-2"><input type="checkbox" checked={Boolean(form.requires_reference)} onChange={(event) => update({ requires_reference: event.target.checked })} /> Requires reference</label>
                    <label className="flex items-center gap-2"><input type="checkbox" checked={Boolean(form.requires_bank_account)} onChange={(event) => update({ requires_bank_account: event.target.checked })} /> Requires bank account</label>
                    <label className="flex items-center gap-2"><input type="checkbox" checked={form.is_active !== false} onChange={(event) => update({ is_active: event.target.checked })} /> Active</label>
                </div>
                <div className="flex justify-end gap-2">
                    <LinkButton to="/payments/methods" variant="secondary">Cancel</LinkButton>
                    <Button type="submit" loading={busy}>{id ? 'Save Method' : 'Create Method'}</Button>
                </div>
            </form>
        </Panel>
    </>;
}