import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { SettingsEditor, type SettingsField, type SettingsOption } from '../../../shared/components/settings/SettingsEditor';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { documentApi } from '../../document/services/documentApi';
import { financeApi } from '../../finance/services/financeApi';
import { paymentApi } from '../../payment/services/paymentApi';
import { voucherApi } from '../services/voucherApi';
import {
    VoucherActivityTimeline,
    VoucherAllocationTable,
    VoucherApprovalPanel,
    VoucherPageHeader,
    VoucherPaymentImpactPanel,
    VoucherPostingPreviewPanel,
    VoucherTable,
} from '../components/VoucherComponents';
import type { Voucher, VoucherAllocation, VoucherPaymentImpactPreview, VoucherPostingPreview, VoucherSettings } from '../types/voucher.types';

export function VoucherApprovalListPage() {
    const [rows, setRows] = useState<Voucher[]>([]);
    const [error, setError] = useState<Error | null>(null);

    useEffect(() => {
        let active = true;
        voucherApi.vouchers.list({ perPage: 25, status: 'submitted' })
            .then((response) => {
                if (active) {
                    setRows(response.data);
                    setError(null);
                }
            })
            .catch((caught: Error) => {
                if (active) {
                    setError(caught);
                }
            });

        return () => {
            active = false;
        };
    }, []);

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                subtitle="Submitted and approved vouchers are shown for backend-controlled approve, reject, post, cancel, and reverse actions."
                title="Voucher Approvals"
            />
            {error ? <EmptyState description={error.message} title="Voucher approvals failed" /> : null}
            <VoucherTable rows={rows} />
            {rows[0] ? <VoucherApprovalPanel voucher={rows[0]} /> : null}
        </div>
    );
}

export function VoucherAllocationListPage() {
    const [rows, setRows] = useState<VoucherAllocation[]>([]);
    const [sourceVoucher, setSourceVoucher] = useState<Voucher | null>(null);
    const [paymentImpact, setPaymentImpact] = useState<VoucherPaymentImpactPreview>();
    const [error, setError] = useState<Error | null>(null);

    useEffect(() => {
        let active = true;
        voucherApi.vouchers.list({ perPage: 1 })
            .then((response) => {
                if (!active) {
                    return undefined;
                }

                const voucher = response.data[0] ?? null;
                setSourceVoucher(voucher);

                if (!voucher) {
                    return undefined;
                }

                return voucherApi.allocations.list(voucher.id, { perPage: 25 });
            })
            .then((response) => {
                if (active && response) {
                    setRows(response.data);
                    setError(null);
                }
            })
            .catch((caught: Error) => {
                if (active) {
                    setError(caught);
                }
            });

        return () => {
            active = false;
        };
    }, []);

    function previewAllocation(): void {
        if (!sourceVoucher) {
            return;
        }

        voucherApi.allocations.preview({ voucherId: sourceVoucher.id })
            .then((response) => setPaymentImpact(response as VoucherPaymentImpactPreview))
            .catch((caught: Error) => setError(caught));
    }

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                subtitle="Voucher allocations target generic documents or module records. Backend owns allocation balance and target eligibility."
                title="Voucher Allocations"
            />
            {error ? <EmptyState description={error.message} title="Voucher allocations failed" /> : null}
            <Card className="p-4">
                <div className="grid gap-3 md:grid-cols-[1fr_180px_180px_160px]">
                    <Input placeholder="Search target reference..." />
                    <Select options={[{ label: 'Any module', value: '' }, { label: 'Payment', value: 'payment' }, { label: 'Finance', value: 'finance' }, { label: 'Generic', value: 'generic' }]} />
                    <Select options={[{ label: 'Any status', value: '' }, { label: 'Preview', value: 'preview' }, { label: 'Allocated', value: 'allocated' }, { label: 'Reversed', value: 'reversed' }]} />
                    <Button disabled={!sourceVoucher} onClick={previewAllocation} title={sourceVoucher ? undefined : 'Load a voucher before previewing allocation impact.'} variant="secondary">Preview</Button>
                </div>
            </Card>
            <VoucherAllocationTable rows={rows} />
            {sourceVoucher && paymentImpact ? <VoucherPaymentImpactPanel preview={paymentImpact} voucher={sourceVoucher} /> : null}
        </div>
    );
}

