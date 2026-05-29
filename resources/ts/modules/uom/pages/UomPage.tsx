import { MasterDataWorkspace } from '../../../shared/components/business/MasterDataWorkspace';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { units } from '../mock/uomMock';
import type { Unit } from '../types/uom.types';

export function UomPage() {
    return (
        <div className="space-y-6">
            <MasterDataWorkspace<Unit>
                backendNotes={['Conversion compatibility', 'Precision and rounding', 'Tenant-scoped unit validation']}
                columns={[
                    { header: 'Code', key: 'code' },
                    { header: 'Name', key: 'name' },
                    { header: 'Category', key: 'category' },
                    { header: 'Precision', key: 'precision' },
                    { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                ]}
                createPath="/uom/units"
                description="UOM categories, units, conversions, compatibility checks, precision, and backend conversion previews."
                fields={[
                    { label: 'Unit code', name: 'code', placeholder: 'PCS' },
                    { label: 'Unit name', name: 'name', placeholder: 'Pieces' },
                    { label: 'Category', name: 'category', placeholder: 'Quantity', type: 'select' },
                    { help: 'Backend applies rounding rules during conversion.', label: 'Precision', name: 'precision', placeholder: '0' },
                ]}
                listPath="/uom"
                previewTitle="UOM backend preview"
                rows={units}
                title="UOM"
            />
            <PreviewPanel
                rows={[
                    { label: 'Conversion preview', value: 'Backend-owned conversion result placeholder' },
                    { label: 'Compatibility', value: 'Backend validates category compatibility' },
                ]}
                title="Conversion Preview"
            />
        </div>
    );
}
