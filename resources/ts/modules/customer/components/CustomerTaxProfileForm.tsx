import { FormSection } from '../../../shared/components/forms/FormSection';
import { Input } from '../../../shared/components/ui/Input';
import type { CustomerTaxProfile } from '../types/customer.types';

export function CustomerTaxProfileForm({ profile }: { profile: CustomerTaxProfile }) {
    return (
        <FormSection description="Readonly backend response. Tax treatment is validated by backend tax/profile rules." title="Tax Profile">
            <div className="grid gap-4 md:grid-cols-3">
                <Input readOnly value={profile.taxStatus} />
                <Input readOnly value={profile.taxGroup || 'Not configured'} />
                <Input readOnly value={profile.taxRegistrationNumber || 'Not configured'} />
            </div>
        </FormSection>
    );
}
