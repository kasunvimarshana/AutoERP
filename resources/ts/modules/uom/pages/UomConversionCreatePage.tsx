import { PageHeader } from '../../../shared/components/business/PageHeader';
import { UomConversionForm } from '../components/UomComponents';

export function UomConversionCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="UOM" subtitle="Create a conversion definition. Preview requests the backend conversion service." title="Create Conversion" />
            <UomConversionForm mode="create" />
        </div>
    );
}
