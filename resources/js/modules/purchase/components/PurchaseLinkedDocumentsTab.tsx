import { Link } from 'react-router-dom';
import { DetailGrid } from '@/shared/components/DetailGrid';
import type { SourceSummary } from '../purchaseApi';

export function PurchaseLinkedDocumentsTab({ source, links = [] }: {
    source?: SourceSummary | null;
    links?: Array<{ label: string; to: string; text: string }>;
}) {
    return (
        <DetailGrid items={[
            { label: 'Source', value: source?.number ?? source?.type?.replaceAll('_', ' ') ?? '-' },
            ...links.map((link) => ({ label: link.label, value: <Link className="text-sky-700 hover:underline" to={link.to}>{link.text}</Link> })),
        ]} />
    );
}
