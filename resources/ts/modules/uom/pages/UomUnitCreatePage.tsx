import { PageHeader } from '../../../shared/components/business/PageHeader';
import { UomUnitForm } from '../components/UomComponents';

export function UomUnitCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="UOM" subtitle="Create the unit identity and usage settings. Conversion effects are configured separately through the UOM API." title="Create Unit" />
            <UomUnitForm mode="create" />
        </div>
    );
}
