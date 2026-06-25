import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import { AccountForm } from '../components/AccountForm';
import { getAccount, getFinanceLookups, updateAccount, type AccountPayload, type FinanceLookups } from '../financeApi';

export default function FinanceAccountEditPage() {
    const accountId = Number(useParams().id);
    const navigate = useNavigate();
    const [form, setForm] = useState<AccountPayload | null>(null);
    const [lookups, setLookups] = useState<FinanceLookups | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(submitting);

    useEffect(() => {
        const controller = new AbortController();
        Promise.all([getAccount(accountId, controller.signal), getFinanceLookups(controller.signal)])
            .then(([account, options]) => {
                if (controller.signal.aborted) return;
                setLookups(options);
                setForm({
                    account_type_id: account.account_type ? Number(account.account_type.id) : null,
                    account_category_id: account.account_category ? Number(account.account_category.id) : null,
                    parent_id: account.parent ? Number(account.parent.id) : null,
                    code: account.code,
                    name: account.name,
                    description: account.description,
                    normal_balance: account.normal_balance,
                    opening_balance: account.opening_balance,
                    is_control_account: account.is_control_account,
                    is_posting_account: account.is_posting_account,
                    is_cash_account: account.is_cash_account,
                    is_bank_account: account.is_bank_account,
                    is_tax_account: account.is_tax_account,
                    is_active: account.is_active,
                });
            })
            .catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError)))
            .finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [accountId]);

    if (loading) return <LoadingState />;
    if (!form || !lookups) return <ErrorAlert error={error} />;

    async function save() {
        if (!form) return;
        setSubmitting(true);
        setError(null);
        try {
            const account = await updateAccount(accountId, form);
            formGuard.markSaved();
            navigate(`/finance/accounts/${account.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    }

    return <>
        <ContentHeader title="Edit account" description="Update account classification and posting behavior." />
        <ErrorAlert error={error} />
        <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
            <AccountForm value={form} onChange={(next) => { formGuard.markDirty(); setForm(next); }} lookups={lookups} error={error} accountId={accountId} />
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                <Button type="submit" loading={submitting}>Save account</Button>
            </div>
        </form>
    </>;
}
