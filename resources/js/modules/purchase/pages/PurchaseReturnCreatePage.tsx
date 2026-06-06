import { useState } from 'react';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import { ManualSupplierReturnForm } from '../components/ManualSupplierReturnForm';
import { PurchaseDebitNoteForm } from '../components/PurchaseDebitNoteForm';
import { PurchaseInventoryAdjustmentRequestForm } from '../components/PurchaseInventoryAdjustmentRequestForm';
import { PurchaseReturnForm } from '../components/PurchaseReturnForm';

type Tab = 'referenced' | 'manual' | 'debit' | 'adjustment';

export default function PurchaseReturnCreatePage() {
    const [tab, setTab] = useState<Tab>('referenced');
    return (
        <div className="space-y-5">
            <ContentHeader title="Purchase return decision" />
            <Panel>
                <Tabs
                    active={tab}
                    onChange={setTab}
                    tabs={[
                        { id: 'referenced', label: 'Referenced return' },
                        { id: 'manual', label: 'Manual supplier return' },
                        { id: 'debit', label: 'Debit note only' },
                        { id: 'adjustment', label: 'Inventory adjustment only' },
                    ]}
                />
                <div className="pt-5">
                    {tab === 'referenced' && <PurchaseReturnForm />}
                    {tab === 'manual' && <ManualSupplierReturnForm />}
                    {tab === 'debit' && <PurchaseDebitNoteForm />}
                    {tab === 'adjustment' && <PurchaseInventoryAdjustmentRequestForm />}
                </div>
            </Panel>
        </div>
    );
}
