import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { LinkButton } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { deleteChequeTemplate, listChequeTemplates } from './chequePrintApi';
import type { ChequeTemplate } from './chequePrintTypes';

export default function ChequeTemplateListPage() {
    const templates = useApi((signal) => listChequeTemplates(false, signal), []);
    const [deleting, setDeleting] = useState<ChequeTemplate | null>(null);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const columns: DataColumn<ChequeTemplate>[] = [
        {
            key: 'template',
            header: 'Template',
            render: (row) => (
                <div>
                    <span className="font-semibold text-slate-900">{row.template_name}</span>
                    <span className="block text-xs text-slate-500">{row.bank_name || 'Bank not specified'}</span>
                </div>
            ),
        },
        { key: 'page', header: 'Page', render: (row) => `${row.page_width_mm} x ${row.page_height_mm} mm` },
        { key: 'font', header: 'Font', render: (row) => `${row.font_family || 'Arial'} / ${row.font_size}pt` },
        { key: 'default', header: 'Default', render: (row) => row.is_default ? 'Yes' : 'No' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            mobile: false,
            render: (row) => (
                <div className="flex justify-end gap-3">
                    <Link className="font-semibold text-sky-700 hover:underline" to={`/payments/cheque-templates/${row.id}/edit`}>Edit</Link>
                    <button type="button" className="font-semibold text-rose-700" onClick={() => setDeleting(row)}>Delete</button>
                </div>
            ),
        },
    ];

    async function remove() {
        if (!deleting) return;
        setBusy(true);
        setActionError(null);
        try {
            await deleteChequeTemplate(deleting.id);
            setDeleting(null);
            templates.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    }

    return (
        <>
            <ContentHeader
                title="Cheque templates"
                description="Bank-specific cheque dimensions and millimeter alignment."
                actions={<LinkButton to="/payments/cheque-templates/create">New template</LinkButton>}
            />
            <ErrorAlert error={actionError ?? templates.error} />
            {templates.loading
                ? <LoadingState />
                : <DataTable rows={templates.data?.data ?? []} columns={columns} rowKey={(row) => row.id} emptyMessage="No cheque templates have been configured." />}
            <ConfirmDialog
                open={Boolean(deleting)}
                title="Delete cheque template"
                message="Delete this template? Templates with print history must be deactivated instead."
                confirmLabel="Delete"
                loading={busy}
                onCancel={() => !busy && setDeleting(null)}
                onConfirm={() => void remove()}
            />
        </>
    );
}
