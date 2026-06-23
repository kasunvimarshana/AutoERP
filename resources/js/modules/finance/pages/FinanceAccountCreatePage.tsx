import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import { useApi } from '@/shared/hooks/useApi';
import { AccountForm } from '../components/AccountForm';
import { createAccount, getFinanceLookups, type AccountPayload } from '../financeApi';

const initial: AccountPayload = {
    account_type_id: null,
    account_category_id: null,
    parent_id: null,
    code: '',
    name: '',
    normal_balance: 'debit',
    opening_balance: '0.000000',
    is_control_account: false,
    is_posting_account: true,
    is_cash_account: false,
    is_bank_account: false,
    is_tax_account: false,
    is_active: true,
};

export default function FinanceAccountCreatePage() {
    const navigate = useNavigate();
    const lookups = useApi(getFinanceLookups, []);
    const [form, setForm] = useState(initial);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(submitting);

    if (lookups.loading) return <LoadingState />;
    if (!lookups.data) return <ErrorAlert error={lookups.error} />;

    async function save() {
        setSubmitting(true);
        setError(null);
        try {
            const account = await createAccount(form);
            formGuard.markSaved();
            navigate(`/finance/accounts/${account.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    }

    return <>
        <ContentHeader title="New account" description="Add a scoped chart-of-accounts record." />
        <ErrorAlert error={error} />
        <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
            <AccountForm value={form} onChange={(next) => { formGuard.markDirty(); setForm(next); }} lookups={lookups.data} error={error} />
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                <Button type="submit" loading={submitting}>Create account</Button>
            </div>
        </form>
    </>;
}
