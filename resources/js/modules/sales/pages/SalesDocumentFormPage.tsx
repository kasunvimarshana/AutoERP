import { useParams } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import { SalesDocumentForm } from '../components/SalesDocumentForm';
import { getSalesOrder, getSalesQuotation } from '../salesApi';
import type { SalesOrder, SalesQuotation } from '../salesTypes';

export default function SalesDocumentFormPage({ kind }: { kind: 'quotation' | 'order' }) {
    const id = Number(useParams().id ?? 0);
    const result = useApi<SalesQuotation | SalesOrder | undefined>(
        (signal) => id
            ? (kind === 'quotation' ? getSalesQuotation(id, signal) : getSalesOrder(id, signal))
            : Promise.resolve(undefined),
        [id, kind],
    );
    const label = kind === 'quotation' ? 'quotation' : 'sales order';

    return (
        <>
            <ContentHeader title={id ? `Edit ${label}` : `New ${label}`} description="Backend-calculated pricing, validation, and workflow state remain authoritative." />
            {result.error && <ErrorAlert error={result.error} />}
            {id && result.loading ? <LoadingState /> : <SalesDocumentForm kind={kind} document={result.data ?? undefined} />}
        </>
    );
}
