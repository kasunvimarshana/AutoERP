import { Link } from 'react-router-dom';
import { MasterDataWorkspace } from '../../../shared/components/business/MasterDataWorkspace';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { items } from '../mock/itemMock';
import type { Item } from '../types/item.types';

export function ItemPage() {
    return (
        <MasterDataWorkspace<Item>
            backendNotes={['Item type rules', 'UOM compatibility', 'Combo circular checks', 'Nested transaction save']}
            columns={[
                { header: 'Code', key: 'code', render: (row) => <Link className="font-semibold text-slate-950" to={`/items/${row.id}`}>{row.code}</Link> },
                { header: 'Name', key: 'name' },
                { header: 'Type', key: 'type' },
                { header: 'UOM', key: 'uom' },
                { header: 'Stock Mode', key: 'stockMode' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            createPath="/items/new"
            description="Items, categories, variants, attributes, combo items, units, pricing references, and metadata."
            fields={[
                { label: 'Item name', name: 'name', placeholder: 'Item, service, or combo name' },
                { label: 'Item type', name: 'type', options: [{ label: 'Stock Item', value: 'stock' }, { label: 'Service Item', value: 'service' }, { label: 'Combo', value: 'combo' }], placeholder: 'Select type', type: 'select' },
                { label: 'Category', name: 'category', placeholder: 'Select category', type: 'select' },
                { help: 'Backend validates unit compatibility and conversion rules.', label: 'Base UOM', name: 'uom', placeholder: 'Select UOM', type: 'select' },
                { help: 'Frontend captures components only; backend validates combo expansion.', label: 'Combo / bundle notes', name: 'comboNotes', placeholder: 'Optional component notes', type: 'textarea' },
            ]}
            listPath="/items"
            previewTitle="Item backend preview"
            rows={items}
            sections={[
                { title: 'Type Indicators', description: 'Stockable, service, labour, non-inventory, and combo behavior is shown clearly.' },
                { title: 'Units', description: 'Frontend captures units; backend validates UOM compatibility and conversion.' },
                { title: 'Combo Components', description: 'Frontend captures requested components; backend validates expansion and circular references.' },
                { title: 'Pricing References', description: 'Pricing is referenced only; backend resolves final price and discounts.' },
            ]}
            title="Items"
        />
    );
}
