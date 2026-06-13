import { PurchaseHeaderAdjustmentEditor } from '@/modules/purchase/components/PurchaseHeaderAdjustmentEditor';
import { Panel } from '@/shared/components/Panel';
import type { EditableSalesAdjustment } from './salesDocumentFormUtils';

interface Props {
    adjustments: EditableSalesAdjustment[];
    onChange: (adjustments: EditableSalesAdjustment[]) => void;
    errorFor: (name: string) => string | undefined;
}

export function SalesDocumentAdjustmentSection({
    adjustments,
    onChange,
    errorFor,
}: Props) {
    return (
        <Panel title="Header adjustments">
            <PurchaseHeaderAdjustmentEditor
                adjustments={adjustments}
                onChange={onChange}
                errorFor={errorFor}
            />
        </Panel>
    );
}
