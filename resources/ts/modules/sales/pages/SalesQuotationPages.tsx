import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Button } from '../../../shared/components/ui/Button';
import { Link, useParams } from 'react-router-dom';
import { SalesQuotationForm, SalesQuotationLineTable, SalesQuotationTable } from '../components/SalesComponents';
import { salesQuotations } from '../mock/salesMock';

export function SalesQuotationListPage() {
    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/sales/quotations/new"><Button>Create Quotation</Button></Link>} eyebrow="Sales" subtitle="Quotation/proforma workspace is mock-backed until backend quotation endpoints are added." title="Quotations / Proforma" />
            <SearchFilterBar placeholder="Search quotation number, customer, status..." />
            <SalesQuotationTable rows={salesQuotations} />
        </div>
    );
}

export function SalesQuotationCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Sales" subtitle="Create quotation input. Backend quotation APIs are a clean future contract." title="Create Quotation" />
            <SalesQuotationForm />
        </div>
    );
}

export function SalesQuotationDetailPage() {
    const { id = 'sq-001' } = useParams();
    const quotation = salesQuotations.find((row) => row.id === id) ?? salesQuotations[0];

    if (!quotation) {
        return <EmptyState description="No quotation mock data available." title="Quotation unavailable" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Button variant="blue">Convert to Sales Order</Button>} eyebrow="Quotation" subtitle="Quotation detail placeholder. Authoritative pricing and conversion will be backend-owned when implemented." title={quotation.quotationNumber} />
            <SalesQuotationTable rows={[quotation]} />
            <SalesQuotationLineTable rows={quotation.lines} />
        </div>
    );
}
