import { Link, useLocation, useParams } from 'react-router-dom';
import { PageHeader } from './PageHeader';
import { PreviewPanel } from './PreviewPanel';
import { StatusBadge } from './StatusBadge';
import { DataTable, type DataTableColumn } from '../data/DataTable';
import { SearchFilterBar } from '../data/SearchFilterBar';
import { FormSection } from '../forms/FormSection';
import { Button } from '../ui/Button';
import { Card } from '../ui/Card';
import { Input } from '../ui/Input';
import { Select } from '../ui/Select';
import { Textarea } from '../ui/Textarea';
import type { SelectOption } from '../../types/select.types';

type FieldType = 'date' | 'email' | 'select' | 'textarea' | 'text';

export type MasterDataField = {
    help?: string;
    label: string;
    name: string;
    options?: SelectOption[];
    placeholder?: string;
    type?: FieldType;
};

type MasterDataWorkspaceProps<TRow extends { id: string; status?: string }> = {
    backendNotes: string[];
    columns: Array<DataTableColumn<TRow>>;
    createPath: string;
    description: string;
    fields: MasterDataField[];
    listPath: string;
    previewTitle: string;
    rows: TRow[];
    sections?: Array<{ description: string; title: string }>;
    title: string;
};

function Field({ field }: { field: MasterDataField }) {
    return (
        <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{field.label}</label>
            {field.type === 'select' ? <Select options={field.options ?? []} placeholder={field.placeholder} /> : null}
            {field.type === 'textarea' ? <Textarea placeholder={field.placeholder} /> : null}
            {!field.type || ['date', 'email', 'text'].includes(field.type) ? <Input placeholder={field.placeholder} type={field.type ?? 'text'} /> : null}
            {field.help ? <p className="text-xs text-slate-400">{field.help}</p> : null}
        </div>
    );
}

export function MasterDataWorkspace<TRow extends { id: string; status?: string }>({
    backendNotes,
    columns,
    createPath,
    description,
    fields,
    listPath,
    previewTitle,
    rows,
    sections,
    title,
}: MasterDataWorkspaceProps<TRow>) {
    const { id } = useParams();
    const { pathname } = useLocation();
    const isCreate = pathname.endsWith('/new');
    const isEdit = pathname.endsWith('/edit');
    const isDetail = Boolean(id) && !isEdit;
    const record = rows.find((row) => row.id === id) ?? rows[0];

    if (isCreate || isEdit) {
        return (
            <div className="space-y-6">
                <PageHeader
                    actions={
                        <>
                            <Link to={listPath}>
                                <Button variant="secondary">Cancel</Button>
                            </Link>
                            <Button>Save Draft</Button>
                            <Button variant="blue">Submit</Button>
                        </>
                    }
                    eyebrow="Master data"
                    subtitle={`${description} Backend validation remains authoritative.`}
                    title={isEdit ? `Edit ${title}` : `Create ${title}`}
                />
                <div className="grid gap-5 xl:grid-cols-[1fr_340px]">
                    <div className="space-y-5">
                        <FormSection description="Collect permitted input fields only. Backend validates tenant, references, defaults, and optional user access." title={`${title} details`}>
                            <div className="grid gap-4 md:grid-cols-2">
                                {fields.map((field) => (
                                    <Field field={field} key={field.name} />
                                ))}
                            </div>
                        </FormSection>
                        {sections?.length ? (
                            <div className="grid gap-4 md:grid-cols-2">
                                {sections.map((section) => (
                                    <Card className="p-4" key={section.title}>
                                        <h3 className="text-sm font-bold text-slate-950">{section.title}</h3>
                                        <p className="mt-1 text-sm text-slate-500">{section.description}</p>
                                    </Card>
                                ))}
                            </div>
                        ) : null}
                    </div>
                    <PreviewPanel
                        rows={backendNotes.map((note, index) => ({ label: `Backend rule ${index + 1}`, value: note }))}
                        title={previewTitle}
                    />
                </div>
            </div>
        );
    }

    if (isDetail) {
        return (
            <div className="space-y-6">
                <PageHeader
                    actions={
                        <>
                            <Link to={listPath}>
                                <Button variant="secondary">Back</Button>
                            </Link>
                            <Link to={`${listPath}/${id}/edit`}>
                                <Button>Edit</Button>
                            </Link>
                        </>
                    }
                    eyebrow="Master data"
                    subtitle={description}
                    title={`${title} Detail`}
                />
                <Card className="p-5">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-base font-bold text-slate-950">Record summary</h2>
                        {record.status ? <StatusBadge status={record.status} /> : null}
                    </div>
                    <DataTable columns={columns} getRowKey={(row) => row.id} rows={[record]} />
                </Card>
                {sections?.length ? (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {sections.map((section) => (
                            <Card className="p-5" key={section.title}>
                                <h3 className="text-sm font-bold text-slate-950">{section.title}</h3>
                                <p className="mt-1 text-sm text-slate-500">{section.description}</p>
                            </Card>
                        ))}
                    </div>
                ) : null}
                <PreviewPanel
                    rows={backendNotes.map((note, index) => ({ label: `Backend responsibility ${index + 1}`, value: note }))}
                    title={previewTitle}
                />
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={
                    <Link to={createPath}>
                        <Button>New {title}</Button>
                    </Link>
                }
                eyebrow="Master data"
                subtitle={description}
                title={title}
            />
            <SearchFilterBar placeholder={`Search ${title.toLowerCase()}...`} />
            {sections?.length ? (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {sections.map((section) => (
                        <Card className="p-5" key={section.title}>
                            <h3 className="text-sm font-bold text-slate-950">{section.title}</h3>
                            <p className="mt-1 text-sm text-slate-500">{section.description}</p>
                        </Card>
                    ))}
                </div>
            ) : null}
            <DataTable columns={columns} getRowKey={(row) => row.id} rows={rows} />
        </div>
    );
}
