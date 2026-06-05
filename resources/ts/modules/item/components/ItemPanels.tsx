import { AuditTimeline } from '../../../shared/components/business/AuditTimeline';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import type {
    ItemAttribute,
    ItemAuditEntry,
    ItemCapabilitySummary,
    ItemComboComponent,
    ItemIdentifier,
    ItemInventorySummary,
    ItemUnit,
    ItemUsageSummary,
    ItemVariant,
} from '../types/item.types';
import { ItemTypeBadge } from './ItemBadges';
import { DeleteItemSubResourceButton } from './ItemSubResourceForms';

export function ItemUnitsPanel({ units }: { units: ItemUnit[] }) {
    return <DataTable columns={[
        { header: 'Unit', key: 'unit' },
        { header: 'Purpose', key: 'purpose' },
        { header: 'Base', key: 'isBase', render: (row) => <StatusBadge status={row.isBase ? 'Base' : 'Conversion'} /> },
    ]} getRowKey={(row) => row.id} rows={units} />;
}

export function ItemAttributesTable({ attributes }: { attributes: ItemAttribute[] }) {
    if (!attributes.length) return <EmptyState description="No attributes are configured for this item." title="No attributes" />;
    return <DataTable columns={[
        { header: 'Name', key: 'name' },
        { header: 'Group', key: 'group' },
        { header: 'Type', key: 'type' },
    ]} getRowKey={(row) => row.id} rows={attributes} />;
}

export function ItemVariantsTable({ onDelete, variants }: { onDelete?: (row: ItemVariant) => Promise<void>; variants: ItemVariant[] }) {
    if (!variants.length) return <EmptyState description="Variants can be added where item setup requires SKU-level options." title="No variants" />;
    return <DataTable columns={[
        { header: 'SKU', key: 'sku' },
        { header: 'Variant', key: 'name' },
        { header: 'Attributes', key: 'attributes', render: (row) => row.attributes.map((attribute) => `${attribute.attribute}: ${attribute.value}`).join(', ') || 'No attributes' },
        { header: 'Status', key: 'isActive', render: (row) => <StatusBadge status={row.isActive ? 'Active' : 'Inactive'} /> },
        ...(onDelete ? [{ header: 'Actions', key: 'id' as keyof ItemVariant, render: (row: ItemVariant) => <DeleteItemSubResourceButton label={row.name} onDelete={() => onDelete(row)} /> }] : []),
    ]} getRowKey={(row) => row.id} rows={variants} />;
}

export function ItemComboComponentsTable({ components, onDelete }: { components: ItemComboComponent[]; onDelete?: (row: ItemComboComponent) => Promise<void> }) {
    if (!components.length) return <EmptyState description="Combo components are validated and expanded by the backend." title="No combo components" />;
    return <DataTable columns={[
        { header: 'Component', key: 'componentItemName' },
        { header: 'Type', key: 'componentType', render: (row) => <ItemTypeBadge type={row.componentType} /> },
        { header: 'Quantity', key: 'quantity' },
        { header: 'UOM', key: 'uom' },
        { header: 'Stock Impact', key: 'stockImpact' },
        ...(onDelete ? [{ header: 'Actions', key: 'id' as keyof ItemComboComponent, render: (row: ItemComboComponent) => <DeleteItemSubResourceButton label={row.componentItemName} onDelete={() => onDelete(row)} /> }] : []),
    ]} getRowKey={(row) => row.id} rows={components} />;
}

export function ItemIdentifiersTable({ identifiers, onDelete }: { identifiers: ItemIdentifier[]; onDelete?: (row: ItemIdentifier) => Promise<void> }) {
    if (!identifiers.length) return <EmptyState description="Barcodes, internal references, and manufacturer identifiers can be added later." title="No identifiers" />;
    return <DataTable columns={[
        { header: 'Type', key: 'type' },
        { header: 'Value', key: 'value' },
        ...(onDelete ? [{ header: 'Actions', key: 'id' as keyof ItemIdentifier, render: (row: ItemIdentifier) => <DeleteItemSubResourceButton label={row.value} onDelete={() => onDelete(row)} /> }] : []),
    ]} getRowKey={(row) => row.id} rows={identifiers} />;
}

export function ItemInventorySummaryPanel({ summary }: { summary: ItemInventorySummary }) {
    return <PreviewPanel rows={[
        { label: 'Stockable', value: summary.isStockable ? 'Yes' : 'No' },
        { label: 'Quantity on hand', value: summary.quantityOnHand },
        { label: 'Reserved quantity', value: summary.quantityReserved },
        { label: 'Available quantity', value: summary.availableQuantity },
        { label: 'Stock level records', value: String(summary.stockLevelCount) },
        { label: 'Minimum stock', value: summary.minimumStock },
        { label: 'Reorder point', value: summary.reorderPoint },
        { label: 'Safety stock', value: summary.safetyStock },
        { label: 'Valuation method', value: summary.valuationMethod ?? 'Not configured' },
        { label: 'Standard cost', value: summary.standardCost ?? 'Not configured' },
    ]} status={summary.isStockable ? 'Stockable' : 'No stock impact'} subtitle="Values are returned by Item and Inventory-backed API data." title="Inventory Summary" />;
}

export function ItemUsagePanel({ summary }: { summary: ItemUsageSummary }) {
    return <ItemCapabilityPanel capabilities={summary.capabilities} />;
}

export function ItemCapabilityPanel({ capabilities }: { capabilities: ItemCapabilitySummary }) {
    const rows = [
        { flag: capabilities.stockable, metric: 'Stockable' },
        { flag: capabilities.affectsInventory, metric: 'Affects inventory' },
        { flag: capabilities.purchasable, metric: 'Purchasable' },
        { flag: capabilities.sellable, metric: 'Sellable' },
        { flag: capabilities.serviceUsable, metric: 'Service usable' },
        { flag: capabilities.rentalUsable, metric: 'Rental usable' },
        { flag: capabilities.batchTracking, metric: 'Batch tracking' },
        { flag: capabilities.serialTracking, metric: 'Serial tracking' },
        { flag: capabilities.hasVariants, metric: 'Has variants' },
        { flag: capabilities.hasComboComponents, metric: 'Has combo components' },
        { flag: capabilities.hasIdentifiers, metric: 'Has identifiers' },
        { flag: capabilities.uomConfigured, metric: 'UOM configured' },
    ];

    return (
        <div className="space-y-4">
            <PreviewPanel rows={[
                { label: 'Inventory references', value: String(capabilities.inventoryReferencesCount) },
            ]} status="API" subtitle="Capability flags and counts come from the Item API." title="Item Usage & Capability" />
            <DataTable columns={[
                { header: 'Capability', key: 'metric' },
                { header: 'Enabled', key: 'flag', render: (row) => <StatusBadge status={row.flag ? 'Yes' : 'No'} /> },
            ]} getRowKey={(row) => row.metric} rows={rows} />
        </div>
    );
}

export function ItemActivityTimeline({ entries }: { entries: ItemAuditEntry[] }) {
    if (!entries.length) return <EmptyState description="No audit/activity entries were returned for this item." title="No item activity" />;

    return <AuditTimeline events={entries.map((entry) => ({ actor: entry.actor, description: entry.description, time: entry.time }))} />;
}
