import { useState } from 'react';
import { Link } from 'react-router-dom';
import { listCustomers } from '@/modules/customer/customerApi';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { getTaxLookups, listCustomerTaxProfiles, saveCustomerTaxProfile } from '../taxApi';
import { taxPermissions } from '../taxPermissions';
import type { TaxProfile } from '../taxTypes';

export default function CustomerTaxProfilePage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, taxPermissions.profilesManage);
    const [page, setPage] = useState(1);
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [form, setForm] = useState({ customer_id: '', tax_group_id: '', registration_number: '', exemption_status: 'taxable', active: true });
    const [refresh, setRefresh] = useState(0);
    const [error, setError] = useState<ApiError | null>(null);
    const profiles = useApi((signal) => listCustomerTaxProfiles({ page, per_page: 25 }, signal), [page, refresh]);
    const lookups = useApi((signal) => getTaxLookups(signal), []);
    const customers = useApi((signal) => listCustomers({ per_page: 100 }, signal), []);

    const columns: DataColumn<TaxProfile>[] = [
        { key: 'customer', header: 'Customer', render: (row) => row.party ? `${row.party.code ?? ''} ${row.party.name}` : '-' },
        { key: 'group', header: 'Tax group', render: (row) => row.tax_group ? `${row.tax_group.code ?? ''} ${row.tax_group.name}` : '-' },
        { key: 'status', header: 'Exemption', render: (row) => row.exemption_status },
        { key: 'active', header: 'Status', render: (row) => <StatusBadge status={row.active ? 'active' : 'inactive'} /> },
        ...(canManage ? [{ key: 'edit', header: '', className: 'text-right', render: (row: TaxProfile) => <button type="button" className="text-sm font-semibold text-sky-700" onClick={() => { setSelectedId(row.id); setForm({ customer_id: String(row.customer_id ?? ''), tax_group_id: String(row.tax_group_id ?? ''), registration_number: row.registration_number ?? '', exemption_status: row.exemption_status, active: row.active }); }}>Edit</button> }] : []),
    ];

    return (
        <>
            <ContentHeader title="Customer tax profiles" description="Customer-specific tax group, registration, and exemption status." actions={<Link className="text-sm font-semibold text-sky-700 hover:underline" to="/tax/supplier-profiles">Supplier profiles</Link>} />
            <ErrorAlert error={error ?? profiles.error ?? lookups.error ?? customers.error} />
            <div className={canManage ? 'grid gap-4 xl:grid-cols-[1fr_420px]' : ''}>
                <div>
                    {profiles.loading ? <LoadingState /> : <DataTable rows={profiles.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
                    <Pagination meta={profiles.data?.meta} onPageChange={setPage} />
                </div>
                {canManage && (
                    <Panel title={selectedId ? 'Edit profile' : 'Create profile'}>
                        <div className="space-y-3">
                            <Select label="Customer" value={form.customer_id} error={fieldError(error, 'customer_id')} options={(customers.data?.data ?? []).map((customer) => ({ value: customer.id, label: `${customer.code ?? ''} - ${customer.name}` }))} onChange={(event) => setForm({ ...form, customer_id: event.target.value })} />
                            <Select label="Tax group" value={form.tax_group_id} options={(lookups.data?.groups ?? []).map((group) => ({ value: group.id, label: `${group.code ?? ''} - ${group.name}` }))} placeholder="No group" onChange={(event) => setForm({ ...form, tax_group_id: event.target.value })} />
                            <Input label="Registration number" value={form.registration_number} onChange={(event) => setForm({ ...form, registration_number: event.target.value })} />
                            <Select label="Exemption status" value={form.exemption_status} error={fieldError(error, 'exemption_status')} options={(lookups.data?.exemption_statuses ?? ['taxable']).map((value) => ({ value, label: value }))} onChange={(event) => setForm({ ...form, exemption_status: event.target.value })} />
                            <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.active} onChange={(event) => setForm({ ...form, active: event.target.checked })} /> Active</label>
                            <Button onClick={async () => {
                                setError(null);
                                try {
                                    await saveCustomerTaxProfile(selectedId, {
                                        customer_id: Number(form.customer_id),
                                        tax_group_id: form.tax_group_id ? Number(form.tax_group_id) : null,
                                        registration_number: form.registration_number || null,
                                        exemption_status: form.exemption_status,
                                        active: form.active,
                                    });
                                    setSelectedId(null);
                                    setForm({ customer_id: '', tax_group_id: '', registration_number: '', exemption_status: 'taxable', active: true });
                                    setRefresh((value) => value + 1);
                                } catch (requestError) {
                                    setError(toApiError(requestError));
                                }
                            }}>{selectedId ? 'Update profile' : 'Create profile'}</Button>
                        </div>
                    </Panel>
                )}
            </div>
        </>
    );
}
