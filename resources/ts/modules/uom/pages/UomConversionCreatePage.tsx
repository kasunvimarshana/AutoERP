import { PageHeader } from '../../../shared/components/business/PageHeader';
import { UomConversionForm } from '../components/UomComponents';

export function UomConversionCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="UOM" subtitle="Create a conversion definition. Use preview to request backend/mock converted output." title="Create Conversion" />
            <UomConversionForm mode="create" />
        </div>
    );
}
