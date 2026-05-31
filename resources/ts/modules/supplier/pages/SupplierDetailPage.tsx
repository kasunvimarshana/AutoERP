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
import { SupplierActivityTimeline } from '../components/SupplierActivityTimeline';
import { SupplierAddressesTable } from '../components/SupplierAddressesTable';
import { SupplierBankAccountsTable } from '../components/SupplierBankAccountsTable';
import { SupplierContactsTable } from '../components/SupplierContactsTable';
import { SupplierFinanceDefaultsForm } from '../components/SupplierFinanceDefaultsForm';
import { SupplierSummaryCard } from '../components/SupplierSummaryCard';
import { SupplierTaxProfileForm } from '../components/SupplierTaxProfileForm';
import { SupplierUserAccessPanel } from '../components/SupplierUserAccessPanel';
import { supplierApi } from '../services/supplierApi';
import type {
    Supplier,
    SupplierAddress,
    SupplierAuditEntry,
    SupplierBankAccount,
    SupplierContact,
    SupplierFinanceDefaults,
    SupplierStatus,
    SupplierTaxProfile,
    SupplierUserAccess,
} from '../types/supplier.types';

const tabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Contacts', value: 'contacts' },
    { label: 'Addresses', value: 'addresses' },
    { label: 'Bank Accounts', value: 'bank-accounts' },
    { label: 'Tax Profile', value: 'tax' },
    { label: 'Finance Defaults', value: 'finance' },
    { label: 'User Access', value: 'user-access' },
    { label: 'Cross-Role Links', value: 'cross-role' },
    { label: 'Audit / History', value: 'audit' },
];

type SupplierDetailState = {
    activity: SupplierAuditEntry[];
    addresses: SupplierAddress[];
    bankAccounts: SupplierBankAccount[];
    contacts: SupplierContact[];
    financeDefaults: SupplierFinanceDefaults;
    partyLinks: BusinessPartyLink[];
    supplier: Supplier;
    taxProfile: SupplierTaxProfile;
    userAccess: SupplierUserAccess[];
};

