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
import { createSupplier, createSupplierWithRelations } from './supplierApi';
import type { SupplierPayload, SupplierWithRelationsPayload } from './supplierTypes';
import { SupplierForm } from './components/SupplierForm';
import {
    emptySupplierOneShotDraft,
    SupplierOneShotBuilder,
    type SupplierOneShotDraft,
} from './components/SupplierOneShotBuilder';

type Tab = 'basic' | 'contacts' | 'addresses' | 'bank_accounts' | 'categories' | 'documents' | 'item_mappings' | 'credit_profile' | 'review';
type RelatedTab = Exclude<Tab, 'basic'>;

const tabs = [
    ['basic', 'Basic'],
    ['contacts', 'Contacts'],
    ['addresses', 'Addresses'],
    ['bank_accounts', 'Bank Accounts'],
    ['categories', 'Categories'],
    ['documents', 'Documents'],
    ['item_mappings', 'Item Mappings'],
    ['credit_profile', 'Credit Profile'],
    ['review', 'Review'],
].map(([id, label]) => ({ id: id as Tab, label }));
const relatedTabs = tabs.filter((tab) => tab.id !== 'basic') as { id: RelatedTab; label: string }[];

const initialSupplier: SupplierPayload = {
    supplier_number: null,
    code: '',
    name: '',
    supplier_type: 'company',
    status: 'pending_approval',
    default_currency_id: null,
    credit_limit: '0.000000',
    is_credit_allowed: true,
    is_advance_allowed: true,
};

export default function SupplierCreatePage() {
    const navigate = useNavigate();
    const [supplier, setSupplier] = useState(initialSupplier);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [includeRelated, setIncludeRelated] = useState(false);
    const [activeTab, setActiveTab] = useState<Tab>('basic');
    const [draft, setDraft] = useState<SupplierOneShotDraft>(emptySupplierOneShotDraft);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const dirty = JSON.stringify(supplier) !== JSON.stringify(initialSupplier)
        || JSON.stringify(draft) !== JSON.stringify(emptySupplierOneShotDraft)
        || currency !== null;
    const confirmDiscard = useUnsavedChanges(dirty && !submitting);

    async function save() {
        setSubmitting(true);
        setError(null);
        try {
            const saved = includeRelated
                ? await createSupplierWithRelations(toPayload(supplier, draft))
                : await createSupplier(supplier);
            navigate(`/suppliers/${saved.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
            setSubmitting(false);
        }
    }

    return (
        <>
            <ContentHeader
                title="New supplier"
                description="Start with the supplier profile. Add related records now only when they are available."
            />
            <ErrorAlert error={error} title="Supplier could not be saved" />
            <form className="mx-auto max-w-6xl space-y-5" onSubmit={(event) => {
                event.preventDefault();
                void save();
            }}>
                <Panel>
                    <label className="flex min-h-11 cursor-pointer items-center gap-3">
                        <input
                            type="checkbox"
                            checked={includeRelated}
                            onChange={(event) => {
                                setIncludeRelated(event.target.checked);
                                setActiveTab('basic');
                            }}
                        />
                        <span>
                            <span className="block text-sm font-semibold text-slate-900">Add related records before saving</span>
                            <span className="block text-sm text-slate-500">Optional contacts, addresses, bank accounts, documents, item mappings, and credit settings.</span>
                        </span>
                    </label>
                </Panel>

                {includeRelated && (
                    <Panel className="p-0">
                        <Tabs id="supplier-create" tabs={tabs} active={activeTab} onChange={setActiveTab} />
                    </Panel>
                )}

                {includeRelated ? (
                    <>
                        <TabPanel tabsId="supplier-create" tabId="basic" active={activeTab}>
                            <SupplierForm value={supplier} onChange={setSupplier} currency={currency} onCurrencyChange={setCurrency} error={error} creating />
                        </TabPanel>
                        {relatedTabs.map((tab) => (
                            <TabPanel key={tab.id} tabsId="supplier-create" tabId={tab.id} active={activeTab}>
                                <Panel>
                                    <SupplierOneShotBuilder section={tab.id} value={draft} onChange={setDraft} />
                                </Panel>
                            </TabPanel>
                        ))}
                    </>
                ) : (
                    <SupplierForm value={supplier} onChange={setSupplier} currency={currency} onCurrencyChange={setCurrency} error={error} creating />
                )}

                <FormActions>
                    <Button type="button" variant="secondary" onClick={() => confirmDiscard() && navigate(-1)}>Cancel</Button>
                    <Button type="submit" loading={submitting}>
                        {includeRelated ? 'Create supplier and related records' : 'Create supplier'}
                    </Button>
                </FormActions>
            </form>
        </>
    );
}

function toPayload(supplier: SupplierPayload, draft: SupplierOneShotDraft): SupplierWithRelationsPayload {
    return {
        supplier,
        contacts: draft.contacts,
        addresses: draft.addresses,
        bank_accounts: draft.bankAccounts.map(({ currency: _currency, ...row }) => row),
        categories: draft.categories.map((category) => Number(category.id)),
        documents: draft.documents,
        item_mappings: draft.itemMappings.map(({ item: _item, uom: _uom, ...row }) => row),
        credit_profile: draft.creditProfile,
    };
}
