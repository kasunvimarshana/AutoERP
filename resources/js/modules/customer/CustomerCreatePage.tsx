import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import type { NamedResource } from '@/shared/types/common';
import { createCustomer, createCustomerWithRelations } from './customerApi';
import type { CustomerPayload, CustomerWithRelationsPayload } from './customerTypes';
import { CustomerForm } from './components/CustomerForm';
import { emptyCustomerOneShotDraft, CustomerOneShotBuilder, type CustomerOneShotDraft } from './components/CustomerOneShotBuilder';

type Tab = 'basic' | 'contacts' | 'addresses' | 'bank_accounts' | 'categories' | 'documents' | 'credit_profile' | 'review';
const tabs = [['basic', 'Basic'], ['contacts', 'Contacts'], ['addresses', 'Addresses'], ['bank_accounts', 'Bank Accounts'], ['categories', 'Categories'], ['documents', 'Documents'], ['credit_profile', 'Credit Profile'], ['review', 'Review']].map(([id, label]) => ({ id: id as Tab, label }));
const initialCustomer: CustomerPayload = { customer_number: null, code: '', name: '', customer_type: 'company', status: 'pending_approval', default_currency_id: null, credit_limit: '0.000000', opening_balance: '0.000000', is_credit_allowed: true, is_advance_allowed: true, is_tax_exempt: false, marketing_consent: false, preferred_communication_channel: null };

export default function CustomerCreatePage() {
    const navigate = useNavigate();
    const [customer, setCustomer] = useState(initialCustomer);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [oneShot, setOneShot] = useState(true);
    const [activeTab, setActiveTab] = useState<Tab>('basic');
    const [draft, setDraft] = useState<CustomerOneShotDraft>(emptyCustomerOneShotDraft);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    async function save() {
        setSubmitting(true); setError(null);
        try {
            const saved = oneShot ? await createCustomerWithRelations(toPayload(customer, draft)) : await createCustomer(customer);
            navigate(`/customers/${saved.id}`);
        } catch (requestError) { setError(toApiError(requestError)); }
        finally { setSubmitting(false); }
    }
    return <><ContentHeader title="New customer" description="Capture basic info, contacts, addresses, documents, and credit profile from one screen." /><div className="mb-4 flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-4"><input id="customer-one-shot" type="checkbox" checked={oneShot} onChange={(event) => { setOneShot(event.target.checked); setActiveTab('basic'); }} /><label htmlFor="customer-one-shot" className="text-sm font-medium">Save related details together</label></div><ErrorAlert error={error} /><form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>{oneShot && <Panel className="p-0"><Tabs tabs={tabs} active={activeTab} onChange={setActiveTab} /></Panel>}{(!oneShot || activeTab === 'basic') && <CustomerForm value={customer} onChange={setCustomer} currency={currency} onCurrencyChange={setCurrency} error={error} creating />}{oneShot && activeTab !== 'basic' && <Panel><CustomerOneShotBuilder section={activeTab} value={draft} onChange={setDraft} /></Panel>}<div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button><Button type="submit" loading={submitting}>{oneShot ? 'Save everything' : 'Create customer'}</Button></div></form></>;
}
function toPayload(customer: CustomerPayload, draft: CustomerOneShotDraft): CustomerWithRelationsPayload {
    return { customer, contacts: draft.contacts, addresses: draft.addresses, bank_accounts: draft.bankAccounts.map(({ currency: _currency, ...row }) => row), categories: draft.categories.map((category) => Number(category.id)), documents: draft.documents, credit_profile: draft.creditProfile };
}
