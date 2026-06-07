import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import type { NamedResource } from '@/shared/types/common';
import { createSupplier, createSupplierWithRelations } from './supplierApi';
import type { SupplierPayload, SupplierWithRelationsPayload } from './supplierTypes';
import { SupplierForm } from './components/SupplierForm';
import { emptySupplierOneShotDraft, SupplierOneShotBuilder, type SupplierOneShotDraft } from './components/SupplierOneShotBuilder';

type Tab = 'basic' | 'contacts' | 'addresses' | 'bank_accounts' | 'categories' | 'documents' | 'item_mappings' | 'credit_profile' | 'review';
const tabs = [['basic', 'Basic'], ['contacts', 'Contacts'], ['addresses', 'Addresses'], ['bank_accounts', 'Bank Accounts'], ['categories', 'Categories'], ['documents', 'Documents'], ['item_mappings', 'Item Mappings'], ['credit_profile', 'Credit Profile'], ['review', 'Review']].map(([id, label]) => ({ id: id as Tab, label }));
const initialSupplier: SupplierPayload = { supplier_number: null, code: '', name: '', supplier_type: 'company', status: 'pending_approval', default_currency_id: null, credit_limit: '0.000000', is_credit_allowed: true, is_advance_allowed: true };

export default function SupplierCreatePage() {
    const navigate = useNavigate();
    const [supplier, setSupplier] = useState(initialSupplier);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [oneShot, setOneShot] = useState(true);
    const [activeTab, setActiveTab] = useState<Tab>('basic');
    const [draft, setDraft] = useState<SupplierOneShotDraft>(emptySupplierOneShotDraft);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    async function save() {
        setSubmitting(true); setError(null);
        try {
            const saved = oneShot ? await createSupplierWithRelations(toPayload(supplier, draft)) : await createSupplier(supplier);
            navigate(`/suppliers/${saved.id}`);
        } catch (requestError) { setError(toApiError(requestError)); }
        finally { setSubmitting(false); }
    }
    return <><ContentHeader title="New supplier" description="Capture supplier profile, contacts, documents, credit profile, and item mappings from one screen." /><div className="mb-4 flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-4"><input id="supplier-one-shot" type="checkbox" checked={oneShot} onChange={(event) => { setOneShot(event.target.checked); setActiveTab('basic'); }} /><label htmlFor="supplier-one-shot" className="text-sm font-medium">Save related details together</label></div><ErrorAlert error={error} /><form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>{oneShot && <Panel className="p-0"><Tabs tabs={tabs} active={activeTab} onChange={setActiveTab} /></Panel>}{(!oneShot || activeTab === 'basic') && <SupplierForm value={supplier} onChange={setSupplier} currency={currency} onCurrencyChange={setCurrency} error={error} creating />}{oneShot && activeTab !== 'basic' && <Panel><SupplierOneShotBuilder section={activeTab} value={draft} onChange={setDraft} /></Panel>}<div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button><Button type="submit" loading={submitting}>{oneShot ? 'Save everything' : 'Create supplier'}</Button></div></form></>;
}
function toPayload(supplier: SupplierPayload, draft: SupplierOneShotDraft): SupplierWithRelationsPayload {
    return { supplier, contacts: draft.contacts, addresses: draft.addresses, bank_accounts: draft.bankAccounts.map(({ currency: _currency, ...row }) => row), categories: draft.categories.map((category) => Number(category.id)), documents: draft.documents, item_mappings: draft.itemMappings.map(({ item: _item, uom: _uom, ...row }) => row), credit_profile: draft.creditProfile };
}
