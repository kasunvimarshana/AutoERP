import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PricingRuleForm } from '../components/PricingComponents';

export function PricingRuleCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Pricing Rule" subtitle="Create resolver rules and conditions. Backend will evaluate priority, stacking, exclusions, discounts, and tiers." title="Create Pricing Rule" />
            <PricingRuleForm mode="create" />
        </div>
    );
}
