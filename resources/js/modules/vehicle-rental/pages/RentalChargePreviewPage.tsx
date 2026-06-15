import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { sumDecimals } from '@/shared/utils/decimal';
import { RentalStatusBadge } from '../components/RentalStatusBadge';
import { approveRentalCharges, generateRentalCharges, getRentalAgreement, listRentalCharges, previewRentalCharges } from '../vehicleRentalApi';

export default function RentalChargePreviewPage() {
    const agreementId = Number(useParams().id);
    const agreement = useApi((signal) => getRentalAgreement(agreementId, signal), [agreementId]);
    const charges = useApi((signal) => listRentalCharges(agreementId, signal), [agreementId]);
    const [previewRows, setPreviewRows] = useState<Awaited<ReturnType<typeof previewRentalCharges>> | null>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    if (agreement.loading) return <LoadingState />;
    if (!agreement.data) return <ErrorAlert error={agreement.error} />;
    const rows = previewRows ?? charges.data ?? [];
    const total = sumDecimals(rows.filter((row) => row.status !== 'cancelled').map((row) => row.total_amount));
    const action = async (name: 'preview' | 'generate' | 'replace' | 'approve') => {
        setBusy(true);
        setError(null);
        try {
            if (name === 'preview') {
                setPreviewRows(await previewRentalCharges(agreementId));
                return;
            }
            if (name === 'approve') await approveRentalCharges(agreementId);
            else await generateRentalCharges(agreementId, name === 'replace');
            setPreviewRows(null);
            charges.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };
    return (
        <>
            <ContentHeader title={`${agreement.data.direction === 'outbound' ? 'Revenue' : 'Cost'} calculation / ${agreement.data.agreement_number}`} description="Auditable calculations become invoice-ready or payable-ready charges only after approval." actions={<>
                <Button type="button" variant="secondary" loading={busy} onClick={() => action('preview')}>Preview</Button>
                <Button type="button" variant="secondary" loading={busy} onClick={() => action('generate')}>Generate</Button>
                {!previewRows && rows.length > 0 && rows.every((row) => row.status === 'draft') && <Button type="button" variant="secondary" loading={busy} onClick={() => action('replace')}>Recalculate</Button>}
                {!previewRows && rows.some((row) => row.status === 'draft') && <Button type="button" loading={busy} onClick={() => action('approve')}>Approve charges</Button>}
                {!previewRows && rows.some((row) => row.status === 'approved' && row.invoice_status !== 'invoiced') && <LinkButton to={`/vehicle-rental/agreements/${agreementId}/invoice`}>{agreement.data.direction === 'outbound' ? 'Create customer invoice' : 'Create supplier payable'}</LinkButton>}
            </>} />
            <ErrorAlert error={error ?? charges.error} />
            <Panel title={previewRows ? 'Non-persistent preview' : 'Charge breakdown'}>
                {charges.loading ? <LoadingState /> : <DataTable rows={rows} rowKey={(row) => row.id} columns={[
                    { key: 'type', header: 'Charge', render: (row) => row.charge_type.replaceAll('_', ' ') },
                    { key: 'period', header: 'Period', render: (row) => row.billing_cycle_key ?? row.period_sequence ?? '-' },
                    { key: 'description', header: 'Description', render: (row) => row.description },
                    { key: 'quantity', header: 'Quantity', render: (row) => row.quantity },
                    { key: 'rate', header: 'Rate', render: (row) => <MoneyDisplay value={row.rate} /> },
                    { key: 'tax', header: 'Tax', render: (row) => <MoneyDisplay value={row.tax_amount} /> },
                    { key: 'withholding', header: 'Withholding', render: (row) => <MoneyDisplay value={row.withholding_amount} /> },
                    { key: 'total', header: 'Total', render: (row) => <MoneyDisplay value={row.total_amount} /> },
                    { key: 'invoice', header: 'Invoice', render: (row) => <RentalStatusBadge status={row.invoice_status} /> },
                    { key: 'status', header: 'Status', render: (row) => <RentalStatusBadge status={row.status} /> },
                ]} />}
                <div className="mt-4 flex justify-end text-lg font-bold">Total&nbsp;<MoneyDisplay value={total} /></div>
            </Panel>
        </>
    );
}
