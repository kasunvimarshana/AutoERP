import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar } from '../../../shared/components/data/DataToolbar';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Button } from '../../../shared/components/ui/Button';
import { SalesQuotationForm, SalesQuotationLineTable, SalesQuotationTable } from '../components/SalesComponents';
import { salesApi } from '../services/salesApi';
import type { SalesQuotation } from '../types/sales.types';

export function SalesQuotationListPage() {
    const [rows, setRows] = useState<SalesQuotation[]>([]);
    const [query, setQuery] = useState('');

    useEffect(() => {
        salesApi.quotations.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/sales/quotations/new"><Button disabled title="This repository has no Sales quotation backend schema/API yet.">Create Quotation</Button></Link>} eyebrow="Sales" subtitle="Quotation/proforma is not backed by the current Sales backend schema, so no synthetic quotation data is shown." title="Quotations / Proforma" />
            <DataToolbar onSearchChange={setQuery} savedViewsDisabledReason="Saved views require a user-preferences backend for Sales lists." searchPlaceholder="Search quotation number, customer, status..." searchValue={query} />
            {rows.length ? <SalesQuotationTable rows={rows} /> : <EmptyState description="No quotation backend table or API exists in this repository. Start from Sales Orders until that backend contract is added." title="Quotation backend unavailable" />}
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
    const [quotation, setQuotation] = useState<SalesQuotation>();
    const [error, setError] = useState('');

    useEffect(() => {
        salesApi.quotations.get(id)
            .then((response) => setQuotation(response.data))
            .catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Quotation backend unavailable.'));
    }, [id]);

    if (error) {
        return <EmptyState description={error} title="Quotation unavailable" />;
    }

    if (!quotation) {
        return <EmptyState description="Loading quotation details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Button disabled title="Quotation conversion requires a quotation backend contract.">Convert to Sales Order</Button>} eyebrow="Quotation" subtitle="Authoritative pricing and conversion require a backend quotation contract." title={quotation.quotationNumber} />
            <SalesQuotationTable rows={[quotation]} />
            <SalesQuotationLineTable rows={quotation.lines} />
        </div>
    );
}
