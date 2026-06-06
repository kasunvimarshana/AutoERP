import { lazy, Suspense } from 'react';
import { Link, useParams } from 'react-router-dom';
import { getSupplier } from '../supplierApi';
import { useApi } from '@/shared/hooks/useApi';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Button } from '@/shared/components/Button';
import { Tabs } from '@/shared/components/Tabs';
import { Panel } from '@/shared/components/Panel';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { RecordTable } from '@/shared/components/RecordTable';

const LazyNotice = lazy(() => import('../relations/SupplierRelationNotice'));
type Tab = 'summary' | 'contacts' | 'addresses' | 'bank_accounts' | 'categories' | 'documents' | 'item_mappings' | 'credit_profile' | 'status_histories';
const tabs = [
    ['summary', 'Summary'], ['contacts', 'Contacts'], ['addresses', 'Addresses'], ['bank_accounts', 'Bank Accounts'],
    ['categories', 'Categories'], ['documents', 'Documents'], ['item_mappings', 'Item Mappings'],
    ['credit_profile', 'Credit Profile'], ['status_histories', 'Status History'],
] .map(([id, label]) => ({ id: id as Tab, label }));

export default function SupplierDetailPage() {
    const id = Number(useParams().id);
    const result = useApi((signal) => getSupplier(id, signal), [id], Number.isFinite(id));
    const tabState = useOnDemandTab<Tab>('summary');
    if (result.loading) return <LoadingState />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    const supplier = result.data;
    const relationRows: Record<string, unknown>[] = tabState.activeTab === 'categories'
        ? (supplier.categories ?? []).map((category) => ({ ...category }))
        : (supplier[tabState.activeTab as keyof typeof supplier] as Record<string, unknown>[] | undefined) ?? [];
    const fieldsByTab: Partial<Record<Tab, string[]>> = {
        contacts: ['contact_name', 'designation', 'email', 'phone', 'is_primary', 'is_active'],
        addresses: ['address_type', 'address_line_1', 'city', 'country', 'is_primary'],
        bank_accounts: ['bank_name', 'account_name', 'account_number', 'currency', 'is_primary'],
        categories: ['code', 'name', 'is_active'],
        documents: ['document_type', 'document_number', 'status', 'issued_date', 'expiry_date'],
        item_mappings: ['item', 'supplier_item_code', 'supplier_item_name', 'minimum_order_quantity', 'is_preferred'],
        status_histories: ['from_status', 'to_status', 'reason', 'changed_at'],
    };

    return (
        <>
            <ContentHeader title={supplier.name} description={supplier.code ?? undefined} actions={<Link to={`/suppliers/${id}/edit`}><Button variant="secondary">Edit supplier</Button></Link>} />
            <ErrorAlert error={result.error} />
            <Panel className="p-0">
                <Tabs tabs={tabs} active={tabState.activeTab} onChange={tabState.openTab} />
                <div className="p-5">
                    {tabState.activeTab === 'summary' && (
                        <DetailGrid items={[
                            { label: 'Status', value: <StatusBadge status={supplier.status} /> },
                            { label: 'Type', value: supplier.supplier_type },
                            { label: 'Email', value: supplier.email },
                            { label: 'Phone', value: supplier.phone },
                            { label: 'Credit limit', value: <MoneyDisplay value={supplier.credit_limit} /> },
                            { label: 'Opening balance', value: <MoneyDisplay value={supplier.opening_balance} /> },
                            { label: 'Credit allowed', value: supplier.is_credit_allowed ? 'Yes' : 'No' },
                            { label: 'Advance allowed', value: supplier.is_advance_allowed ? 'Yes' : 'No' },
                            { label: 'Notes', value: supplier.notes },
                        ]} />
                    )}
                    {tabState.activeTab === 'credit_profile' && (
                        <RecordTable rows={supplier.credit_profile ? [supplier.credit_profile] : []} fields={['credit_limit', 'credit_period_days', 'warning_threshold_percent', 'allow_over_credit', 'allow_partial_payment', 'is_active']} />
                    )}
                    {tabState.activeTab !== 'summary' && tabState.activeTab !== 'credit_profile' && (
                        <Suspense fallback={<LoadingState />}>
                            <LazyNotice />
                            <div className="mt-4"><RecordTable rows={relationRows} fields={fieldsByTab[tabState.activeTab] ?? []} /></div>
                        </Suspense>
                    )}
                </div>
            </Panel>
        </>
    );
}
