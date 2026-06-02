import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { BusinessPartyLinksPanel } from '../../../shared/components/business/BusinessPartyLinksPanel';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { businessPartyLinkApi } from '../../../services/api/businessPartyLinkApi';
import type { BusinessPartyLink } from '../../../shared/types/businessParty.types';
import { CustomerAddressesTable } from '../components/CustomerAddressesTable';
import { CustomerContactsTable } from '../components/CustomerContactsTable';
import { CustomerCreditProfilePanel } from '../components/CustomerCreditProfilePanel';
import { CustomerFinanceDefaultsForm } from '../components/CustomerFinanceDefaultsForm';
import { CustomerSummaryCard } from '../components/CustomerSummaryCard';
import { CustomerTaxProfileForm } from '../components/CustomerTaxProfileForm';
import { CustomerUserAccessPanel } from '../components/CustomerUserAccessPanel';
import { CustomerVehiclesTable } from '../components/CustomerVehiclesTable';
import { customerApi } from '../services/customerApi';
import type {
    Customer,
    CustomerAddress,
    CustomerContact,
    CustomerCreditProfile,
    CustomerFinanceDefaults,
    CustomerTaxProfile,
    CustomerUserAccess,
    CustomerVehicle,
} from '../types/customer.types';

const tabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Contacts', value: 'contacts' },
    { label: 'Addresses', value: 'addresses' },
    { label: 'Vehicles', value: 'vehicles' },
    { label: 'Tax Profile', value: 'tax' },
    { label: 'Credit Profile', value: 'credit' },
    { label: 'Finance Defaults', value: 'finance' },
    { label: 'User Access', value: 'user-access' },
    { label: 'Cross-Role Links', value: 'cross-role' },
];

type CustomerDetailState = {
    addresses: CustomerAddress[];
    contacts: CustomerContact[];
    creditProfile: CustomerCreditProfile;
    customer: Customer;
    financeDefaults: CustomerFinanceDefaults;
    partyLinks: BusinessPartyLink[];
    taxProfile: CustomerTaxProfile;
    userAccess: CustomerUserAccess[];
    vehicles: CustomerVehicle[];
};

function emptyTaxProfile(customerId: string): CustomerTaxProfile {
    return { customerId, taxGroup: 'Not configured', taxStatus: 'unregistered' };
}

function emptyCreditProfile(customerId: string): CustomerCreditProfile {
    return {
        agingSummary: 'Open the tab to load backend credit details.',
        backendPreviewStatus: 'Not loaded',
        creditLimit: 'Not loaded',
        creditStatus: 'Not loaded',
        customerId,
        outstandingBalance: 'Not loaded',
        paymentTerms: 'Not loaded',
    };
}

function emptyFinanceDefaults(customerId: string): CustomerFinanceDefaults {
    return {
        arAccount: 'Not loaded',
        costCenter: 'Not loaded',
        currency: 'Not loaded',
        customerId,
        paymentTerm: 'Not loaded',
        revenueAccount: 'Not loaded',
    };
}

