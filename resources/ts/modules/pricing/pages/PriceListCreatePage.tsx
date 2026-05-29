import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PriceListForm } from '../components/PricingComponents';

export function PriceListCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Pricing" subtitle="Create a price list and optional price item setup. Backend will later validate currency, dates, party scope, and price resolving." title="Create Price List" />
            <PriceListForm mode="create" />
        </div>
    );
}
