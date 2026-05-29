import { PageHeader } from '../../../shared/components/business/PageHeader';
import { UomUnitForm } from '../components/UomComponents';

export function UomUnitCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="UOM" subtitle="Create the unit identity and usage settings. Conversion effects are backend-owned and configured separately." title="Create Unit" />
            <UomUnitForm mode="create" />
        </div>
    );
}
