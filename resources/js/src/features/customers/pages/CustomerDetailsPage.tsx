import { useMemo } from 'react';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Button } from '../../../components/ui/Button';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { SectionCard } from '../../../components/forms/SectionCard';
import { DataTable, StatusBadge, type DataTableColumn } from '../../../components/tables';
import { useTenant } from '../../auth/context/TenantContext';
import { useUser } from '../../access/hooks';
import { useCustomer, useCustomerAddresses, useCustomerContacts, useCustomerPriceLists } from '../hooks';
import { parsePositiveInteger, formatDate, getStatusTone } from '../../shared/utils';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import type { CustomerAddress, CustomerContact, CustomerPriceListAssignment } from '../types';

const detailTabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'addresses', label: 'Addresses' },
    { id: 'contacts', label: 'Contacts' },
    { id: 'pricing', label: 'Pricing' },
    { id: 'sales', label: 'Sales' },
    { id: 'ar', label: 'AR' },
] as const;

type DetailTabId = (typeof detailTabs)[number]['id'];

export function CustomerDetailsPage() {
    const { tenantId } = useTenant();
    const { customerId: customerIdParam } = useParams();
    const customerId = parsePositiveInteger(customerIdParam ?? null, 0);
    const [searchParams, setSearchParams] = useSearchParams();
    const activeTab = (searchParams.get('tab') as DetailTabId | null) ?? 'overview';

    const customerQuery = useCustomer(customerId, customerId > 0);
    const userQuery = useUser(customerQuery.data?.user_id ?? 0, 'permissions', Boolean(customerQuery.data?.user_id));
    const addressesQuery = useCustomerAddresses(customerId, tenantId, customerId > 0 && activeTab === 'addresses');
    const contactsQuery = useCustomerContacts(customerId, tenantId, customerId > 0 && activeTab === 'contacts');
    const pricingQuery = useCustomerPriceLists(customerId, tenantId, customerId > 0 && activeTab === 'pricing');

    const addressColumns: DataTableColumn<CustomerAddress>[] = useMemo(
        () => [
            {
                key: 'label',
                header: 'Address',
                render: (address) => (
                    <div>
                        <p className="font-medium text-stone-950">{address.label ?? `${address.type} address`}</p>
                        <p className="mt-1 text-xs text-stone-500">{address.address_line1}</p>
                    </div>
                ),
            },
            { key: 'type', header: 'Type', render: (address) => <StatusBadge>{address.type}</StatusBadge> },
            { key: 'city', header: 'City', render: (address) => <span className="text-sm text-stone-700">{address.city}</span> },
            { key: 'default', header: 'Default', render: (address) => <StatusBadge tone={address.is_default ? 'success' : 'default'}>{address.is_default ? 'Default' : 'Secondary'}</StatusBadge> },
        ],
        [],
    );

    const contactColumns: DataTableColumn<CustomerContact>[] = useMemo(
        () => [
            {
                key: 'name',
                header: 'Contact',
                render: (contact) => (
                    <div>
                        <p className="font-medium text-stone-950">{contact.name}</p>
                        <p className="mt-1 text-xs text-stone-500">{contact.role ?? 'No role assigned'}</p>
                    </div>
                ),
            },
            { key: 'email', header: 'Email', render: (contact) => <span className="text-sm text-stone-700">{contact.email ?? '-'}</span> },
            { key: 'phone', header: 'Phone', render: (contact) => <span className="text-sm text-stone-700">{contact.phone ?? '-'}</span> },
            { key: 'primary', header: 'Primary', render: (contact) => <StatusBadge tone={contact.is_primary ? 'success' : 'default'}>{contact.is_primary ? 'Primary' : 'Secondary'}</StatusBadge> },
        ],
        [],
    );

    const pricingColumns: DataTableColumn<CustomerPriceListAssignment>[] = useMemo(
        () => [
            { key: 'price_list_id', header: 'Price List ID', render: (assignment) => <span className="text-sm text-stone-700">#{assignment.price_list_id}</span> },
            { key: 'priority', header: 'Priority', render: (assignment) => <span className="text-sm text-stone-700">{assignment.priority}</span> },
            { key: 'updated_at', header: 'Updated', render: (assignment) => formatDate(assignment.updated_at) },
        ],
        [],
    );

    function renderTabContent() {
        switch (activeTab) {
            case 'overview':
                return (
                    <div className="grid gap-4 xl:grid-cols-2">
                        <SectionCard description="Core customer identity, ownership, and commercial state from the backend customer resource." title="Account overview">
                            <dl className="grid gap-4 text-sm text-stone-700 md:grid-cols-2">
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Customer Code</dt>
                                    <dd className="mt-1 font-medium text-stone-950">{customerQuery.data?.customer_code ?? '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Type</dt>
                                    <dd className="mt-1 font-medium text-stone-950">{customerQuery.data?.type ?? '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Status</dt>
                                    <dd className="mt-1">
                                        <StatusBadge tone={getStatusTone(customerQuery.data?.status)}>{customerQuery.data?.status ?? '-'}</StatusBadge>
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Payment Terms</dt>
                                    <dd className="mt-1 font-medium text-stone-950">{customerQuery.data?.payment_terms_days ? `${customerQuery.data.payment_terms_days} days` : '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Credit Limit</dt>
                                    <dd className="mt-1 font-medium text-stone-950">{customerQuery.data?.credit_limit ?? '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">AR Account ID</dt>
                                    <dd className="mt-1 font-medium text-stone-950">{customerQuery.data?.ar_account_id ?? '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Tax Number</dt>
                                    <dd className="mt-1 font-medium text-stone-950">{customerQuery.data?.tax_number ?? '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Registration</dt>
                                    <dd className="mt-1 font-medium text-stone-950">{customerQuery.data?.registration_number ?? '-'}</dd>
                                </div>
                            </dl>
                        </SectionCard>

                        <SectionCard description="Linked portal-user information is loaded from the Users module when a customer has an associated user record." title="Portal user">
                            {userQuery.isPending ? (
                                <LoadingState lines={4} />
                            ) : userQuery.isError ? (
                                isForbiddenError(userQuery.error) ? (
                                    <ProtectedErrorState description={userQuery.error.message} />
                                ) : (
                                    <ErrorState description={userQuery.error.message} title="Unable to load linked user" />
                                )
                            ) : userQuery.data ? (
                                <dl className="grid gap-4 text-sm text-stone-700 md:grid-cols-2">
                                    <div>
                                        <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Full Name</dt>
                                        <dd className="mt-1 font-medium text-stone-950">{userQuery.data.full_name ?? '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Email</dt>
                                        <dd className="mt-1 font-medium text-stone-950">{userQuery.data.email ?? '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Phone</dt>
                                        <dd className="mt-1 font-medium text-stone-950">{userQuery.data.phone ?? '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Roles</dt>
                                        <dd className="mt-1 flex flex-wrap gap-2">
                                            {userQuery.data.roles.length > 0 ? (
                                                userQuery.data.roles.map((role) => <StatusBadge key={role.id}>{role.name}</StatusBadge>)
                                            ) : (
                                                <span className="font-medium text-stone-950">No roles assigned</span>
                                            )}
                                        </dd>
                                    </div>
                                </dl>
                            ) : (
                                <EmptyState className="border-0 bg-transparent p-0 shadow-none" description="This customer does not have a linked portal user yet." title="No linked user" />
                            )}
                        </SectionCard>
                    </div>
                );
            case 'addresses':
                if (addressesQuery.isPending) {
                    return <LoadingState lines={6} />;
                }
                if (addressesQuery.isError) {
                    return isForbiddenError(addressesQuery.error) ? (
                        <ProtectedErrorState description={addressesQuery.error.message} />
                    ) : (
                        <ErrorState description={addressesQuery.error.message} title="Unable to load customer addresses" />
                    );
                }
                return (
                    <ContentCard className="p-0">
                        <DataTable
                            columns={addressColumns}
                            emptyState={<EmptyState className="m-6" description="No addresses are recorded for this customer yet." title="No addresses found" />}
                            getRowKey={(address) => address.id}
                            rows={addressesQuery.data.items}
                        />
                    </ContentCard>
                );
            case 'contacts':
                if (contactsQuery.isPending) {
                    return <LoadingState lines={6} />;
                }
                if (contactsQuery.isError) {
                    return isForbiddenError(contactsQuery.error) ? (
                        <ProtectedErrorState description={contactsQuery.error.message} />
                    ) : (
                        <ErrorState description={contactsQuery.error.message} title="Unable to load customer contacts" />
                    );
                }
                return (
                    <ContentCard className="p-0">
                        <DataTable
                            columns={contactColumns}
                            emptyState={<EmptyState className="m-6" description="No contacts are recorded for this customer yet." title="No contacts found" />}
                            getRowKey={(contact) => contact.id}
                            rows={contactsQuery.data.items}
                        />
                    </ContentCard>
                );
            case 'pricing':
                if (pricingQuery.isPending) {
                    return <LoadingState lines={6} />;
                }
                if (pricingQuery.isError) {
                    return isForbiddenError(pricingQuery.error) ? (
                        <ProtectedErrorState description={pricingQuery.error.message} />
                    ) : (
                        <ErrorState description={pricingQuery.error.message} title="Unable to load customer pricing" />
                    );
                }
                return (
                    <ContentCard className="p-0">
                        <DataTable
                            columns={pricingColumns}
                            emptyState={<EmptyState className="m-6" description="No customer-specific price list assignments are configured yet." title="No price lists found" />}
                            getRowKey={(assignment) => assignment.id}
                            rows={pricingQuery.data.items}
                        />
                    </ContentCard>
                );
            case 'sales':
                return (
                    <SectionCard description="Sales-order, shipment, and invoice profile wiring is reserved for the Sales phase. This tab is already in place so the customer profile layout stays stable." title="Sales readiness">
                        <p className="text-sm leading-6 text-stone-600">Sales endpoints were not part of this phase request, so this section intentionally stays as a readiness panel for upcoming order and invoice integration.</p>
                    </SectionCard>
                );
            case 'ar':
                return (
                    <SectionCard description="Receivables transaction endpoints are outside the current phase scope, but the customer record already carries the AR account linkage used to anchor this tab later." title="AR readiness">
                        <p className="text-sm leading-6 text-stone-600">Accounts receivable detail wiring is deferred. The profile still exposes AR-facing master data so the customer record is ready for that next phase.</p>
                    </SectionCard>
                );
            default:
                return null;
        }
    }

    if (customerId <= 0) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState description="The customer route is missing a valid customer ID." title="Invalid customer route" />
            </div>
        );
    }

    if (customerQuery.isPending) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <LoadingState lines={8} />
            </div>
        );
    }

    if (customerQuery.isError) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                {isForbiddenError(customerQuery.error) ? (
                    <ProtectedErrorState description={customerQuery.error.message} />
                ) : (
                    <ErrorState description={customerQuery.error.message} title="Unable to load customer profile" />
                )}
            </div>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <Link to={`/customers/${customerQuery.data.id}/edit`}>
                        <Button variant="secondary">Edit Customer</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Customers', href: '/customers' }, { label: customerQuery.data.name }]}
                description="Customer profiles keep addresses, contacts, pricing context, and future commercial modules grouped behind one stable master-data page."
                title={customerQuery.data.name}
            />

            <ContentCard className="space-y-6">
                <div className="flex flex-wrap gap-2 border-b border-stone-200/80 pb-4">
                    {detailTabs.map((tab) => (
                        <button
                            key={tab.id}
                            className={activeTab === tab.id ? 'rounded-full bg-stone-950 px-4 py-2 text-sm font-medium text-white' : 'rounded-full bg-stone-100 px-4 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-200'}
                            onClick={() => setSearchParams({ tab: tab.id })}
                            type="button"
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                {renderTabContent()}
            </ContentCard>
        </div>
    );
}
