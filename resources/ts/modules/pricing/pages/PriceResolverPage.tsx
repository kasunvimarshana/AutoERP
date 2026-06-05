import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PriceResolverForm } from '../components/PricingComponents';

export function PriceResolverPage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Pricing Preview" subtitle="Submit module, party, item, UOM, date, quantity, and currency context to the backend resolver. Results are readonly." title="Price Resolver" />
            <PriceResolverForm />
        </div>
    );
}
