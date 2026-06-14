import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
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
import { changeRentalReservationStatus, listRentalReservations } from '../vehicleRentalApi';
import type { RentalReservation } from '../vehicleRentalTypes';
import { RentalStatusBadge } from '../components/RentalStatusBadge';

export default function RentalReservationListPage() {
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listRentalReservations({ search: debounced, status, page, per_page: 25 }, signal), [debounced, status, page]);
    const action = async (row: RentalReservation, next: 'pending' | 'confirm' | 'cancel') => {
        setBusyId(row.id);
        setError(null);
        try {
            await changeRentalReservationStatus(row.id, next);
            result.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusyId(null);
        }
    };
    const columns: DataColumn<RentalReservation>[] = [
        { key: 'number', header: 'Reservation', render: (row) => <span className="font-semibold text-slate-900">{row.reservation_number}</span> },
        { key: 'party', header: 'Party', render: (row) => readableRelation(row.party) },
        { key: 'vehicle', header: 'Vehicle', render: (row) => row.vehicle?.registration_number ?? readableRelation(row.vehicle) },
        { key: 'period', header: 'Period', render: (row) => `${formatDate(row.start_at)} to ${formatDate(row.expected_end_at)}` },
        { key: 'direction', header: 'Direction', render: (row) => row.direction },
        { key: 'status', header: 'Status', render: (row) => <RentalStatusBadge status={row.status} /> },
        { key: 'actions', header: '', render: (row) => <div className="flex flex-wrap gap-2">
            {row.status === 'draft' && <Button type="button" variant="secondary" loading={busyId === row.id} onClick={() => action(row, 'pending')}>Submit</Button>}
            {row.status === 'pending' && <Button type="button" loading={busyId === row.id} onClick={() => action(row, 'confirm')}>Confirm</Button>}
            {row.status === 'confirmed' && <Link to={`/vehicle-rental/agreements/create?reservation_id=${row.id}`}><Button type="button">Create agreement</Button></Link>}
            {!['converted', 'cancelled'].includes(row.status) && <Button type="button" variant="danger" loading={busyId === row.id} onClick={() => action(row, 'cancel')}>Cancel</Button>}
        </div> },
    ];

    return (
        <>
            <ContentHeader title="Rental reservations" description="Booking intent before a rental contract is confirmed." actions={<Link to="/vehicle-rental/reservations/create"><Button>New reservation</Button></Link>} />
            <div className="mb-4 grid gap-4 md:grid-cols-2">
                <Input label="Search" type="search" value={search} placeholder="Reservation, party, or vehicle" onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select label="Status" value={status} options={['draft', 'pending', 'confirmed', 'converted', 'cancelled'].map((value) => ({ value, label: value }))} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={error ?? result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
