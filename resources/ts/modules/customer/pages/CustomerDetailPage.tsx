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
import { CustomerActivityTimeline } from '../components/CustomerActivityTimeline';
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
    { label: 'Activity / Audit', value: 'audit' },
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

export function CustomerDetailPage() {
    const { id } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [detail, setDetail] = useState<CustomerDetailState | null>(null);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        const customerId = id ?? '';

        Promise.all([
            customerApi.getCustomer(customerId),
            customerApi.listContacts(customerId),
            customerApi.listAddresses(customerId),
            customerApi.listVehicles(customerId),
            customerApi.getTaxProfile(customerId),
            customerApi.getCreditProfile(customerId),
            customerApi.getFinanceDefaults(customerId),
            customerApi.listUserAccess(customerId),
            businessPartyLinkApi.listForSource('customer', customerId),
        ])
            .then(([customer, contacts, addresses, vehicles, taxProfile, creditProfile, financeDefaults, userAccess, partyLinks]) => {
                if (mounted) {
                    setDetail({
                        addresses: addresses.data,
                        contacts: contacts.data,
                        creditProfile: creditProfile.data,
                        customer: customer.data,
                        financeDefaults: financeDefaults.data,
                        partyLinks: partyLinks.data,
                        taxProfile: taxProfile.data,
                        userAccess: userAccess.data,
                        vehicles: vehicles.data,
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

    if (isLoading) {
        return <EmptyState description="Loading profile, related records, and backend preview panels..." title="Loading customer detail" />;
    }

    if (error || !detail) {
        return <EmptyState description={error || 'Customer was not found.'} title="Unable to load customer" />;
    }

    const { addresses, contacts, creditProfile, customer, financeDefaults, partyLinks, taxProfile, userAccess, vehicles } = detail;

    return (
        <div className="space-y-6">
            <PageHeader
                actions={
                    <>
                        <Link to="/customers">
                            <Button variant="secondary">Back</Button>
                        </Link>
                        <Link to={`/customers/${customer.id}/edit`}>
                            <Button>Edit Customer</Button>
                        </Link>
                    </>
                }
                eyebrow="Customer"
                subtitle="Customer detail aggregates profile, contacts, addresses, vehicles, backend-owned credit previews, finance defaults, optional user access, and audit."
                title={customer.name}
            />
            <CustomerSummaryCard customer={customer} />
            <Card className="p-5">
                <Tabs active={activeTab} items={tabs} onChange={setActiveTab} />
            </Card>

            {activeTab === 'overview' ? (
                <div className="grid gap-5 xl:grid-cols-[1fr_340px]">
                    <Card className="p-5">
                        <h2 className="text-base font-bold text-slate-950">Overview</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            {[
                                ['Customer code', customer.code],
                                ['Industry', customer.industry],
                                ['Primary contact', customer.contactPerson],
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
            {activeTab === 'contacts' ? <CustomerContactsTable contacts={contacts} /> : null}
            {activeTab === 'addresses' ? <CustomerAddressesTable addresses={addresses} /> : null}
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
            {activeTab === 'audit' ? <CustomerActivityTimeline /> : null}
        </div>
    );
}
