import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import { RentalStatusBadge } from '../components/RentalStatusBadge';
import { listRentalAgreements } from '../vehicleRentalApi';
import type { RentalAgreement } from '../vehicleRentalTypes';

export default function RentalAgreementListPage() {
    const [searchParams] = useSearchParams();
    const requestedDirection = searchParams.get('direction');
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [direction, setDirection] = useState(requestedDirection === 'inbound' || requestedDirection === 'outbound' ? requestedDirection : '');
    const [page, setPage] = useState(1);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listRentalAgreements({ search: debounced, status, direction, page, per_page: 25 }, signal), [debounced, status, direction, page]);
    useEffect(() => {
        setDirection(requestedDirection === 'inbound' || requestedDirection === 'outbound' ? requestedDirection : '');
        setPage(1);
    }, [requestedDirection]);
    const columns: DataColumn<RentalAgreement>[] = [
        { key: 'number', header: 'Agreement', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/vehicle-rental/agreements/${row.id}`}>{row.agreement_number}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.agreement_date) },
        { key: 'party', header: 'Party', render: (row) => readableRelation(row.party) },
        { key: 'vehicle', header: 'Vehicle', render: (row) => row.vehicles.map((item) => item.vehicle?.registration_number ?? item.vehicle?.name).filter(Boolean).join(', ') || '-' },
        { key: 'period', header: 'Period', render: (row) => `${formatDate(row.start_at)} to ${formatDate(row.expected_end_at)}` },
        { key: 'direction', header: 'Direction', render: (row) => row.direction },
        { key: 'status', header: 'Status', render: (row) => <RentalStatusBadge status={row.status} /> },
        { key: 'actions', header: '', render: (row) => <LinkButton to={`/vehicle-rental/agreements/${row.id}`} variant="secondary">Open</LinkButton> },
    ];
    const directionalView = requestedDirection === 'inbound' || requestedDirection === 'outbound';
    const title = requestedDirection === 'inbound'
        ? 'Owner / Supplier Agreements'
        : requestedDirection === 'outbound'
            ? 'Customer Agreements'
            : 'Rental Agreements';
    const createLabel = requestedDirection === 'inbound' ? 'New owner agreement' : requestedDirection === 'outbound' ? 'New customer agreement' : 'New agreement';
    const createTo = requestedDirection ? `/vehicle-rental/agreements/create?direction=${requestedDirection}` : '/vehicle-rental/agreements/create';
    return (
        <>
            <ContentHeader title={title} description="Operational source documents for outbound rentals and inbound hire-in." actions={<>
                {!directionalView && <LinkButton to="/vehicle-rental/reservations" variant="secondary">Reservations</LinkButton>}
                {!directionalView && <LinkButton to="/vehicle-rental/availability" variant="secondary">Availability</LinkButton>}
                <LinkButton to={createTo}>{createLabel}</LinkButton>
            </>} />
            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Input label="Search" type="search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select label="Status" value={status} options={['draft', 'confirmed', 'active', 'returned', 'completed', 'cancelled'].map((value) => ({ value, label: value }))} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
                <Select label="Direction" value={direction} options={[{ value: 'outbound', label: 'Outbound' }, { value: 'inbound', label: 'Inbound' }]} onChange={(event) => { setDirection(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
