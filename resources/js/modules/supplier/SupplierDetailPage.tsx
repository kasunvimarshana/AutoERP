import { lazy, Suspense } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { EntityDetailLayout } from '@/shared/components/EntityDetailLayout';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import { useApi } from '@/shared/hooks/useApi';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { getSupplier } from './supplierApi';
import { SupplierSummaryCard } from './components/SupplierSummaryCard';

const SupplierContactTab = lazy(() => import('./components/SupplierContactTab'));
const SupplierAddressTab = lazy(() => import('./components/SupplierAddressTab'));
const SupplierBankAccountTab = lazy(() => import('./components/SupplierBankAccountTab'));
const SupplierCategoryTab = lazy(() => import('./components/SupplierCategoryTab'));
const SupplierDocumentTab = lazy(() => import('./components/SupplierDocumentTab'));
const SupplierItemMappingTab = lazy(() => import('./components/SupplierItemMappingTab'));
const SupplierCreditProfileTab = lazy(() => import('./components/SupplierCreditProfileTab'));
const SupplierStatusHistoryTab = lazy(() => import('./components/SupplierStatusHistoryTab'));

type Tab = 'summary' | 'contacts' | 'addresses' | 'bank_accounts' | 'categories' | 'documents' | 'item_mappings' | 'credit_profile' | 'status_history';
const tabs = [['summary', 'Summary'], ['contacts', 'Contacts'], ['addresses', 'Addresses'], ['bank_accounts', 'Bank Accounts'], ['categories', 'Categories'], ['documents', 'Documents'], ['item_mappings', 'Item Mappings'], ['credit_profile', 'Credit Profile'], ['status_history', 'Status History']].map(([id, label]) => ({ id: id as Tab, label }));

export default function SupplierDetailPage() {
    const supplierId = Number(useParams().id);
    const supplier = useApi((signal) => getSupplier(supplierId, signal), [supplierId], Number.isFinite(supplierId));
    const tab = useOnDemandTab<Tab>('summary');
    if (supplier.loading) return <LoadingState />;
    if (!supplier.data) return <ErrorAlert error={supplier.error} />;

    return <>
        <ContentHeader
            title={`${supplier.data.code} - ${supplier.data.name}`}
            description="Supplier master data and relation-aware CRUD."
            actions={<Link to={`/suppliers/${supplierId}/edit`}><Button variant="secondary">Edit supplier</Button></Link>}
        />
        <EntityDetailLayout actions={
            <>
                <Link to="/purchase/orders/create"><Button type="button" variant="secondary" className="w-full">Create purchase order</Button></Link>
                <Link to="/purchase/invoices/create"><Button type="button" variant="secondary" className="w-full">Create supplier invoice</Button></Link>
                <Link to="/purchase/payments/prepare"><Button type="button" variant="secondary" className="w-full">Prepare payment</Button></Link>
            </>
        }>
            <Panel className="p-0">
                <Tabs tabs={tabs} active={tab.activeTab} onChange={tab.openTab} />
                <div className="p-5">
                    {tab.activeTab === 'summary' && <SupplierSummaryCard supplier={supplier.data} />}
                    <Suspense fallback={<LoadingState />}>
                        {tab.openedTabs.has('contacts') && <div hidden={tab.activeTab !== 'contacts'}><SupplierContactTab supplierId={supplierId} /></div>}
                        {tab.openedTabs.has('addresses') && <div hidden={tab.activeTab !== 'addresses'}><SupplierAddressTab supplierId={supplierId} /></div>}
                        {tab.openedTabs.has('bank_accounts') && <div hidden={tab.activeTab !== 'bank_accounts'}><SupplierBankAccountTab supplierId={supplierId} /></div>}
                        {tab.openedTabs.has('categories') && <div hidden={tab.activeTab !== 'categories'}><SupplierCategoryTab supplierId={supplierId} /></div>}
                        {tab.openedTabs.has('documents') && <div hidden={tab.activeTab !== 'documents'}><SupplierDocumentTab supplierId={supplierId} /></div>}
                        {tab.openedTabs.has('item_mappings') && <div hidden={tab.activeTab !== 'item_mappings'}><SupplierItemMappingTab supplierId={supplierId} /></div>}
                        {tab.openedTabs.has('credit_profile') && <div hidden={tab.activeTab !== 'credit_profile'}><SupplierCreditProfileTab supplierId={supplierId} /></div>}
                        {tab.openedTabs.has('status_history') && <div hidden={tab.activeTab !== 'status_history'}><SupplierStatusHistoryTab supplierId={supplierId} /></div>}
                    </Suspense>
                </div>
            </Panel>
        </EntityDetailLayout>
    </>;
}
