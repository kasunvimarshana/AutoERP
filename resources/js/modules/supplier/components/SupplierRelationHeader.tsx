import { Button } from '@/shared/components/Button';

export function SupplierRelationHeader({ title, description, onAdd, addLabel = 'Add' }: {
    title: string;
    description: string;
    onAdd?: () => void;
    addLabel?: string;
}) {
    return <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div><h2 className="font-semibold text-slate-900">{title}</h2><p className="text-sm text-slate-500">{description}</p></div>
        {onAdd && <Button onClick={onAdd}>{addLabel}</Button>}
    </div>;
}