export function VoucherPostingPreviewPage() {
    const [reference, setReference] = useState('');
    const [postingPreview, setPostingPreview] = useState<VoucherPostingPreview>();
    const [paymentImpact, setPaymentImpact] = useState<VoucherPaymentImpactPreview>();
    const [error, setError] = useState<Error | null>(null);
    const [isLoading, setIsLoading] = useState(false);

    function previewPosting(): void {
        setIsLoading(true);
        Promise.all([
            voucherApi.previews.posting({ reference }),
            voucherApi.previews.paymentImpact({ reference }),
        ])
            .then(([posting, payment]) => {
                setPostingPreview(posting as VoucherPostingPreview);
                setPaymentImpact(payment as VoucherPaymentImpactPreview);
                setError(null);
            })
            .catch((caught: Error) => setError(caught))
            .finally(() => setIsLoading(false));
    }

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<Link to="/vouchers/new"><Button>New Voucher</Button></Link>}
                subtitle="Standalone posting preview sends voucher source or draft line input to backend. React does not balance or post journals."
                title="Voucher Posting Preview"
            />
            <Card className="p-5">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Input onChange={(event) => setReference(event.target.value)} placeholder="Voucher or source reference" value={reference} />
                    <Select options={[{ label: 'Voucher draft', value: 'voucher' }, { label: 'Manual lines', value: 'manual' }, { label: 'Payment source', value: 'payment' }, { label: 'Finance source', value: 'finance' }]} />
                    <Input placeholder="Fiscal period checked by backend" />
                    <Button disabled={isLoading} onClick={previewPosting} variant="blue">{isLoading ? 'Previewing...' : 'Preview Posting'}</Button>
                </div>
            </Card>
            {error ? <EmptyState description={error.message} title="Preview failed" /> : null}
            {postingPreview || paymentImpact ? (
                <div className="grid gap-5 xl:grid-cols-2">
                    {postingPreview ? <PreviewPanel rows={[
                        { label: 'Debit total', value: postingPreview.calculated.debitTotal },
                        { label: 'Credit total', value: postingPreview.calculated.creditTotal },
                        { label: 'Balanced?', value: postingPreview.calculated.balanced },
                        { label: 'Posting eligibility', value: postingPreview.calculated.eligibility },
                    ]} status="Finance Preview" title="Posting Preview" /> : null}
                    {paymentImpact ? <PreviewPanel rows={[
                        { label: 'Payment impact', value: paymentImpact.calculated.paymentImpact },
                        { label: 'Allocation balance', value: paymentImpact.calculated.allocationBalance },
                        { label: 'Settlement status', value: paymentImpact.calculated.settlementStatus },
                    ]} status="Payment Preview" title="Payment Impact" /> : null}
                </div>
            ) : <EmptyState description="Enter a reference and request a backend preview." title="No preview requested" />}
        </div>
    );
}

async function voucherAccountOptions(): Promise<SettingsOption[]> {
    const response = await financeApi.listAccounts({ is_active: true, per_page: 25 });
    return response.data.map((account) => ({ label: `${account.accountCode} - ${account.accountName}`, value: account.id }));
}

async function voucherTaxGroupOptions(): Promise<SettingsOption[]> {
    const response = await financeApi.listTaxGroups({ is_active: true, per_page: 25 });
    return response.data.map((taxGroup) => ({ label: `${taxGroup.code} - ${taxGroup.name}`, value: taxGroup.id }));
}

async function voucherDocumentDefinitionOptions(): Promise<SettingsOption[]> {
    const response = await documentApi.listDefinitions();
    return response.data.map((definition) => ({ label: `${definition.code} - ${definition.name}`, value: definition.id }));
}

async function voucherPaymentMethodOptions(): Promise<SettingsOption[]> {
    const response = await paymentApi.listPaymentMethods();
    return response.data.map((method) => ({ label: `${method.code} - ${method.name}`, value: method.id }));
}

