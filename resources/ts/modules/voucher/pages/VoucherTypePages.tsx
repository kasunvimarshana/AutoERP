import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { voucherApi } from '../services/voucherApi';
import { VoucherPageHeader, VoucherTypeForm, VoucherTypeSummaryCard, VoucherTypeTable } from '../components/VoucherComponents';
import type { VoucherType } from '../types/voucher.types';

export function VoucherTypeListPage() {
    const [rows, setRows] = useState<VoucherType[]>([]);
    const [filters, setFilters] = useState<Record<string, DataToolbarFilterValue>>({});
    const [search, setSearch] = useState('');
    const [error, setError] = useState<Error | null>(null);
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        let active = true;
        setIsLoading(true);
        voucherApi.types.list({
            perPage: 25,
            search,
            status: String(filters.status ?? ''),
        })
            .then((response) => {
                if (!active) {
                    return;
                }

                setRows(response.data);
                setError(null);
            })
            .catch((caught: Error) => {
                if (active) {
                    setError(caught);
                }
            })
            .finally(() => {
                if (active) {
                    setIsLoading(false);
                }
            });

        return () => {
            active = false;
        };
    }, [filters.status, search]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        setFilters((current) => ({ ...current, [filterId]: value }));
    }

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<Link to="/vouchers/types/new"><Button>New Type</Button></Link>}
                subtitle="Voucher types define generic voucher behavior, direction, payment requirements, approval, balance validation, sequence, and document defaults."
                title="Voucher Types"
            />
            {error ? <EmptyState description={error.message} title="Voucher types failed" /> : null}
            <DataToolbar
                disabled={isLoading}
                filterValues={filters}
                filters={[{ id: 'status', label: 'Status', options: [{ label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }], type: 'status' }]}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onResetFilters={() => setFilters({})}
                onSearchChange={setSearch}
                savedViewsDisabledReason="Saved voucher type views need a preferences endpoint."
                searchPlaceholder="Search type code, name, category..."
                searchValue={search}
            />
            <VoucherTypeTable rows={rows} />
        </div>
    );
}

export function VoucherTypeCreatePage() {
    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<><Link to="/vouchers/types"><Button variant="secondary">Cancel</Button></Link><Button>Save Type</Button></>}
                subtitle="Create a generic voucher type. Backend validates sequence, document, payment, and workflow configuration."
                title="New Voucher Type"
            />
            <VoucherTypeForm />
        </div>
    );
}

export function VoucherTypeEditPage() {
    const { id = '' } = useParams();
    const [type, setType] = useState<VoucherType | null>(null);
    const [error, setError] = useState<Error | null>(null);

    useEffect(() => {
        let active = true;
        setType(null);
        voucherApi.types.get(id)
            .then((response) => {
                if (active) {
                    setType(response.data);
                    setError(null);
                }
            })
            .catch((caught: Error) => {
                if (active) {
                    setError(caught);
                }
            });

        return () => {
            active = false;
        };
    }, [id]);

    if (error) {
        return <EmptyState description={error.message} title="Voucher type failed" />;
    }

    if (!type) {
        return <EmptyState description="Loading voucher type from backend..." title="Loading voucher type" />;
    }

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<><Link to={`/vouchers/types/${type.id}`}><Button variant="secondary">View</Button></Link><Button>Save Changes</Button></>}
                subtitle="Edit voucher type setup. Type behavior stays generic and backend-owned."
                title={`Edit ${type.name}`}
            />
            <VoucherTypeForm type={type} />
        </div>
    );
}

export function VoucherTypeDetailPage() {
    const { id = '' } = useParams();
    const [type, setType] = useState<VoucherType | null>(null);
    const [error, setError] = useState<Error | null>(null);

    useEffect(() => {
        let active = true;
        setType(null);
        voucherApi.types.get(id)
            .then((response) => {
                if (active) {
                    setType(response.data);
                    setError(null);
                }
            })
            .catch((caught: Error) => {
                if (active) {
                    setError(caught);
                }
            });

        return () => {
            active = false;
        };
    }, [id]);

    if (error) {
        return <EmptyState description={error.message} title="Voucher type failed" />;
    }

    if (!type) {
        return <EmptyState description="Loading voucher type from backend..." title="Loading voucher type" />;
    }

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<><Link to={`/vouchers/types/${type.id}/edit`}><Button>Edit</Button></Link><Button variant="secondary">Activate</Button><Button variant="danger">Deactivate</Button></>}
                subtitle="Type detail shows reusable voucher behavior without Purchase, Sales, Service, or Rental-specific workflow logic."
                title={type.name}
            />
            <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
                <VoucherTypeSummaryCard type={type} />
                <PreviewPanel rows={[
                    { label: 'Sequence', value: type.defaultSequence },
                    { label: 'Document definition', value: type.defaultDocumentDefinition },
                    { label: 'Balance validation', value: type.requiresBalancedLines ? 'Backend required' : 'Backend optional' },
                    { label: 'Payment method', value: type.requiresPaymentMethod ? 'Backend required' : 'Not required' },
                ]} status="Type Behavior" title="Behavior" />
            </div>
        </div>
    );
}
