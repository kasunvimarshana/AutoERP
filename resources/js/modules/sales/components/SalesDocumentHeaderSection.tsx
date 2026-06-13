import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import {
    CustomerLookupSelect,
    SalesCurrencyLookupSelect,
    SalesWarehouseLocationLookupSelect,
    SalesWarehouseLookupSelect,
} from './SalesLookups';

type DocumentKind = 'quotation' | 'order';

interface Props {
    kind: DocumentKind;
    customer: NamedResource | null;
    warehouse: NamedResource | null;
    location: NamedResource | null;
    currency: NamedResource | null;
    documentDate: string;
    secondaryDate: string;
    exchangeRate: string;
    notes: string;
    onCustomerChange: (value: NamedResource | null) => void;
    onWarehouseChange: (value: NamedResource | null) => void;
    onLocationChange: (value: NamedResource | null) => void;
    onCurrencyChange: (value: NamedResource | null) => void;
    onDocumentDateChange: (value: string) => void;
    onSecondaryDateChange: (value: string) => void;
    onExchangeRateChange: (value: string) => void;
    onNotesChange: (value: string) => void;
    errorFor: (name: string) => string | undefined;
}

export function SalesDocumentHeaderSection(props: Props) {
    const isQuotation = props.kind === 'quotation';

    return (
        <Panel title={isQuotation ? 'Quotation header' : 'Order header'}>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <CustomerLookupSelect
                    value={props.customer}
                    onChange={props.onCustomerChange}
                    error={props.errorFor('customer_id')}
                />
                {!isQuotation && (
                    <SalesWarehouseLookupSelect
                        value={props.warehouse}
                        onChange={props.onWarehouseChange}
                        error={props.errorFor('warehouse_id')}
                    />
                )}
                {!isQuotation && (
                    <SalesWarehouseLocationLookupSelect
                        warehouseId={props.warehouse?.id}
                        value={props.location}
                        onChange={props.onLocationChange}
                        error={props.errorFor('warehouse_location_id')}
                    />
                )}
                <SalesCurrencyLookupSelect
                    value={props.currency}
                    onChange={props.onCurrencyChange}
                    error={props.errorFor('currency_id')}
                />
                <Input
                    label={isQuotation ? 'Quotation date' : 'Order date'}
                    type="date"
                    value={props.documentDate}
                    error={props.errorFor(isQuotation ? 'quotation_date' : 'sales_order_date')}
                    onChange={(event) => props.onDocumentDateChange(event.target.value)}
                />
                <Input
                    label={isQuotation ? 'Valid until' : 'Expected delivery'}
                    type="date"
                    value={props.secondaryDate}
                    error={props.errorFor(
                        isQuotation ? 'valid_until' : 'expected_delivery_date',
                    )}
                    onChange={(event) => props.onSecondaryDateChange(event.target.value)}
                />
                <DecimalInput
                    label="Exchange rate"
                    value={props.exchangeRate}
                    error={props.errorFor('exchange_rate')}
                    onChange={(event) => props.onExchangeRateChange(event.target.value)}
                />
            </div>
            <div className="mt-4">
                <Textarea
                    label="Notes"
                    value={props.notes}
                    error={props.errorFor('notes')}
                    onChange={(event) => props.onNotesChange(event.target.value)}
                />
            </div>
        </Panel>
    );
}
