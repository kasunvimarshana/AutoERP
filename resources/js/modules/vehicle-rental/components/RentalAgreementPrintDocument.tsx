import { Button, LinkButton } from "@/shared/components/Button";
import { formatDate } from "@/shared/utils/formatDate";
import { humanize } from "@/shared/utils/object";
import { rentalAgreementKindLabel } from "../rentalAgreementPresentation";
import { rentalAgreementRateLabel } from "../rentalAgreementUi";
import type { RentalAgreementDocumentSnapshot } from "../vehicleRentalTypes";

type SnapshotRateVersion = NonNullable<
    RentalAgreementDocumentSnapshot["rate_version"]
>;
type SnapshotRateComponent = SnapshotRateVersion["components"][number];

export type RentalAgreementPrintableSnapshot = Omit<
    RentalAgreementDocumentSnapshot,
    "rate_version"
> & {
    rate_version?:
        | (Omit<SnapshotRateVersion, "components"> & {
              components: Array<
                  SnapshotRateComponent & { is_taxable?: boolean }
              >;
          })
        | null;
};

interface RentalAgreementPrintDocumentProps {
    snapshot: RentalAgreementPrintableSnapshot;
    backPath: string;
}

export function RentalAgreementPrintDocument({
    snapshot,
    backPath,
}: RentalAgreementPrintDocumentProps) {
    const partyLabel =
        snapshot.party.type === "customer" ? "Lessee" : "Lessor";
    const currency =
        snapshot.commercial_terms.currency.code ??
        snapshot.commercial_terms.currency.symbol ??
        "";

    return (
        <div className="agreement-print-shell">
            <style>{`
                @media print {
                    @page { size: A4 portrait; margin: 14mm; }
                    .agreement-print-shell { padding: 0 !important; }
                    .agreement-print-document {
                        border: 0 !important;
                        box-shadow: none !important;
                        max-width: none !important;
                    }
                }
            `}</style>
            <div className="no-print mb-5 flex justify-end gap-3">
                <LinkButton variant="secondary" to={backPath}>
                    Back to agreement
                </LinkButton>
                <Button onClick={() => window.print()}>Print agreement</Button>
            </div>
            <article className="agreement-print-document mx-auto max-w-4xl rounded-lg border border-slate-300 bg-white p-8 text-slate-950 shadow-sm">
                <header className="border-b border-slate-300 pb-5 text-center">
                    <p className="text-sm font-semibold uppercase tracking-widest text-slate-600">
                        {rentalAgreementKindLabel(snapshot.agreement_kind)}
                    </p>
                    <h1 className="mt-2 text-3xl font-bold">
                        {snapshot.agreement_number}
                    </h1>
                    <p className="mt-2 text-sm text-slate-600">
                        Executed {formatDate(snapshot.executed_at)}
                    </p>
                </header>

                <section className="grid gap-6 border-b border-slate-200 py-6 md:grid-cols-2">
                    <DocumentParty
                        label="Organization"
                        name={snapshot.organization.name}
                        code={snapshot.organization.code}
                    />
                    <DocumentParty
                        label={partyLabel}
                        name={snapshot.party.name}
                        code={snapshot.party.code}
                    />
                </section>

                <section className="border-b border-slate-200 py-6">
                    <h2 className="text-lg font-bold">Agreement summary</h2>
                    <dl className="mt-4 grid gap-x-8 gap-y-3 text-sm md:grid-cols-2">
                        <DocumentField
                            label="Kind"
                            value={rentalAgreementKindLabel(
                                snapshot.agreement_kind,
                            )}
                        />
                        <DocumentField
                            label="Legal context"
                            value={humanize(snapshot.legal_context)}
                        />
                        <DocumentField
                            label="Agreement date"
                            value={formatDate(snapshot.agreement_date)}
                        />
                        <DocumentField
                            label="Rental period"
                            value={`${formatDate(snapshot.period.starts_at)} - ${formatDate(snapshot.period.ends_at)}`}
                        />
                        <DocumentField
                            label="Rental mode"
                            value={humanize(
                                snapshot.commercial_terms.rental_mode,
                            )}
                        />
                        <DocumentField
                            label="Billing"
                            value={`${humanize(snapshot.commercial_terms.billing_cycle)} / ${humanize(snapshot.commercial_terms.billing_basis)}`}
                        />
                        <DocumentField
                            label="Proration"
                            value={humanize(
                                snapshot.commercial_terms.proration_rule,
                            )}
                        />
                        <DocumentField
                            label="Payment terms"
                            value={formatPaymentTerms(
                                snapshot.commercial_terms.payment_term_days,
                            )}
                        />
                        <DocumentField
                            label="Currency"
                            value={
                                snapshot.commercial_terms.currency.name ??
                                currency
                            }
                        />
                    </dl>
                    {snapshot.commercial_terms.remarks && (
                        <div className="mt-4 text-sm">
                            <h3 className="font-semibold">
                                Agreement remarks
                            </h3>
                            <p className="mt-1 whitespace-pre-wrap">
                                {snapshot.commercial_terms.remarks}
                            </p>
                        </div>
                    )}
                </section>

                <section className="border-b border-slate-200 py-6">
                    <h2 className="text-lg font-bold">Commercial rates</h2>
                    {snapshot.rate_version?.components.length ? (
                        <table className="mt-4 w-full border-collapse text-left text-sm">
                            <thead>
                                <tr className="border-b border-slate-300">
                                    <th className="py-2 pr-4">Component</th>
                                    <th className="py-2 pr-4">Unit</th>
                                    <th className="py-2 pr-4">Tax</th>
                                    <th className="py-2 text-right">Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                {snapshot.rate_version.components.map(
                                    (component) => (
                                        <tr
                                            key={componentKey(component)}
                                            className="border-b border-slate-100"
                                        >
                                            <td className="py-2 pr-4">
                                                {rentalAgreementRateLabel(
                                                    snapshot.agreement_kind,
                                                    component.component_code,
                                                )}
                                            </td>
                                            <td className="py-2 pr-4">
                                                {humanize(component.unit)}
                                            </td>
                                            <td className="py-2 pr-4">
                                                {formatTaxTreatment(component)}
                                            </td>
                                            <td className="py-2 text-right font-semibold">
                                                {currency} {component.rate}
                                            </td>
                                        </tr>
                                    ),
                                )}
                            </tbody>
                        </table>
                    ) : (
                        <p className="mt-3 text-sm text-slate-600">
                            No commercial rates were captured.
                        </p>
                    )}
                </section>

                <section className="py-6">
                    <h2 className="text-lg font-bold">Terms and conditions</h2>
                    {snapshot.terms.length ? (
                        <ol className="mt-4 space-y-5">
                            {snapshot.terms.map((term) => (
                                <li key={term.sequence} className="text-sm">
                                    <h3 className="font-semibold">
                                        {term.sequence}.{" "}
                                        {term.title ||
                                            `Clause ${term.sequence}`}
                                    </h3>
                                    <p className="mt-1 whitespace-pre-wrap leading-6">
                                        {term.content}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    ) : (
                        <p className="mt-3 text-sm text-slate-600">
                            No agreement-specific clauses were captured.
                        </p>
                    )}
                </section>

                <footer className="mt-8 border-t border-slate-300 pt-4 text-xs text-slate-500">
                    Immutable execution snapshot v{snapshot.version}, captured{" "}
                    {formatDate(snapshot.captured_at)}.
                </footer>
            </article>
        </div>
    );
}

function DocumentParty({
    label,
    name,
    code,
}: {
    label: string;
    name?: string | null;
    code?: string | null;
}) {
    return (
        <div>
            <h2 className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                {label}
            </h2>
            <p className="mt-2 font-bold">{name ?? "-"}</p>
            {code && <p className="mt-1 text-sm text-slate-600">{code}</p>}
        </div>
    );
}

function DocumentField({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    return (
        <div>
            <dt className="text-slate-500">{label}</dt>
            <dd className="font-semibold">{value}</dd>
        </div>
    );
}

function componentKey(component: SnapshotRateComponent): string {
    return `${component.component_code}:${component.unit}`;
}

function formatTaxTreatment(
    component: SnapshotRateComponent & { is_taxable?: boolean },
): string {
    if (component.is_taxable === undefined) return "Not captured";
    return component.is_taxable ? "Taxable" : "Non-taxable";
}

function formatPaymentTerms(value?: number | null): string {
    if (value === null || value === undefined) return "Not specified";
    if (value === 0) return "Due immediately";
    return `${value} days`;
}
