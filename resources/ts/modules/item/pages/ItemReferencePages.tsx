import { useEffect, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { itemApi } from '../services/itemApi';
import type { ItemAttribute, ItemCategory, ItemComboComponent, ItemIdentifier, ItemVariant } from '../types/item.types';
import { ItemComboComponentsTable, ItemIdentifiersTable, ItemVariantsTable } from '../components/ItemPanels';
import { DeleteItemSubResourceButton, ItemAttributeCreateForm, ItemCategoryCreateForm } from '../components/ItemSubResourceForms';

function ReferenceState<T>({ error, isLoading, rows, title }: { error: string; isLoading: boolean; rows: T[]; title: string }) {
    if (isLoading) {
        return <EmptyState description={`Loading ${title.toLowerCase()} from the Item API...`} title={`Loading ${title}`} />;
    }

    if (error) {
        return <EmptyState description={error} title={`Unable to load ${title.toLowerCase()}`} />;
    }

    if (!rows.length) {
        return <EmptyState description={`No ${title.toLowerCase()} records were returned by the backend.`} title={`No ${title}`} />;
    }

    return null;
}

export function ItemCategoryListPage() {
    const [rows, setRows] = useState<ItemCategory[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [reloadKey, setReloadKey] = useState(0);

    useEffect(() => {
        let mounted = true;
        itemApi.listCategories()
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load item categories.'); })
            .finally(() => { if (mounted) setIsLoading(false); });

        return () => { mounted = false; };
    }, [reloadKey]);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Items" subtitle="Categories organize reusable item master data." title="Item Categories" />
            <ItemCategoryCreateForm onSaved={() => setReloadKey((value) => value + 1)} />
            <ReferenceState error={error} isLoading={isLoading} rows={rows} title="item categories" />
            {!isLoading && !error && rows.length ? (
                <DataTable columns={[
                    { header: 'Code', key: 'code' },
                    { header: 'Name', key: 'name' },
                    { header: 'Description', key: 'description', render: (row) => row.description || 'Not provided' },
                    { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                    { header: 'Actions', key: 'id', render: (row) => <DeleteItemSubResourceButton label={row.name} onDelete={async () => { await itemApi.deleteCategory(row.id); setReloadKey((value) => value + 1); }} /> },
                ]} getRowKey={(row) => row.id} rows={rows} />
            ) : null}
        </div>
    );
}

export function ItemAttributeListPage() {
    const [rows, setRows] = useState<ItemAttribute[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [reloadKey, setReloadKey] = useState(0);

    useEffect(() => {
        let mounted = true;
        itemApi.listAttributes()
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load item attributes.'); })
            .finally(() => { if (mounted) setIsLoading(false); });

        return () => { mounted = false; };
    }, [reloadKey]);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Items" subtitle="Attributes describe reusable item options and are validated by the Item API." title="Item Attributes" />
            <ItemAttributeCreateForm onSaved={() => setReloadKey((value) => value + 1)} />
            <ReferenceState error={error} isLoading={isLoading} rows={rows} title="item attributes" />
            {!isLoading && !error && rows.length ? (
                <DataTable columns={[
                    { header: 'Name', key: 'name' },
                    { header: 'Group', key: 'group', render: (row) => row.group || 'Ungrouped' },
                    { header: 'Type', key: 'type' },
                    { header: 'Actions', key: 'id', render: (row) => <DeleteItemSubResourceButton label={row.name} onDelete={async () => { await itemApi.deleteAttribute(row.id); setReloadKey((value) => value + 1); }} /> },
                ]} getRowKey={(row) => row.id} rows={rows} />
            ) : null}
        </div>
    );
}

export function ItemVariantListPage() {
    const [rows, setRows] = useState<ItemVariant[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [reloadKey, setReloadKey] = useState(0);

    useEffect(() => {
        let mounted = true;
        itemApi.listVariants()
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load item variants.'); })
            .finally(() => { if (mounted) setIsLoading(false); });

        return () => { mounted = false; };
    }, [reloadKey]);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Items" subtitle="Variant records are SKU-level item options." title="Item Variants" />
            <ReferenceState error={error} isLoading={isLoading} rows={rows} title="item variants" />
            {!isLoading && !error && rows.length ? <ItemVariantsTable onDelete={async (row) => { await itemApi.deleteVariant(row.id); setReloadKey((value) => value + 1); }} variants={rows} /> : null}
        </div>
    );
}

export function ItemComboListPage() {
    const [rows, setRows] = useState<ItemComboComponent[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [reloadKey, setReloadKey] = useState(0);

    useEffect(() => {
        let mounted = true;
        itemApi.listComboComponents()
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load combo components.'); })
            .finally(() => { if (mounted) setIsLoading(false); });

        return () => { mounted = false; };
    }, [reloadKey]);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Items" subtitle="Combo components are captured as setup and expanded by backend services." title="Combo / Bundle Components" />
            <ReferenceState error={error} isLoading={isLoading} rows={rows} title="combo components" />
            {!isLoading && !error && rows.length ? <ItemComboComponentsTable components={rows} onDelete={async (row) => { await itemApi.deleteComboComponent(row.id); setReloadKey((value) => value + 1); }} /> : null}
        </div>
    );
}

export function ItemIdentifierListPage() {
    const [rows, setRows] = useState<ItemIdentifier[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [reloadKey, setReloadKey] = useState(0);

    useEffect(() => {
        let mounted = true;
        itemApi.listIdentifiers()
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load item identifiers.'); })
            .finally(() => { if (mounted) setIsLoading(false); });

        return () => { mounted = false; };
    }, [reloadKey]);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Items" subtitle="Identifiers include barcodes, QR codes, RFID, and external references." title="Identifiers / Barcodes" />
            <ReferenceState error={error} isLoading={isLoading} rows={rows} title="item identifiers" />
            {!isLoading && !error && rows.length ? <ItemIdentifiersTable identifiers={rows} onDelete={async (row) => { await itemApi.deleteIdentifier(row.id); setReloadKey((value) => value + 1); }} /> : null}
        </div>
    );
}
