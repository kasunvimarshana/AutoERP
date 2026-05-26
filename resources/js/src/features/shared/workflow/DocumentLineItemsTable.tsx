import { EmptyState } from '../../../components/feedback/EmptyState';
import { ContentCard } from '../../../components/ui/ContentCard';
import { DataTable, type DataTableColumn } from '../../../components/tables';

type DocumentLineItemsTableProps<T> = {
    title: string;
    description: string;
    rows: T[];
    columns: DataTableColumn<T>[];
    getRowKey: (row: T) => string | number;
    emptyTitle?: string;
    emptyDescription?: string;
};

export function DocumentLineItemsTable<T>({
    title,
    description,
    rows,
    columns,
    getRowKey,
    emptyTitle = 'No line items available',
    emptyDescription = 'The current backend resource does not return line-item rows for this document.',
}: DocumentLineItemsTableProps<T>) {
    return (
        <ContentCard className="p-0">
            <div className="border-b border-stone-200/80 px-6 py-5">
                <h3 className="text-lg font-semibold text-stone-950">{title}</h3>
                <p className="mt-1 text-sm leading-6 text-stone-600">{description}</p>
            </div>
            <DataTable
                columns={columns}
                emptyState={<EmptyState className="m-6" description={emptyDescription} title={emptyTitle} />}
                getRowKey={getRowKey}
                rows={rows}
            />
        </ContentCard>
    );
}
