import { useEffect, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { itemApi } from '../services/itemApi';
import type { ItemAttribute, ItemCategory, ItemComboComponent, ItemIdentifier, ItemVariant } from '../types/item.types';
import { ItemComboComponentsTable, ItemIdentifiersTable, ItemVariantsTable } from '../components/ItemPanels';

export function ItemCategoryListPage() {
    const [rows, setRows] = useState<ItemCategory[]>([]);
    useEffect(() => { itemApi.listCategories().then((response) => setRows(response.data)); }, []);
    return <div className="space-y-6"><PageHeader eyebrow="Items" subtitle="Categories organize items across purchase, sales, service, rental, and inventory." title="Item Categories" />{rows.length ? <DataTable columns={[{ header: 'Code', key: 'code' }, { header: 'Name', key: 'name' }, { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> }]} getRowKey={(row) => row.id} rows={rows} /> : <EmptyState description="No categories returned yet." title="No item categories" />}</div>;
}

export function ItemAttributeListPage() {
    const [rows, setRows] = useState<ItemAttribute[]>([]);
    useEffect(() => { itemApi.listAttributes().then((response) => setRows(response.data)); }, []);
    return <div className="space-y-6"><PageHeader eyebrow="Items" subtitle="Attributes describe item options. Backend validates variant usage." title="Item Attributes" />{rows.length ? <DataTable columns={[{ header: 'Name', key: 'name' }, { header: 'Group', key: 'group' }, { header: 'Type', key: 'type' }]} getRowKey={(row) => row.id} rows={rows} /> : <EmptyState description="No attributes returned yet." title="No item attributes" />}</div>;
}

export function ItemVariantListPage() {
    const [rows, setRows] = useState<ItemVariant[]>([]);
    useEffect(() => { itemApi.listVariants('item-001').then((response) => setRows(response.data)); }, []);
    return <div className="space-y-6"><PageHeader eyebrow="Items" subtitle="Variant records are SKU-level item options. Values are backend validated." title="Item Variants" /><ItemVariantsTable variants={rows} /></div>;
}

export function ItemComboListPage() {
    const [rows, setRows] = useState<ItemComboComponent[]>([]);
    useEffect(() => { itemApi.listComboComponents('item-004').then((response) => setRows(response.data)); }, []);
    return <div className="space-y-6"><PageHeader eyebrow="Items" subtitle="Combo components are captured by frontend and expanded/validated by backend." title="Combo / Bundle Components" /><ItemComboComponentsTable components={rows} /></div>;
}

export function ItemIdentifierListPage() {
    const [rows, setRows] = useState<ItemIdentifier[]>([]);
    useEffect(() => { itemApi.listIdentifiers('item-001').then((response) => setRows(response.data)); }, []);
    return <div className="space-y-6"><PageHeader eyebrow="Items" subtitle="Identifiers include barcodes, internal codes, and manufacturer references." title="Item Identifiers" /><ItemIdentifiersTable identifiers={rows} /></div>;
}
