import { DataTable } from '../../../shared/components/data/DataTable';
import type { OrderItem } from '../types/vehicleService.types';

type OrderItemsTableProps = {
    items: OrderItem[];
};

export function OrderItemsTable({ items }: OrderItemsTableProps) {
    return (
        <DataTable
            columns={[
                { header: 'Product', key: 'product', render: (row) => <span className="font-semibold text-slate-600">{row.product}</span> },
                { header: 'Quantity', key: 'quantity' },
                { header: 'Net Unit Price', key: 'netUnitPrice' },
                { header: 'Discount', key: 'discountLabel' },
                { header: 'Sub Total', key: 'subTotal', render: (row) => <span className="font-bold text-slate-950">{row.subTotal}</span> },
            ]}
            getRowKey={(row) => row.id}
            rows={items}
        />
    );
}
