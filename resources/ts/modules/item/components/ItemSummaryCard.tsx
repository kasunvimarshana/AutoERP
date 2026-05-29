import { Card } from '../../../shared/components/ui/Card';
import type { Item } from '../types/item.types';
import { ItemStockBehaviorBadge, ItemTypeBadge } from './ItemBadges';

export function ItemSummaryCard({ item }: { item: Item }) {
    return (
        <Card className="p-5">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="text-xs font-bold uppercase tracking-widest text-slate-400">{item.code}</p>
                        <ItemTypeBadge type={item.itemType} />
                        <ItemStockBehaviorBadge behavior={item.stockBehavior} />
                    </div>
                    <h2 className="mt-2 text-xl font-bold text-slate-950">{item.name}</h2>
                    <p className="mt-1 text-sm text-slate-500">{item.description || 'No description provided.'}</p>
                </div>
                <div className="grid gap-3 text-sm md:grid-cols-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wide text-slate-400">Category</p>
                        <p className="mt-1 font-semibold text-slate-800">{item.category}</p>
                    </div>
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wide text-slate-400">Base UOM</p>
                        <p className="mt-1 font-semibold text-slate-800">{item.baseUom}</p>
                    </div>
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wide text-slate-400">Updated</p>
                        <p className="mt-1 font-semibold text-slate-800">{item.updatedAt}</p>
                    </div>
                </div>
            </div>
        </Card>
    );
}
