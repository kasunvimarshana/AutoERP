import { MasterDataWorkspace } from '../../../shared/components/business/MasterDataWorkspace';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { priceRules } from '../mock/pricingMock';
import type { PriceRule } from '../types/pricing.types';

export function PricingPage() {
    return (
        <div className="space-y-6">
            <MasterDataWorkspace<PriceRule>
                backendNotes={['Price resolving', 'Tier priority', 'Discount logic', 'Currency, UOM, and effective date rules']}
                columns={[
                    { header: 'Rule', key: 'name' },
                    { header: 'Scope', key: 'scope' },
                    { header: 'Priority', key: 'priority' },
                    { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                ]}
                createPath="/pricing/rules"
                description="Price lists, price items, rules, tiers, discounts, resolver previews, and history."
                fields={[
                    { label: 'Rule name', name: 'name', placeholder: 'Fleet customer discount' },
                    { label: 'Scope', name: 'scope', placeholder: 'Select module scope', type: 'select' },
                    { help: 'Backend resolves priority; frontend only captures rule input.', label: 'Priority', name: 'priority', placeholder: '100' },
                    { help: 'Backend evaluates conditions against transaction context.', label: 'Conditions', name: 'conditions', placeholder: 'Condition notes', type: 'textarea' },
                ]}
                listPath="/pricing"
                previewTitle="Pricing backend preview"
                rows={priceRules}
                title="Pricing"
            />
            <PreviewPanel
                rows={[
                    { label: 'Price resolver', value: 'Backend-owned price result placeholder' },
                    { label: 'Discount preview', value: 'Backend-owned discount result placeholder' },
                ]}
                title="Price Resolver Preview"
            />
        </div>
    );
}
