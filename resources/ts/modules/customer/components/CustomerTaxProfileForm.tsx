import { FormSection } from '../../../shared/components/forms/FormSection';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import type { CustomerTaxProfile } from '../types/customer.types';

export function CustomerTaxProfileForm({ profile }: { profile: CustomerTaxProfile }) {
    return (
        <FormSection description="Tax treatment will be validated by backend tax/profile rules during integration." title="Tax Profile">
            <div className="grid gap-4 md:grid-cols-3">
                <Select
                    defaultValue={profile.taxStatus}
                    options={[
                        { label: 'Registered', value: 'registered' },
                        { label: 'Unregistered', value: 'unregistered' },
                        { label: 'Exempt', value: 'exempt' },
                    ]}
                    placeholder="Tax status"
                />
                <Input defaultValue={profile.taxGroup} placeholder="Tax group" />
                <Input defaultValue={profile.taxRegistrationNumber} placeholder="Tax registration number" />
            </div>
        </FormSection>
    );
}
