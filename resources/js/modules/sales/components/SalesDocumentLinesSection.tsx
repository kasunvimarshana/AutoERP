import { PurchaseOrderLineEditor } from '@/modules/purchase/components/PurchaseOrderLineEditor';
import { Panel } from '@/shared/components/Panel';
import type { EditableSalesLine } from './salesDocumentFormUtils';

interface Props {
    lines: EditableSalesLine[];
    onChange: (lines: EditableSalesLine[]) => void;
    errorFor: (name: string) => string | undefined;
}

export function SalesDocumentLinesSection({ lines, onChange, errorFor }: Props) {
    return (
        <Panel title="Lines">
            <PurchaseOrderLineEditor lines={lines} onChange={onChange} errorFor={errorFor} />
        </Panel>
    );
}
