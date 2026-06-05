import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PriceListForm } from '../components/PricingComponents';

export function PriceListCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Pricing" subtitle="Create a price list. Currency, dates, party scope, and resolver priority are validated by the backend." title="Create Price List" />
            <PriceListForm mode="create" />
        </div>
    );
}
