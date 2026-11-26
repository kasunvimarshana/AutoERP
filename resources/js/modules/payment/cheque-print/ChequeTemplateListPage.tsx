/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { LinkButton } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { deleteChequeTemplate, listChequeTemplates } from './chequePrintApi';
import type { ChequeTemplate } from './chequePrintTypes';

export default function ChequeTemplateListPage() {
    const [search, setSearch] = useState('');
    const templates = useApi((signal) => listChequeTemplates({ all: true, search: search || undefined }, signal), [search]);
    const [deleting, setDeleting] = useState<ChequeTemplate | null>(null);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    const rows = templates.data?.data ?? [];
    const filteredRows = search
        ? rows.filter((r) =>
            r.template_name.toLowerCase().includes(search.toLowerCase()) ||
            (r.bank_name ?? '').toLowerCase().includes(search.toLowerCase()))
        : rows;

    const columns: DataColumn<ChequeTemplate>[] = [
        {
            key: 'template',
            header: 'Template',
            render: (row) => (
                <div>
                    <div className="flex items-center gap-2">
                        <span className="font-semibold text-slate-900">{row.template_name}</span>
                        {row.is_default && (
                            <span className="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                Default
                            </span>
                        )}
                    </div>
                    <span className="block text-xs text-slate-500">{row.bank_name || 'Bank not specified'}</span>
                </div>
            ),
        },
        {
            key: 'page',
            header: 'Page dimensions',
            render: (row) => (
                <span className="text-sm text-slate-700 font-mono">
                    {row.page_width_mm} &times; {row.page_height_mm} mm
                </span>
            ),
        },
        {
            key: 'font',
            header: 'Typography',
            render: (row) => (
                <span className="text-sm text-slate-600">
                    {row.font_family || 'Arial'} &bull; {row.font_size}pt
                </span>
            ),
        },
        {
            key: 'features',
            header: 'Features',
            render: (row) => {
                const meta = row.metadata ?? {};
                const tags: string[] = [];
                if (meta.crossing_type && meta.crossing_type !== 'none') tags.push('Crossing');
                if (meta.bearer_strike_through) tags.push('Bearer strike');
                if (meta.date_letter_spacing) tags.push('Boxed date');
                return (
                    <span className="text-xs text-slate-500">
                        {tags.length > 0 ? tags.join(', ') : 'Standard'}
                    </span>
                );
            },
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} />,
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            mobile: false,
            render: (row) => (
                <div className="flex justify-end items-center gap-3">
                    <Link
                        className="font-semibold text-blue-600 hover:text-blue-800 hover:underline"
                        to={`/payments/cheque-templates/${row.id}/edit`}
                    >
                        Edit
                    </Link>
                    {row.can_delete !== false && !row.is_default ? (
                        <button
                            type="button"
                            className="font-semibold text-rose-700 hover:text-rose-900 hover:underline"
                            onClick={() => setDeleting(row)}
                        >
                            Delete
                        </button>
                    ) : (
                        <span
                            className="text-xs text-slate-400 italic cursor-not-allowed"
                            title={row.is_default ? 'Default template cannot be deleted' : 'Template has print history; deactivate instead'}
                        >
                            Locked
                        </span>
                    )}
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
                description="Manage bank-specific cheque dimensions, millimeter alignments, and print configurations."
                actions={<LinkButton to="/payments/cheque-templates/create">New template</LinkButton>}
            />
            <ErrorAlert error={actionError ?? templates.error} />

            <div className="mb-4 max-w-sm">
                <Input
                    placeholder="Search templates or banks..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                />
            </div>

            {templates.loading ? (
                <LoadingState />
            ) : (
                <DataTable
                    rows={filteredRows}
                    columns={columns}
                    rowKey={(row) => row.id}
                    emptyMessage="No cheque templates have been configured."
                />
            )}

            <ConfirmDialog
                open={Boolean(deleting)}
                title="Delete cheque template"
                message={`Delete template "${deleting?.template_name}"? Templates with recorded print history cannot be deleted and must be deactivated instead.`}
                confirmLabel="Delete"
                loading={busy}
                onCancel={() => !busy && setDeleting(null)}
                onConfirm={() => void remove()}
            />
        </>
    );
}
