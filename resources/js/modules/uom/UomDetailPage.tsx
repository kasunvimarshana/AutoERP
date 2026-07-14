import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { ApiError } from '@/shared/api/apiError';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Tabs } from '@/shared/components/Tabs';
import { getUom, listUomConversions } from './uomApi';
import { uomPermissions } from './uomPermissions';
import type { UnitOfMeasure, UomConversion } from './uomTypes';

type Tab = 'summary' | 'from' | 'to';
const tabs = [
    { id: 'summary' as const, label: 'Summary' },
    { id: 'from' as const, label: 'Conversions From' },
    { id: 'to' as const, label: 'Conversions To' },
];

export default function UomDetailPage() {
    const auth = useAuth();
    const { id } = useParams();
    const uomId = Number(id);
    const [uom, setUom] = useState<UnitOfMeasure | null>(null);
    const [activeTab, setActiveTab] = useState<Tab>('summary');
    const [error, setError] = useState<ApiError | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const controller = new AbortController();
        getUom(uomId, controller.signal)
            .then((next) => !controller.signal.aborted && setUom(next))
            .catch((nextError) => !controller.signal.aborted && setError(nextError instanceof ApiError ? nextError : new ApiError('Unable to load UOM.', null)))
            .finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [uomId]);

    if (loading) return <LoadingState />;
    if (!uom) return <ErrorAlert error={error ?? new ApiError('UOM was not found.', 404)} />;

    return (
        <>
            <ContentHeader title={`${uom.code} - ${uom.name}`} description="Generic unit of measure detail." actions={hasPermission(auth, uomPermissions.update) ? <LinkButton to={`/uoms/${uom.id}/edit`}>Edit</LinkButton> : undefined} />
            <Panel className="p-0">
                <Tabs tabs={tabs} active={activeTab} onChange={setActiveTab} />
                <div className="p-5">
                    {activeTab === 'summary' && <Summary uom={uom} />}
                    {activeTab === 'from' && <ConversionTab uomId={uomId} direction="from" />}
                    {activeTab === 'to' && <ConversionTab uomId={uomId} direction="to" />}
                </div>
            </Panel>
        </>
    );
}

function Summary({ uom }: { uom: UnitOfMeasure }) {
    const rows = [
        ['Code', uom.code],
        ['Name', uom.name],
        ['Symbol', uom.symbol ?? '-'],
        ['Type', uom.type],
        ['Category', uom.category],
        ['Decimal precision', String(uom.decimal_precision)],
        ['Allows fractional quantity', uom.allow_fractional_quantity ? 'Yes' : 'No'],
        ['Base UOM', uom.is_base ? 'Yes' : 'No'],
        ['Status', uom.is_active ? 'Active' : 'Inactive'],
        ['Description', uom.description ?? '-'],
    ];

    return <dl className="grid gap-4 md:grid-cols-2">{rows.map(([label, value]) => <div key={label}><dt className="text-xs uppercase text-slate-500">{label}</dt><dd className="mt-1 font-medium text-slate-900">{value}</dd></div>)}</dl>;
}

function ConversionTab({ uomId, direction }: { uomId: number; direction: 'from' | 'to' }) {
    const [rows, setRows] = useState<UomConversion[]>([]);
    const [error, setError] = useState<ApiError | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const controller = new AbortController();
        listUomConversions({ [direction === 'from' ? 'from_uom_id' : 'to_uom_id']: uomId, per_page: 25 }, controller.signal)
            .then((result) => !controller.signal.aborted && setRows(result.data))
            .catch((nextError) => !controller.signal.aborted && setError(nextError instanceof ApiError ? nextError : new ApiError('Unable to load conversions.', null)))
            .finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [direction, uomId]);

    const columns: DataColumn<UomConversion>[] = [
        { key: 'from', header: 'From', render: (row) => formatUom(row.from_uom) },
        { key: 'to', header: 'To', render: (row) => formatUom(row.to_uom) },
        { key: 'factor', header: 'Factor', render: (row) => row.conversion_factor },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
    ];

    if (loading) return <LoadingState />;
    return <><ErrorAlert error={error} /><DataTable rows={rows} columns={columns} rowKey={(row) => row.id} /></>;
}

function formatUom(uom: UomConversion['from_uom']) {
    return uom ? <span>{uom.code}<span className="block text-xs text-slate-500">{uom.name}</span></span> : '-';
}