export function CustomerDetailPage() {
    const { id } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [actionError, setActionError] = useState('');
    const [detail, setDetail] = useState<CustomerDetailState | null>(null);
    const [error, setError] = useState('');
    const [isChangingStatus, setIsChangingStatus] = useState(false);
    const [isLoading, setIsLoading] = useState(true);
    const [loadedTabs, setLoadedTabs] = useState<Set<string>>(new Set(['overview']));
    const [tabLoading, setTabLoading] = useState('');

    useEffect(() => {
        let mounted = true;
        const customerId = id ?? '';

        customerApi.getCustomer(customerId)
            .then((customer) => {
                if (mounted) {
                    setDetail({
                        addresses: [],
                        contacts: [],
                        creditProfile: emptyCreditProfile(customerId),
                        customer: customer.data,
                        financeDefaults: emptyFinanceDefaults(customerId),
                        partyLinks: [],
                        taxProfile: emptyTaxProfile(customerId),
                        userAccess: [],
                        vehicles: [],
                    });
                }
            })
            .catch((caught: unknown) => {
                if (mounted) {
                    setError(caught instanceof Error ? caught.message : 'Unable to load customer detail.');
                }
            })
            .finally(() => {
                if (mounted) {
                    setIsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, [id]);

    useEffect(() => {
        let mounted = true;
        const customerId = id ?? '';

        if (!detail || loadedTabs.has(activeTab)) {
            return () => {
                mounted = false;
            };
        }

        async function loadTab() {
            setTabLoading(activeTab);
            try {
                if (activeTab === 'contacts') {
                    const response = await customerApi.listContacts(customerId);
                    if (mounted) setDetail((current) => current ? { ...current, contacts: response.data } : current);
                }
                if (activeTab === 'addresses') {
                    const response = await customerApi.listAddresses(customerId);
                    if (mounted) setDetail((current) => current ? { ...current, addresses: response.data } : current);
                }
                if (activeTab === 'vehicles') {
                    const response = await customerApi.listVehicles(customerId);
                    if (mounted) setDetail((current) => current ? { ...current, vehicles: response.data } : current);
                }
                if (activeTab === 'tax') {
                    const response = await customerApi.getTaxProfile(customerId);
                    if (mounted) setDetail((current) => current ? { ...current, taxProfile: response.data } : current);
                }
                if (activeTab === 'credit') {
                    const response = await customerApi.getCreditProfile(customerId);
                    if (mounted) setDetail((current) => current ? { ...current, creditProfile: response.data } : current);
                }
                if (activeTab === 'finance') {
                    const response = await customerApi.getFinanceDefaults(customerId);
                    if (mounted) setDetail((current) => current ? { ...current, financeDefaults: response.data } : current);
                }
                if (activeTab === 'user-access') {
                    const response = await customerApi.listUserAccess(customerId);
                    if (mounted) setDetail((current) => current ? { ...current, userAccess: response.data } : current);
                }
                if (activeTab === 'cross-role') {
                    const response = await businessPartyLinkApi.listForSource('customer', customerId);
                    if (mounted) setDetail((current) => current ? { ...current, partyLinks: response.data } : current);
                }

                if (mounted) {
                    setLoadedTabs((current) => new Set([...current, activeTab]));
                }
            } catch (caught) {
                if (mounted) {
                    setActionError(caught instanceof Error ? caught.message : 'Unable to load this customer section.');
                }
            } finally {
                if (mounted) {
                    setTabLoading('');
                }
            }
        }

        void loadTab();

        return () => {
            mounted = false;
        };
    }, [activeTab, detail, id, loadedTabs]);

    if (isLoading) {
        return <EmptyState description="Loading profile, related records, and backend preview panels..." title="Loading customer detail" />;
    }

    if (error || !detail) {
        return <EmptyState description={error || 'Customer was not found.'} title="Unable to load customer" />;
    }

    const { addresses, contacts, creditProfile, customer, financeDefaults, partyLinks, taxProfile, userAccess, vehicles } = detail;

    async function changeCustomerStatus(status: Customer['status']) {
        setIsChangingStatus(true);
        setActionError('');

        try {
            const response = await customerApi.changeStatus(customer.id, status);
            setDetail((current) => current ? { ...current, customer: response.data } : current);
        } catch (caught) {
            setActionError(caught instanceof Error ? caught.message : 'Unable to change customer status.');
        } finally {
            setIsChangingStatus(false);
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={
                    <>
                        <Link to="/customers">
                            <Button variant="secondary">Back</Button>
                        </Link>
                        {customer.status !== 'active' ? (
                            <Button disabled={isChangingStatus} onClick={() => void changeCustomerStatus('active')} variant="secondary">Activate</Button>
                        ) : null}
                        {customer.status === 'active' ? (
                            <Button disabled={isChangingStatus} onClick={() => void changeCustomerStatus('inactive')} variant="secondary">Deactivate</Button>
                        ) : null}
                        {customer.status !== 'blocked' ? (
                            <Button disabled={isChangingStatus} onClick={() => void changeCustomerStatus('blocked')} variant="danger">Block</Button>
                        ) : (
                            <Button disabled={isChangingStatus} onClick={() => void changeCustomerStatus('active')} variant="secondary">Unblock</Button>
                        )}
                        <Link to={`/customers/${customer.id}/edit`}>
                            <Button>Edit Customer</Button>
                        </Link>
                    </>
                }
                eyebrow="Customer"
                subtitle="Customer detail aggregates profile, contacts, addresses, vehicles, backend-owned credit previews, finance defaults, optional user access, and cross-role links."
                title={customer.name}
            />
            {actionError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{actionError}</div> : null}
            <CustomerSummaryCard customer={customer} />
            <Card className="p-5">
                <Tabs active={activeTab} items={tabs} onChange={setActiveTab} />
            </Card>
            {tabLoading ? <EmptyState description="Loading this section from the backend..." title="Loading section" /> : null}

            {activeTab === 'overview' ? (
                <div className="grid gap-5 xl:grid-cols-[1fr_340px]">
                    <Card className="p-5">
                        <h2 className="text-base font-bold text-slate-950">Overview</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            {[
                                ['Customer code', customer.code],
                                ['Industry', customer.industry || 'Not provided'],
                                ['Primary contact', customer.contactPerson || 'Not provided'],
                                ['Tax number', customer.taxNumber || 'Not provided'],
                                ['Created', customer.createdAt],
                                ['User access', customer.userAccessStatus === 'linked' ? 'Linked separately' : 'No user linked'],
                            ].map(([label, value]) => (
                                <div className="rounded-lg border border-slate-200 bg-slate-50 p-3" key={label}>
                                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</p>
                                    <p className="mt-1 text-sm font-semibold text-slate-800">{value}</p>
                                </div>
                            ))}
                        </div>
                    </Card>
                    <PreviewPanel
                        rows={[
                            { label: 'Credit', value: 'Backend preview only' },
                            { label: 'Outstanding', value: 'Backend-owned value' },
                            { label: 'Status changes', value: 'Backend workflow controlled' },
                        ]}
                        title="Customer backend summary"
                    />
                </div>
            ) : null}
            {activeTab === 'contacts' ? (
                <CustomerContactsTable
                    contacts={contacts}
                    customerId={customer.id}
                    onSaved={(contact) => setDetail((current) => current ? { ...current, contacts: [...current.contacts, contact] } : current)}
                />
            ) : null}
            {activeTab === 'addresses' ? (
                <CustomerAddressesTable
                    addresses={addresses}
                    customerId={customer.id}
                    onSaved={(address) => setDetail((current) => current ? { ...current, addresses: [...current.addresses, address] } : current)}
                />
            ) : null}
            {activeTab === 'vehicles' ? <CustomerVehiclesTable vehicles={vehicles} /> : null}
            {activeTab === 'tax' ? <CustomerTaxProfileForm profile={taxProfile} /> : null}
            {activeTab === 'credit' ? <CustomerCreditProfilePanel profile={creditProfile} /> : null}
            {activeTab === 'finance' ? <CustomerFinanceDefaultsForm defaults={financeDefaults} /> : null}
            {activeTab === 'user-access' ? <CustomerUserAccessPanel access={userAccess} /> : null}
            {activeTab === 'cross-role' ? (
                <BusinessPartyLinksPanel
                    emptyDescription="No linked supplier, provider, payer, or payee role was returned for this customer."
                    links={partyLinks}
                />
            ) : null}
        </div>
    );
}
