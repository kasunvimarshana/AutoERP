import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormActions } from '@/shared/components/FormActions';
import { Panel } from '@/shared/components/Panel';
import { TabPanel, Tabs } from '@/shared/components/Tabs';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import type { NamedResource } from '@/shared/types/common';
import { notifyError } from '@/shared/notifications/appToast';
import { closePendingWhatsAppWindow, navigateToWhatsApp, openPendingWhatsAppWindow } from '@/shared/utils/whatsAppNavigation';
import { selectWhatsAppVerificationPhone } from '@/shared/utils/whatsAppVerificationTarget';
import { startSupplierWhatsAppVerification } from './supplierApi';
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
    const auth = useAuth();
    const canVerifyWhatsApp = hasPermission(auth, 'suppliers.update');
    const [supplier, setSupplier] = useState(initialSupplier);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [includeRelated, setIncludeRelated] = useState(false);
    const [activeTab, setActiveTab] = useState<Tab>('basic');
    const [draft, setDraft] = useState<SupplierOneShotDraft>(emptySupplierOneShotDraft);
    const [submitting, setSubmitting] = useState(false);
    const [verifyAfterSave, setVerifyAfterSave] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const dirty = JSON.stringify(supplier) !== JSON.stringify(initialSupplier)
        || JSON.stringify(draft) !== JSON.stringify(emptySupplierOneShotDraft)
        || currency !== null;
    const confirmDiscard = useUnsavedChanges(dirty && !submitting);
    const verificationPhone = selectWhatsAppVerificationPhone(supplier.mobile, includeRelated ? draft.contacts : []);
    const shouldVerifyAfterSave = verifyAfterSave && canVerifyWhatsApp && verificationPhone !== null;

    async function save() {
        setSubmitting(true);
        setError(null);
        const pendingWindow = shouldVerifyAfterSave ? openPendingWhatsAppWindow() : null;
        try {
            const saved = includeRelated
                ? await createSupplierWithRelations(toPayload(supplier, draft))
                : await createSupplier(supplier);
            if (!shouldVerifyAfterSave) {
                navigate(`/suppliers/${saved.id}`);
                return;
            }

            if (shouldVerifyAfterSave) {
                try {
                    const challenge = await startSupplierWhatsAppVerification(saved.id, crypto.randomUUID());
                    if (!navigateToWhatsApp(challenge.whatsapp_url, pendingWindow)) {
                        throw new Error('The server returned an invalid WhatsApp link.');
                    }
                    if (pendingWindow === null) {
                        return;
                    }
                } catch (verificationError) {
                    closePendingWhatsAppWindow(pendingWindow);
                    notifyError(toApiError(verificationError), 'Supplier created; verification not started');
                }
            }


            navigate(`/suppliers/${saved.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
            closePendingWhatsAppWindow(pendingWindow);
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
                    <label className="mr-auto flex items-start gap-2 text-sm text-slate-700">
                        <input
                            className="mt-1"
                            type="checkbox"
                            checked={verifyAfterSave}
                            disabled={!canVerifyWhatsApp || verificationPhone === null || submitting}
                            onChange={(event) => setVerifyAfterSave(event.target.checked)}
                        />
                        <span>Verify WhatsApp after saving{verificationPhone ? <span className="block text-xs text-slate-500">Target: {verificationPhone}</span> : <span className="block text-xs text-amber-700">Add a WhatsApp number first.</span>}</span>
                    </label>
                    <Button type="button" variant="secondary" onClick={() => confirmDiscard() && navigate(-1)}>Cancel</Button>
                    <Button type="submit" loading={submitting}>
                        {shouldVerifyAfterSave ? 'Create & verify WhatsApp' : (includeRelated ? 'Create supplier and related records' : 'Create supplier')}
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