export function VoucherSettingsPage() {
    const [settings, setSettings] = useState<VoucherSettings | null>(null);
    const [error, setError] = useState<Error | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function reload() {
        setIsLoading(true);
        try {
            const response = await voucherApi.settings.get();
            setSettings(response.data);
            setError(null);
        } catch (caught) {
            setError(caught as Error);
        } finally {
            setIsLoading(false);
        }
    }

    useEffect(() => {
        let active = true;
        setIsLoading(true);
        voucherApi.settings.get()
            .then((response) => {
                if (active) {
                    setSettings(response.data);
                    setError(null);
                }
            })
            .catch((caught: Error) => {
                if (active) {
                    setError(caught);
                }
            })
            .finally(() => {
                if (active) {
                    setIsLoading(false);
                }
            });

        return () => {
            active = false;
        };
    }, []);

    const fields = useMemo<SettingsField[]>(() => {
        if (!settings) {
            return [];
        }

        return [
            { key: 'default_cash_account_id', label: 'Cash account', loadOptions: voucherAccountOptions, section: 'Account mappings', type: 'select' },
            { key: 'default_bank_account_id', label: 'Bank account', loadOptions: voucherAccountOptions, section: 'Account mappings', type: 'select' },
            { key: 'default_expense_account_id', label: 'Expense account', loadOptions: voucherAccountOptions, section: 'Account mappings', type: 'select' },
            { key: 'default_advance_account_id', label: 'Advance account', loadOptions: voucherAccountOptions, section: 'Account mappings', type: 'select' },
            { key: 'default_receivable_account_id', label: 'Receivable account', loadOptions: voucherAccountOptions, section: 'Account mappings', type: 'select' },
            { key: 'default_payable_account_id', label: 'Payable account', loadOptions: voucherAccountOptions, section: 'Account mappings', type: 'select' },
            { key: 'default_write_off_account_id', label: 'Write-off account', loadOptions: voucherAccountOptions, section: 'Account mappings', type: 'select' },
            { key: 'default_tax_account_id', label: 'Tax account', loadOptions: voucherAccountOptions, section: 'Account mappings', type: 'select' },
            { key: 'default_tax_group_id', label: 'Tax group', loadOptions: voucherTaxGroupOptions, section: 'Account mappings', type: 'select' },
            { currentLabel: settings.defaultPaymentMethod, key: 'default_payment_method_id', label: 'Default payment method', loadOptions: voucherPaymentMethodOptions, section: 'Payment defaults', type: 'select' },
            { currentLabel: settings.defaultDocumentDefinition, key: 'default_document_definition_id', label: 'Default document definition', loadOptions: voucherDocumentDefinitionOptions, section: 'Documents', type: 'select' },
            { key: 'payment_voucher_document_definition_id', label: 'Payment voucher document', loadOptions: voucherDocumentDefinitionOptions, section: 'Documents', type: 'select' },
            { key: 'receipt_voucher_document_definition_id', label: 'Receipt voucher document', loadOptions: voucherDocumentDefinitionOptions, section: 'Documents', type: 'select' },
            { key: 'journal_voucher_document_definition_id', label: 'Journal voucher document', loadOptions: voucherDocumentDefinitionOptions, section: 'Documents', type: 'select' },
            { key: 'default_sequence_period_type', label: 'Default sequence period', options: [{ label: 'Monthly', value: 'monthly' }, { label: 'Yearly', value: 'yearly' }, { label: 'Infinite', value: 'infinite' }], section: 'Documents', type: 'select' },
            { key: 'require_approval', label: 'Require approval', section: 'Workflow controls', type: 'boolean' },
            { key: 'allow_direct_posting', label: 'Allow direct posting', section: 'Workflow controls', type: 'boolean' },
            { key: 'allow_reversal', label: 'Allow reversal', section: 'Workflow controls', type: 'boolean' },
            { key: 'allow_partial_allocation', label: 'Allow partial allocation', section: 'Workflow controls', type: 'boolean' },
            { key: 'is_active', label: 'Settings active', section: 'Workflow controls', type: 'boolean' },
        ];
    }, [settings]);

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                subtitle="Voucher settings are module defaults. They do not encode Purchase, Sales, Service, or Rental-specific workflow."
                title="Voucher Settings"
            />
            {error ? <EmptyState description={error.message} title="Voucher settings failed" /> : null}
            {isLoading && !settings ? <EmptyState description="Loading voucher settings from backend..." title="Loading settings" /> : null}
            {settings ? (
                <SettingsEditor
                    fields={fields}
                    initialValues={settings._raw ?? {}}
                    onSave={async (payload) => {
                        await voucherApi.settings.update(payload);
                        await reload();
                    }}
                    title="Voucher configuration"
                />
            ) : null}
            <VoucherActivityTimeline rows={[]} />
        </div>
    );
}
