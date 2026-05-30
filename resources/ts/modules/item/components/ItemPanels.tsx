import { AuditTimeline } from '../../../shared/components/business/AuditTimeline';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import type {
    ItemAttribute,
    ItemAuditEntry,
    ItemComboComponent,
    ItemIdentifier,
    ItemInventorySummary,
    ItemPricingReference,
    ItemUnit,
    ItemUsageSummary,
    ItemVariant,
} from '../types/item.types';
import { ItemTypeBadge } from './ItemBadges';

export function ItemUnitsPanel({ units }: { units: ItemUnit[] }) {
    return <DataTable columns={[
        { header: 'Unit', key: 'unit' },
        { header: 'Purpose', key: 'purpose' },
        { header: 'Base', key: 'isBase', render: (row) => <StatusBadge status={row.isBase ? 'Base' : 'Conversion'} /> },
    ]} getRowKey={(row) => row.id} rows={units} />;
}

export function ItemAttributesTable({ attributes }: { attributes: ItemAttribute[] }) {
    if (!attributes.length) return <EmptyState description="No attributes configured for this item yet." title="No attributes" />;
    return <DataTable columns={[
        { header: 'Name', key: 'name' },
        { header: 'Group', key: 'group' },
        { header: 'Type', key: 'type' },
    ]} getRowKey={(row) => row.id} rows={attributes} />;
}

export function ItemVariantsTable({ variants }: { variants: ItemVariant[] }) {
    if (!variants.length) return <EmptyState description="Variants can be added where item setup requires SKU-level options." title="No variants" />;
    return <DataTable columns={[
        { header: 'SKU', key: 'sku' },
        { header: 'Variant', key: 'name' },
        { header: 'Attributes', key: 'attributes', render: (row) => row.attributes.map((attribute) => `${attribute.attribute}: ${attribute.value}`).join(', ') || 'No attributes' },
        { header: 'Status', key: 'isActive', render: (row) => <StatusBadge status={row.isActive ? 'Active' : 'Inactive'} /> },
    ]} getRowKey={(row) => row.id} rows={variants} />;
}

export function ItemComboComponentsTable({ components }: { components: ItemComboComponent[] }) {
    if (!components.length) return <EmptyState description="Combo components are validated and expanded by the backend." title="No combo components" />;
    return <DataTable columns={[
        { header: 'Component', key: 'componentItemName' },
        { header: 'Type', key: 'componentType', render: (row) => <ItemTypeBadge type={row.componentType} /> },
        { header: 'Quantity', key: 'quantity' },
        { header: 'UOM', key: 'uom' },
        { header: 'Stock Impact', key: 'stockImpact' },
    ]} getRowKey={(row) => row.id} rows={components} />;
}

export function ItemIdentifiersTable({ identifiers }: { identifiers: ItemIdentifier[] }) {
    if (!identifiers.length) return <EmptyState description="Barcodes, internal references, and manufacturer identifiers can be added later." title="No identifiers" />;
    return <DataTable columns={[
        { header: 'Type', key: 'type' },
        { header: 'Value', key: 'value' },
    ]} getRowKey={(row) => row.id} rows={identifiers} />;
}

export function ItemPricingReferencesPanel({ references }: { references: ItemPricingReference[] }) {
    if (!references.length) return <EmptyState description="Pricing is resolved by backend pricing rules; no references are returned yet." title="No pricing references" />;
    return <DataTable columns={[
        { header: 'Price List', key: 'priceList' },
        { header: 'Currency', key: 'currency' },
        { header: 'Backend Resolver', key: 'resolvedByBackend' },
    ]} getRowKey={(row) => row.id} rows={references} />;
}

export function ItemInventorySummaryPanel({ summary }: { summary: ItemInventorySummary }) {
    return <PreviewPanel rows={[
        { label: 'Availability', value: summary.availability },
        { label: 'Stock on hand', value: summary.stockOnHand },
        { label: 'Cost', value: summary.costPreview },
        { label: 'Valuation', value: summary.valuation },
    ]} status="Backend-owned" subtitle="The frontend does not calculate stock, cost, or valuation." title="Inventory Summary" />;
}

export function ItemUsagePanel({ summary }: { summary: ItemUsageSummary }) {
    return <PreviewPanel rows={[
        { label: 'Receipt', value: summary.receiptUse },
        { label: 'Issue', value: summary.issueUse },
        { label: 'Consumption', value: summary.consumptionUse },
        { label: 'Charge', value: summary.chargeUse },
        { label: 'Inventory', value: summary.inventoryUse },
    ]} status="Readonly" subtitle="Usage is shown from backend/mock summaries only." title="Usage Summary" />;
}

export function ItemActivityTimeline({ entries }: { entries: ItemAuditEntry[] }) {
    return <AuditTimeline events={entries.map((entry) => ({ actor: entry.actor, description: entry.description, time: entry.time }))} />;
}
