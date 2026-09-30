import { DetailGrid } from '@/shared/components/DetailGrid';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { readableRelation } from '@/shared/utils/object';
import { subtractDecimal } from '@/shared/utils/decimal';
import type { Item } from '../itemTypes';

export function ItemSummaryCard({ item }: { item: Item }) {
    const currency = item.tenant_base_currency?.code ?? 'LKR';
    const profitRows = [
        { label: 'Selling', price: item.resolved_sales_unit_price },
        { label: 'Service', price: item.resolved_service_unit_price },
    ];

    return <>
        {item.item_type === 'stock' && <section className="mb-6" aria-labelledby="item-profit-heading">
            <h2 id="item-profit-heading" className="mb-3 text-sm font-semibold text-slate-900">Profit by price type</h2>
            <div className="overflow-x-auto rounded-lg border border-slate-200">
                <table className="w-full min-w-[36rem] text-left text-sm">
                    <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th className="px-4 py-3 font-semibold">Price type</th>
                            <th className="px-4 py-3 font-semibold">Price</th>
                            <th className="px-4 py-3 font-semibold">Profit / base unit</th>
                            <th className="px-4 py-3 font-semibold">Margin</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {profitRows.map(({ label, price }) => {
                            const profit = price !== null && price !== undefined && item.resolved_purchase_unit_price !== null && item.resolved_purchase_unit_price !== undefined
                                ? subtractDecimal(price, item.resolved_purchase_unit_price)
                                : null;
                            const margin = profit !== null && Number(price) !== 0
                                ? `${((Number(profit) / Number(price)) * 100).toFixed(2)}%`
                                : null;

                            return <tr key={label}>
                                <th scope="row" className="px-4 py-3 font-medium text-slate-800">{label}</th>
                                <td className="px-4 py-3 text-slate-700">{price === null || price === undefined ? 'Not set' : <MoneyDisplay value={price} currency={currency} />}</td>
                                <td className="px-4 py-3 text-slate-700">{profit === null ? 'Set purchase price to calculate' : <MoneyDisplay value={profit} currency={currency} />}</td>
                                <td className="px-4 py-3 text-slate-700">{margin ?? '—'}</td>
                            </tr>;
                        })}
                    </tbody>
                </table>
            </div>
            <p className="mt-2 text-xs text-slate-500">Based on current prices in {readableRelation(item.base_uom)} and {currency}. Margin = profit ÷ price.</p>
        </section>}
        <DetailGrid items={[
        { label: 'Status', value: <StatusBadge status={item.is_active ? 'active' : 'inactive'} /> },
        { label: 'Type', value: item.item_type },
        { label: 'Tracking', value: item.tracking_type },
        { label: 'Costing', value: item.costing_method },
        { label: 'Category', value: readableRelation(item.category) },
        { label: 'Brand', value: readableRelation(item.brand) },
        { label: 'Base UOM', value: readableRelation(item.base_uom) },
        { label: 'Standard Currency', value: readableRelation(item.tenant_base_currency) },
        { label: 'SKU', value: item.sku },
        { label: 'Barcode', value: item.barcode },
        { label: 'Stockable', value: item.is_stockable ? 'Yes' : 'No' },
        { label: 'Combo/package', value: item.is_combo ? 'Yes' : 'No' },
        { label: 'Description', value: item.description },
        ]} />
    </>;
}
