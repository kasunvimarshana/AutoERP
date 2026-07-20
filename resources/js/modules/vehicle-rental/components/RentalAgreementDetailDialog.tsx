import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { getRentalAgreement } from '../vehicleRentalApi';
import type { RentalRateCode } from '../vehicleRentalTypes';

interface RentalAgreementDetailDialogProps {
    agreementId: number | null;
    open: boolean;
    onClose: () => void;
}

function dateLabel(value?: string | null): string {
    if (!value) return '—';
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString();
}

function rateLabel(code: RentalRateCode): string {
    return code
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

export function RentalAgreementDetailDialog({
    agreementId,
    open,
    onClose,
}: RentalAgreementDetailDialogProps) {
    const result = useApi((signal) => {
        if (agreementId === null) throw new Error('Rental agreement id is required.');
        return getRentalAgreement(agreementId, signal);
    }, [agreementId], open && agreementId !== null);
    const agreement = result.data;
    const party = agreement?.customer ?? agreement?.supplier;

    return (
        <Modal
            open={open}
            title={agreement ? agreement.agreement_number : 'Rental agreement'}
            onClose={onClose}
        >
            <ErrorAlert error={result.error} inline />
            {result.loading && <LoadingState />}
            {agreement && (
                <div className="space-y-6">
                    <div className="flex items-center justify-between gap-4">
                        <p className="text-sm text-slate-600">
                            {agreement.kind === 'owner' ? 'Owner agreement' : 'Customer agreement'}
                        </p>
                        <StatusBadge status={agreement.status} />
                    </div>
                    <DetailGrid items={[
                        { label: agreement.kind === 'owner' ? 'Owner / supplier' : 'Customer', value: party?.name || party?.code || '—' },
                        { label: 'Billing basis', value: agreement.billing_basis },
                        { label: 'Currency', value: agreement.currency?.code || agreement.currency?.name || '—' },
                        { label: 'Executed on', value: dateLabel(agreement.executed_at) },
                        { label: 'Starts on', value: dateLabel(agreement.starts_on) },
                        { label: 'Ends on', value: dateLabel(agreement.ends_on) },
                        { label: 'Included KM', value: agreement.included_km },
                        { label: 'Payment terms', value: `${agreement.payment_terms_days} days` },
                        { label: 'Tax group', value: agreement.tax_group?.name || agreement.tax_group?.code || '—' },
                        { label: 'Deposit required', value: agreement.deposit_required ? 'Yes' : 'No' },
                        {
                            label: 'Deposit amount',
                            value: agreement.deposit_required
                                ? <MoneyDisplay value={agreement.deposit_amount} currency={agreement.currency?.code ?? undefined} />
                                : '—',
                        },
                        { label: 'Version', value: agreement.row_version },
                    ]} />
                    <div>
                        <h3 className="text-sm font-semibold text-slate-900">Terms</h3>
                        <p className="mt-2 whitespace-pre-wrap text-sm text-slate-700">{agreement.terms || '—'}</p>
                    </div>
                    <div>
                        <h3 className="text-sm font-semibold text-slate-900">Notes</h3>
                        <p className="mt-2 whitespace-pre-wrap text-sm text-slate-700">{agreement.notes || '—'}</p>
                    </div>
                    <div className="space-y-4">
                        <h3 className="text-sm font-semibold text-slate-900">Rate history</h3>
                        {(agreement.rate_versions ?? []).map((version) => (
                            <div key={version.id} className="rounded-lg border border-slate-200 p-4">
                                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                                    <span className="font-medium text-slate-900">Version {version.version_number}</span>
                                    <span className="text-sm text-slate-600">
                                        {dateLabel(version.effective_from)} → {dateLabel(version.effective_to)}
                                    </span>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-left text-sm">
                                        <thead className="border-b border-slate-200 text-slate-500">
                                            <tr>
                                                <th className="px-2 py-2 font-medium">Rate</th>
                                                <th className="px-2 py-2 font-medium">Unit</th>
                                                <th className="px-2 py-2 font-medium">Amount</th>
                                                <th className="px-2 py-2 font-medium">Taxable</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {version.rates.map((rate) => (
                                                <tr key={`${version.id}:${rate.code}`} className="border-b border-slate-100 last:border-0">
                                                    <td className="px-2 py-2">{rateLabel(rate.code)}</td>
                                                    <td className="px-2 py-2">{rate.unit}</td>
                                                    <td className="px-2 py-2">
                                                        <MoneyDisplay value={rate.rate} currency={agreement.currency?.code ?? undefined} />
                                                    </td>
                                                    <td className="px-2 py-2">{rate.is_taxable ? 'Yes' : 'No'}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        ))}
                        {(agreement.rate_versions ?? []).length === 0 && (
                            <p className="text-sm text-slate-500">No rate versions are available.</p>
                        )}
                    </div>
                </div>
            )}
        </Modal>
    );
}
