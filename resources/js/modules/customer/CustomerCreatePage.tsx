import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormActions } from '@/shared/components/FormActions';
import { Panel } from '@/shared/components/Panel';
import { TabPanel, Tabs } from '@/shared/components/Tabs';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import type { NamedResource } from '@/shared/types/common';
import { createCustomer, createCustomerWithRelations } from './customerApi';
import type { CustomerPayload, CustomerWithRelationsPayload } from './customerTypes';
import { CustomerForm } from './components/CustomerForm';
import { emptyCustomerOneShotDraft, CustomerOneShotBuilder, type CustomerOneShotDraft } from './components/CustomerOneShotBuilder';

type Tab = 'basic' | 'contacts' | 'addresses' | 'bank_accounts' | 'categories' | 'documents' | 'credit_profile' | 'review';
type RelatedTab = Exclude<Tab, 'basic'>;
const tabs = [['basic','Basic'],['contacts','Contacts'],['addresses','Addresses'],['bank_accounts','Bank Accounts'],['categories','Categories'],['documents','Documents'],['credit_profile','Credit Profile'],['review','Review']].map(([id,label]) => ({ id: id as Tab, label }));
const relatedTabs = tabs.filter((tab) => tab.id !== 'basic') as { id: RelatedTab; label: string }[];
const initialCustomer: CustomerPayload = { customer_number: null, code: '', name: '', customer_type: 'company', default_currency_id: null, credit_limit: '0.000000', is_credit_allowed: true, is_advance_allowed: true, is_tax_exempt: false, marketing_consent: false, preferred_communication_channel: null };

export default function CustomerCreatePage() {
    const navigate = useNavigate();
    const [customer, setCustomer] = useState(initialCustomer);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [includeRelated, setIncludeRelated] = useState(false);
    const [activeTab, setActiveTab] = useState<Tab>('basic');
    const [draft, setDraft] = useState<CustomerOneShotDraft>(emptyCustomerOneShotDraft);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const dirty = JSON.stringify(customer) !== JSON.stringify(initialCustomer) || JSON.stringify(draft) !== JSON.stringify(emptyCustomerOneShotDraft) || currency !== null;
    const confirmDiscard = useUnsavedChanges(dirty && !submitting);
    async function save() {
        setSubmitting(true); setError(null);
        try {
            const saved = includeRelated ? await createCustomerWithRelations(toPayload(customer, draft)) : await createCustomer(customer);
            navigate(`/customers/${saved.id}`);
        } catch (requestError) { setError(toApiError(requestError)); setSubmitting(false); }
    }
    return <>
        <ContentHeader title="New customer" description="Customers start pending approval. Activation is a separate authorized lifecycle command." />
        <ErrorAlert error={error} title="Customer could not be saved" />
        <form className="mx-auto max-w-6xl space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
            <Panel><label className="flex min-h-11 cursor-pointer items-center gap-3"><input type="checkbox" checked={includeRelated} onChange={(event) => { setIncludeRelated(event.target.checked); setActiveTab('basic'); }} /><span><span className="block text-sm font-semibold text-slate-900">Add related records before saving</span><span className="block text-sm text-slate-500">Optional contacts, addresses, bank accounts, documents, and credit settings.</span></span></label></Panel>
            {includeRelated && <Panel className="p-0"><Tabs id="customer-create" tabs={tabs} active={activeTab} onChange={setActiveTab} /></Panel>}
            {includeRelated ? <><TabPanel tabsId="customer-create" tabId="basic" active={activeTab}><CustomerForm value={customer} onChange={setCustomer} currency={currency} onCurrencyChange={setCurrency} error={error} /></TabPanel>{relatedTabs.map((tab) => <TabPanel key={tab.id} tabsId="customer-create" tabId={tab.id} active={activeTab}><Panel><CustomerOneShotBuilder section={tab.id} value={draft} onChange={setDraft} /></Panel></TabPanel>)}</> : <CustomerForm value={customer} onChange={setCustomer} currency={currency} onCurrencyChange={setCurrency} error={error} />}
            <FormActions><Button type="button" variant="secondary" onClick={() => confirmDiscard() && navigate(-1)}>Cancel</Button><Button type="submit" loading={submitting}>{includeRelated ? 'Create customer and related records' : 'Create customer'}</Button></FormActions>
        </form>
    </>;
}

function toPayload(customer: CustomerPayload, draft: CustomerOneShotDraft): CustomerWithRelationsPayload {
    return { customer, contacts: draft.contacts, addresses: draft.addresses, bank_accounts: draft.bankAccounts.map(({ currency: _currency, ...row }) => row), categories: draft.categories.map((category) => Number(category.id)), documents: draft.documents, credit_profile: draft.creditProfile };
}