export function SupplierDetailPage() {
    const { id } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [actionError, setActionError] = useState('');
    const [detail, setDetail] = useState<SupplierDetailState | null>(null);
    const [error, setError] = useState('');
    const [isChangingStatus, setIsChangingStatus] = useState(false);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        const supplierId = id ?? '';

        Promise.all([
            supplierApi.getSupplier(supplierId),
            supplierApi.listContacts(supplierId),
            supplierApi.listAddresses(supplierId),
            supplierApi.listBankAccounts(supplierId),
            supplierApi.getTaxProfile(supplierId),
            supplierApi.getFinanceDefaults(supplierId),
            supplierApi.listUserAccess(supplierId),
            supplierApi.getSupplierActivity(supplierId),
            businessPartyLinkApi.listForSource('supplier', supplierId),
        ])
            .then(([supplier, contacts, addresses, bankAccounts, taxProfile, financeDefaults, userAccess, activity, partyLinks]) => {
                if (mounted) {
                    setDetail({
                        activity: activity.data,
                        addresses: addresses.data,
                        bankAccounts: bankAccounts.data,
                        contacts: contacts.data,
                        financeDefaults: financeDefaults.data,
                        partyLinks: partyLinks.data,
                        supplier: supplier.data,
                        taxProfile: taxProfile.data,
                        userAccess: userAccess.data,
                    });
                }
            })
            .catch((caught: unknown) => {
                if (mounted) {
                    setError(caught instanceof Error ? caught.message : 'Unable to load supplier detail.');
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
        return <EmptyState description="Loading profile, supplier records, backend preview panels, and audit..." title="Loading supplier detail" />;
    }

    if (error || !detail) {
        return <EmptyState description={error || 'Supplier was not found.'} title="Unable to load supplier" />;
    }

    const { activity, addresses, bankAccounts, contacts, financeDefaults, partyLinks, supplier, taxProfile, userAccess } = detail;

    async function changeSupplierStatus(status: SupplierStatus) {
        setIsChangingStatus(true);
        setActionError('');

        try {
            const response = await supplierApi.changeStatus(supplier.id, status);
            setDetail((current) => current ? { ...current, supplier: response.data } : current);
        } catch (caught) {
            setActionError(caught instanceof Error ? caught.message : 'Unable to change supplier status.');
        } finally {
            setIsChangingStatus(false);
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={
                    <>
                        <Link to="/suppliers">
                            <Button variant="secondary">Back</Button>
                        </Link>
                        {supplier.status !== 'active' ? (
                            <Button disabled={isChangingStatus} onClick={() => void changeSupplierStatus('active')} variant="secondary">Activate</Button>
                        ) : null}
                        {supplier.status === 'active' ? (
                            <Button disabled={isChangingStatus} onClick={() => void changeSupplierStatus('inactive')} variant="secondary">Deactivate</Button>
                        ) : null}
                        {supplier.status !== 'blocked' ? (
                            <Button disabled={isChangingStatus} onClick={() => void changeSupplierStatus('blocked')} variant="danger">Block</Button>
                        ) : (
                            <Button disabled={isChangingStatus} onClick={() => void changeSupplierStatus('active')} variant="secondary">Unblock</Button>
                        )}
                        <Link to={`/suppliers/${supplier.id}/edit`}>
                            <Button>Edit Supplier</Button>
                        </Link>
                    </>
                }
                eyebrow="Supplier"
                subtitle="Supplier detail aggregates vendor profile, contacts, addresses, bank accounts, backend-owned tax/finance previews, optional user access, cross-role links, and audit."
                title={supplier.name}
            />
            {actionError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{actionError}</div> : null}
            <SupplierSummaryCard supplier={supplier} />
            <Card className="p-5">
                <Tabs active={activeTab} items={tabs} onChange={setActiveTab} />
            </Card>

            {activeTab === 'overview' ? (
                <div className="grid gap-5 xl:grid-cols-[1fr_340px]">
                    <Card className="p-5">
                        <h2 className="text-base font-bold text-slate-950">Overview</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            {[
                                ['Supplier code', supplier.code],
                                ['Legal name', supplier.legalName || 'Not provided'],
                                ['Type', supplier.supplierType],
                                ['Category', supplier.category],
                                ['Tax / VAT', supplier.vatNumber || supplier.taxNumber || 'Not provided'],
                                ['User access', supplier.userAccessStatus === 'linked' ? 'Linked separately' : 'No user linked'],
                                ['Updated', supplier.updatedAt],
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
                            { label: 'Payables', value: 'Backend-owned value' },
                            { label: 'Source totals', value: 'Backend-owned value' },
                            { label: 'Status changes', value: 'Backend workflow controlled' },
                        ]}
                        title="Supplier backend summary"
                    />
                </div>
            ) : null}
            {activeTab === 'contacts' ? (
                <SupplierContactsTable
                    contacts={contacts}
                    supplierId={supplier.id}
                    onSaved={(contact) => setDetail((current) => current ? { ...current, contacts: [...current.contacts, contact] } : current)}
                />
            ) : null}
            {activeTab === 'addresses' ? (
                <SupplierAddressesTable
                    addresses={addresses}
                    supplierId={supplier.id}
                    onSaved={(address) => setDetail((current) => current ? { ...current, addresses: [...current.addresses, address] } : current)}
                />
            ) : null}
            {activeTab === 'bank-accounts' ? (
                <SupplierBankAccountsTable
                    accounts={bankAccounts}
                    supplierId={supplier.id}
                    onSaved={(account) => setDetail((current) => current ? { ...current, bankAccounts: [...current.bankAccounts, account] } : current)}
                />
            ) : null}
            {activeTab === 'tax' ? <SupplierTaxProfileForm profile={taxProfile} /> : null}
            {activeTab === 'finance' ? <SupplierFinanceDefaultsForm defaults={financeDefaults} /> : null}
            {activeTab === 'user-access' ? <SupplierUserAccessPanel access={userAccess} /> : null}
            {activeTab === 'cross-role' ? (
                <BusinessPartyLinksPanel
                    emptyDescription="No linked customer, provider, payer, or payee role was returned for this supplier."
                    links={partyLinks}
                />
            ) : null}
            {activeTab === 'audit' ? <SupplierActivityTimeline entries={activity} /> : null}
        </div>
    );
}
