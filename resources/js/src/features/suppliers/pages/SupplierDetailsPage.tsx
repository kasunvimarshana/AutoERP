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
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatDate, formatCurrency, getStatusTone, parsePositiveInteger } from '../../shared/utils';
import { useSupplier, useSupplierAddresses, useSupplierContacts, useSupplierPriceLists, useSupplierProducts } from '../hooks';
import type { SupplierAddress, SupplierContact, SupplierPriceListAssignment, SupplierProduct } from '../types';

const detailTabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'addresses', label: 'Addresses' },
    { id: 'contacts', label: 'Contacts' },
    { id: 'products', label: 'Products' },
    { id: 'pricing', label: 'Pricing' },
    { id: 'purchases', label: 'Purchases' },
    { id: 'ap', label: 'AP' },
] as const;

type DetailTabId = (typeof detailTabs)[number]['id'];

export function SupplierDetailsPage() {
    const { tenantId } = useTenant();
    const { supplierId: supplierIdParam } = useParams();
    const supplierId = parsePositiveInteger(supplierIdParam ?? null, 0);
    const [searchParams, setSearchParams] = useSearchParams();
    const activeTab = (searchParams.get('tab') as DetailTabId | null) ?? 'overview';

    const supplierQuery = useSupplier(supplierId, supplierId > 0);
    const userQuery = useUser(supplierQuery.data?.user_id ?? 0, 'permissions', Boolean(supplierQuery.data?.user_id));
    const addressesQuery = useSupplierAddresses(supplierId, tenantId, supplierId > 0 && activeTab === 'addresses');
    const contactsQuery = useSupplierContacts(supplierId, tenantId, supplierId > 0 && activeTab === 'contacts');
    const productsQuery = useSupplierProducts(supplierId, tenantId, supplierId > 0 && activeTab === 'products');
    const pricingQuery = useSupplierPriceLists(supplierId, tenantId, supplierId > 0 && activeTab === 'pricing');

    const addressColumns: DataTableColumn<SupplierAddress>[] = useMemo(
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

    const contactColumns: DataTableColumn<SupplierContact>[] = useMemo(
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

    const productColumns: DataTableColumn<SupplierProduct>[] = useMemo(
        () => [
            { key: 'product_id', header: 'Product ID', render: (product) => <span className="text-sm text-stone-700">#{product.product_id}</span> },
            { key: 'variant_id', header: 'Variant', render: (product) => <span className="text-sm text-stone-700">{product.variant_id ? `#${product.variant_id}` : '-'}</span> },
            { key: 'supplier_sku', header: 'Supplier SKU', render: (product) => <span className="text-sm text-stone-700">{product.supplier_sku ?? '-'}</span> },
            { key: 'lead_time_days', header: 'Lead Time', render: (product) => <span className="text-sm text-stone-700">{product.lead_time_days ? `${product.lead_time_days} days` : '-'}</span> },
            { key: 'last_purchase_price', header: 'Last Price', render: (product) => <span className="text-sm text-stone-700">{formatCurrency(product.last_purchase_price)}</span> },
        ],
        [],
    );

    const pricingColumns: DataTableColumn<SupplierPriceListAssignment>[] = useMemo(
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
                        <SectionCard description="Core supplier identity, commercial settings, and procurement-facing state from the backend supplier resource." title="Account overview">
                            <dl className="grid gap-4 text-sm text-stone-700 md:grid-cols-2">
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Supplier Code</dt>
                                    <dd className="mt-1 font-medium text-stone-950">{supplierQuery.data?.supplier_code ?? '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Type</dt>
                                    <dd className="mt-1 font-medium text-stone-950">{supplierQuery.data?.type ?? '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Status</dt>
                                    <dd className="mt-1">
                                        <StatusBadge tone={getStatusTone(supplierQuery.data?.status)}>{supplierQuery.data?.status ?? '-'}</StatusBadge>
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Payment Terms</dt>
                                    <dd className="mt-1 font-medium text-stone-950">{supplierQuery.data?.payment_terms_days ? `${supplierQuery.data.payment_terms_days} days` : '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">AP Account ID</dt>
                                    <dd className="mt-1 font-medium text-stone-950">{supplierQuery.data?.ap_account_id ?? '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Currency ID</dt>
                                    <dd className="mt-1 font-medium text-stone-950">{supplierQuery.data?.currency_id ?? '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Tax Number</dt>
                                    <dd className="mt-1 font-medium text-stone-950">{supplierQuery.data?.tax_number ?? '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Registration</dt>
                                    <dd className="mt-1 font-medium text-stone-950">{supplierQuery.data?.registration_number ?? '-'}</dd>
                                </div>
                            </dl>
                        </SectionCard>

                        <SectionCard description="Linked supplier portal-user information is loaded from the Users module when a supplier carries an associated user record." title="Portal user">
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
                                <EmptyState className="border-0 bg-transparent p-0 shadow-none" description="This supplier does not have a linked portal user yet." title="No linked user" />
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
                        <ErrorState description={addressesQuery.error.message} title="Unable to load supplier addresses" />
                    );
                }
                return (
                    <ContentCard className="p-0">
                        <DataTable
                            columns={addressColumns}
                            emptyState={<EmptyState className="m-6" description="No addresses are recorded for this supplier yet." title="No addresses found" />}
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
                        <ErrorState description={contactsQuery.error.message} title="Unable to load supplier contacts" />
                    );
                }
                return (
                    <ContentCard className="p-0">
                        <DataTable
                            columns={contactColumns}
                            emptyState={<EmptyState className="m-6" description="No contacts are recorded for this supplier yet." title="No contacts found" />}
                            getRowKey={(contact) => contact.id}
                            rows={contactsQuery.data.items}
                        />
                    </ContentCard>
                );
            case 'products':
                if (productsQuery.isPending) {
                    return <LoadingState lines={6} />;
                }
                if (productsQuery.isError) {
                    return isForbiddenError(productsQuery.error) ? (
                        <ProtectedErrorState description={productsQuery.error.message} />
                    ) : (
                        <ErrorState description={productsQuery.error.message} title="Unable to load supplier products" />
                    );
                }
                return (
                    <ContentCard className="p-0">
                        <DataTable
                            columns={productColumns}
                            emptyState={<EmptyState className="m-6" description="No supplier-product relationships are configured yet." title="No products found" />}
                            getRowKey={(product) => product.id}
                            rows={productsQuery.data.items}
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
                        <ErrorState description={pricingQuery.error.message} title="Unable to load supplier pricing" />
                    );
                }
                return (
                    <ContentCard className="p-0">
                        <DataTable
                            columns={pricingColumns}
                            emptyState={<EmptyState className="m-6" description="No supplier price list assignments are configured yet." title="No price lists found" />}
                            getRowKey={(assignment) => assignment.id}
                            rows={pricingQuery.data.items}
                        />
                    </ContentCard>
                );
            case 'purchases':
                return (
                    <SectionCard description="Purchase-order, GRN, invoice, and return detail wiring is reserved for the Purchase phase. This tab is already anchored in the supplier profile for continuity." title="Purchase readiness">
                        <p className="text-sm leading-6 text-stone-600">Purchasing transaction endpoints were not part of this phase request, so this section intentionally stays as a readiness panel for upcoming procurement integration.</p>
                    </SectionCard>
                );
            case 'ap':
                return (
                    <SectionCard description="Accounts payable transaction detail is outside the current phase scope, but the supplier master already carries the AP account linkage used to anchor that future workspace." title="AP readiness">
                        <p className="text-sm leading-6 text-stone-600">Payables detail wiring is deferred. The supplier record still exposes the AP-facing master data needed for the next finance-oriented phase.</p>
                    </SectionCard>
                );
            default:
                return null;
        }
    }

    if (supplierId <= 0) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState description="The supplier route is missing a valid supplier ID." title="Invalid supplier route" />
            </div>
        );
    }

    if (supplierQuery.isPending) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <LoadingState lines={8} />
            </div>
        );
    }

    if (supplierQuery.isError) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                {isForbiddenError(supplierQuery.error) ? (
                    <ProtectedErrorState description={supplierQuery.error.message} />
                ) : (
                    <ErrorState description={supplierQuery.error.message} title="Unable to load supplier profile" />
                )}
            </div>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <Link to={`/suppliers/${supplierQuery.data.id}/edit`}>
                        <Button variant="secondary">Edit Supplier</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Suppliers', href: '/suppliers' }, { label: supplierQuery.data.name }]}
                description="Supplier profiles keep addresses, contacts, products, pricing context, and future purchasing modules grouped behind one stable master-data page."
                title={supplierQuery.data.name}
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
