import { Link } from 'react-router-dom';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import type { SalesRelatedDocument } from '../salesTypes';

function pathFor(document: SalesRelatedDocument) {
    if (document.type === 'sales_delivery') return `/sales/deliveries/${document.id}`;
    if (document.type === 'sales_return') return `/sales/returns/${document.id}`;
    if (document.type === 'sales_credit_note') return `/sales/credit-notes/${document.id}`;
    if (document.type === 'customer_invoice') return `/invoices/${document.id}`;
    return '#';
}

export function SalesRelatedDocuments({ documents }: { documents?: SalesRelatedDocument[] }) {
    return (
        <Panel title="Related documents">
            {!documents?.length ? (
                <p className="text-sm text-slate-500">No related documents.</p>
            ) : (
                <div className="divide-y divide-slate-100">
                    {documents.map((document) => (
                        <div key={`${document.type}-${document.id}`} className="flex items-center justify-between gap-3 py-2 text-sm">
                            <Link className="font-medium text-sky-700 hover:underline" to={pathFor(document)}>
                                {document.number || `${document.type.replaceAll('_', ' ')} #${document.id}`}
                            </Link>
                            <StatusBadge status={document.status ?? undefined} />
                        </div>
                    ))}
                </div>
            )}
        </Panel>
    );
}
