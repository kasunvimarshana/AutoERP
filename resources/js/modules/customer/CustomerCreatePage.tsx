import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormActions } from '@/shared/components/FormActions';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { TabPanel, Tabs } from '@/shared/components/Tabs';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import type { NamedResource } from '@/shared/types/common';
import { notifyError } from '@/shared/notifications/appToast';
import { closePendingWhatsAppWindow, navigateToWhatsApp, openPendingWhatsAppWindow } from '@/shared/utils/whatsAppNavigation';
import { selectWhatsAppVerificationPhone } from '@/shared/utils/whatsAppVerificationTarget';
import { startCustomerWhatsAppVerification } from './customerApi';
import { createCustomer, createCustomerWithRelations } from './customerApi';
import { defaultCustomerPayload, loadCustomerCreationDefaults } from './customerCreateDefaults';
import type { CustomerPayload, CustomerWithRelationsPayload } from './customerTypes';
import { CustomerForm } from './components/CustomerForm';
import {
    emptyCustomerOneShotDraft,
    CustomerOneShotBuilder,
    type CustomerOneShotDraft,
} from './components/CustomerOneShotBuilder';

type Tab = 'basic' | 'contacts' | 'addresses' | 'bank_accounts' | 'credit_profile' | 'review';
type RelatedTab = Exclude<Tab, 'basic'>;

const tabs = [
    ['basic', 'Basic'],
    ['contacts', 'Contacts'],
    ['addresses', 'Addresses'],
    ['bank_accounts', 'Bank Accounts'],
    ['credit_profile', 'Credit Profile'],
    ['review', 'Review'],
].map(([id, label]) => ({ id: id as Tab, label }));
const relatedTabs = tabs.filter((tab) => tab.id !== 'basic') as { id: RelatedTab; label: string }[];

export default function CustomerCreatePage() {
    const auth = useAuth();
    const canVerifyWhatsApp = hasPermission(auth, 'customers.update');
    const navigate = useNavigate();
    const [customer, setCustomer] = useState<CustomerPayload>(defaultCustomerPayload);
    const [initialCustomer, setInitialCustomer] = useState<CustomerPayload | null>(null);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [initialCurrencyId, setInitialCurrencyId] = useState<NamedResource['id'] | null>(null);
    const [includeRelated, setIncludeRelated] = useState(false);
    const [activeTab, setActiveTab] = useState<Tab>('basic');
    const [draft, setDraft] = useState<CustomerOneShotDraft>(emptyCustomerOneShotDraft);
    const [submitting, setSubmitting] = useState(false);
    const [verifyAfterSave, setVerifyAfterSave] = useState(false);
    const [loadingDefaults, setLoadingDefaults] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);
    const dirty = initialCustomer !== null && (JSON.stringify(customer) !== JSON.stringify(initialCustomer)
        || JSON.stringify(draft) !== JSON.stringify(emptyCustomerOneShotDraft)
        || (currency?.id ?? null) !== initialCurrencyId);
    const confirmDiscard = useUnsavedChanges(dirty && !submitting);
    const verificationPhone = selectWhatsAppVerificationPhone(customer.mobile, includeRelated ? draft.contacts : []);
    const shouldVerifyAfterSave = verifyAfterSave && canVerifyWhatsApp && verificationPhone !== null;

    useEffect(() => {
        const controller = new AbortController();

        void loadCustomerCreationDefaults(controller.signal)
            .then(({ code, currency: defaultCurrency }) => {
                const defaults = {
                    ...defaultCustomerPayload(),
                    code,
                    default_currency_id: Number(defaultCurrency.id),
                };
                setCustomer(defaults);
                setInitialCustomer(defaults);
                setCurrency(defaultCurrency);
                setInitialCurrencyId(defaultCurrency.id);
                setLoadingDefaults(false);
            })
            .catch((requestError) => {
                if (controller.signal.aborted) return;
                setError(toApiError(requestError));
                setLoadingDefaults(false);
            });

        return () => controller.abort();
    }, []);

    async function save() {
        setSubmitting(true);
        const pendingWindow = shouldVerifyAfterSave ? openPendingWhatsAppWindow() : null;
        setError(null);
        try {
            const saved = includeRelated
                ? await createCustomerWithRelations(toPayload(customer, draft))
                : await createCustomer(customer);

            if (shouldVerifyAfterSave) {
                try {
                    const challenge = await startCustomerWhatsAppVerification(saved.id, crypto.randomUUID());
                    if (!navigateToWhatsApp(challenge.whatsapp_url, pendingWindow)) {
                        throw new Error('The server returned an invalid WhatsApp link.');
                    }
                    if (pendingWindow === null) {
                        return;
                    }
                } catch (verificationError) {
                    closePendingWhatsAppWindow(pendingWindow);
                    notifyError(toApiError(verificationError), 'Customer created; verification not started');
                }
            }

            navigate(`/customers/${saved.id}`);
        } catch (requestError) {
            closePendingWhatsAppWindow(pendingWindow);
            setError(toApiError(requestError));
            setSubmitting(false);
        }
    }

    return (
        <>
            <ContentHeader
                title="New customer"
                description="Start with the customer profile. Add related records now only when they are available."
            />
            <ErrorAlert error={error} title="Customer could not be saved" />
            {loadingDefaults && <LoadingState label="Preparing customer defaults..." />}
            {!loadingDefaults && initialCustomer !== null && (
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
                            <span className="block text-sm text-slate-500">Optional contacts, addresses, bank accounts, and credit settings.</span>
                        </span>
                    </label>
                </Panel>

                {includeRelated && (
                    <Panel className="p-0">
                        <Tabs id="customer-create" tabs={tabs} active={activeTab} onChange={setActiveTab} />
                    </Panel>
                )}

                {includeRelated ? (
                    <>
                        <TabPanel tabsId="customer-create" tabId="basic" active={activeTab}>
                            <CustomerForm value={customer} onChange={setCustomer} currency={currency} onCurrencyChange={setCurrency} error={error} creating />
                        </TabPanel>
                        {relatedTabs.map((tab) => (
                            <TabPanel key={tab.id} tabsId="customer-create" tabId={tab.id} active={activeTab}>
                                <Panel>
                                    <CustomerOneShotBuilder section={tab.id} value={draft} onChange={setDraft} />
                                </Panel>
                            </TabPanel>
                        ))}
                    </>
                ) : (
                    <CustomerForm value={customer} onChange={setCustomer} currency={currency} onCurrencyChange={setCurrency} error={error} creating />
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
                        {shouldVerifyAfterSave ? 'Create & verify WhatsApp' : (includeRelated ? 'Create customer and related records' : 'Create customer')}
                    </Button>
                </FormActions>
            </form>
            )}
        </>
    );
}

function toPayload(customer: CustomerPayload, draft: CustomerOneShotDraft): CustomerWithRelationsPayload {
    return {
        customer,
        contacts: draft.contacts,
        addresses: draft.addresses,
        bank_accounts: draft.bankAccounts.map(({ currency: _currency, ...row }) => row),
        credit_profile: draft.creditProfile,
    };
}
