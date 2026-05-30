import { DataTable } from '../data/DataTable';
import { EmptyState } from '../ui/EmptyState';
import { StatusBadge } from './StatusBadge';
import type { BusinessPartyLink } from '../../types/businessParty.types';

function humanize(value: string) {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function partyLabel(type: string, id?: string, name?: string) {
    if (name) {
        return `${name} (${humanize(type)})`;
    }

    if (id) {
        return `${humanize(type)} #${id}`;
    }

    return humanize(type);
}

type BusinessPartyLinksPanelProps = {
    emptyDescription?: string;
    links: BusinessPartyLink[];
    title?: string;
};

export function BusinessPartyLinksPanel({
    emptyDescription = 'No linked party roles were returned for this profile.',
    links,
    title = 'Cross-Role Links',
}: BusinessPartyLinksPanelProps) {
    if (links.length === 0) {
        return <EmptyState description={emptyDescription} title={title} />;
    }

    return (
        <div className="space-y-3">
            <div>
                <h2 className="text-base font-bold text-slate-950">{title}</h2>
                <p className="mt-1 text-sm text-slate-500">Customer, supplier, provider, payer, and payee roles are linked as context, not treated as mutually exclusive identities.</p>
            </div>
            <DataTable
                columns={[
                    {
                        header: 'Source',
                        key: 'source',
                        render: (row) => partyLabel(row.sourcePartyType, row.sourcePartyId, row.sourcePartyName),
                    },
                    {
                        header: 'Relation',
                        key: 'relationType',
                        render: (row) => humanize(row.relationType),
                    },
                    {
                        header: 'Target',
                        key: 'target',
                        render: (row) => partyLabel(row.targetPartyType, row.targetPartyId, row.targetPartyName),
                    },
                    {
                        header: 'Window',
                        key: 'window',
                        render: (row) => `${row.startDate || 'Open'} to ${row.endDate || 'Current'}`,
                    },
                    {
                        header: 'Status',
                        key: 'isActive',
                        render: (row) => <StatusBadge status={row.isActive ? 'active' : 'inactive'} />,
                    },
                    {
                        header: 'Notes',
                        key: 'notes',
                        render: (row) => row.notes || 'No notes',
                    },
                ]}
                getRowKey={(row) => row.id}
                rows={links}
            />
        </div>
    );
}

