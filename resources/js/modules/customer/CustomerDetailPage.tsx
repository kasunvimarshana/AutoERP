import { lazy, Suspense } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import { useApi } from '@/shared/hooks/useApi';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { getCustomer } from './customerApi';
import { CustomerSummaryCard } from './components/CustomerSummaryCard';

const CustomerContactTab = lazy(() => import('./components/CustomerContactTab'));
const CustomerAddressTab = lazy(() => import('./components/CustomerAddressTab'));
const CustomerBankAccountTab = lazy(() => import('./components/CustomerBankAccountTab'));
const CustomerCategoryTab = lazy(() => import('./components/CustomerCategoryTab'));
const CustomerDocumentTab = lazy(() => import('./components/CustomerDocumentTab'));
const CustomerCreditProfileTab = lazy(() => import('./components/CustomerCreditProfileTab'));
const CustomerStatusHistoryTab = lazy(() => import('./components/CustomerStatusHistoryTab'));

type Tab = 'summary' | 'contacts' | 'addresses' | 'bank_accounts' | 'categories' | 'documents' | 'credit_profile' | 'status_history';
const tabs = [['summary', 'Summary'], ['contacts', 'Contacts'], ['addresses', 'Addresses'], ['bank_accounts', 'Bank Accounts'], ['categories', 'Categories'], ['documents', 'Documents'], ['credit_profile', 'Credit Profile'], ['status_history', 'Status History']].map(([id, label]) => ({ id: id as Tab, label }));

export default function CustomerDetailPage() {
    const customerId = Number(useParams().id);
    const customer = useApi((signal) => getCustomer(customerId, signal), [customerId], Number.isFinite(customerId));
    const tab = useOnDemandTab<Tab>('summary');
    if (customer.loading) return <LoadingState />;
    if (!customer.data) return <ErrorAlert error={customer.error} />;

    return <>
        <ContentHeader
            title={`${customer.data.code} - ${customer.data.name}`}
            description="Customer master data and relation-aware CRUD."
            actions={<Link to={`/customers/${customerId}/edit`}><Button variant="secondary">Edit customer</Button></Link>}
        />
        <Panel className="p-0">
            <Tabs tabs={tabs} active={tab.activeTab} onChange={tab.openTab} />
            <div className="p-5">
                {tab.activeTab === 'summary' && <CustomerSummaryCard customer={customer.data} />}
                <Suspense fallback={<LoadingState />}>
                    {tab.openedTabs.has('contacts') && <div hidden={tab.activeTab !== 'contacts'}><CustomerContactTab customerId={customerId} /></div>}
                    {tab.openedTabs.has('addresses') && <div hidden={tab.activeTab !== 'addresses'}><CustomerAddressTab customerId={customerId} /></div>}
                    {tab.openedTabs.has('bank_accounts') && <div hidden={tab.activeTab !== 'bank_accounts'}><CustomerBankAccountTab customerId={customerId} /></div>}
                    {tab.openedTabs.has('categories') && <div hidden={tab.activeTab !== 'categories'}><CustomerCategoryTab customerId={customerId} /></div>}
                    {tab.openedTabs.has('documents') && <div hidden={tab.activeTab !== 'documents'}><CustomerDocumentTab customerId={customerId} /></div>}
                    {tab.openedTabs.has('credit_profile') && <div hidden={tab.activeTab !== 'credit_profile'}><CustomerCreditProfileTab customerId={customerId} /></div>}
                    {tab.openedTabs.has('status_history') && <div hidden={tab.activeTab !== 'status_history'}><CustomerStatusHistoryTab customerId={customerId} /></div>}
                </Suspense>
            </div>
        </Panel>
    </>;
}
