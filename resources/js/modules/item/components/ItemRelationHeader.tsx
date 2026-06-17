import { Button } from '@/shared/components/Button';

export function ItemRelationHeader({ title, description, onAdd, disabled = false }: {
    title: string;
    description: string;
    onAdd?: () => void;
    disabled?: boolean;
}) {
    return (
        <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 className="font-semibold text-slate-900">{title}</h3>
                <p className="text-sm text-slate-500">{description}</p>
            </div>
            {onAdd && <Button onClick={onAdd} disabled={disabled}>Add</Button>}
        </div>
    );
}
