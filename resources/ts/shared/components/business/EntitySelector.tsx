import { Select } from '../ui/Select';
import { Button } from '../ui/Button';
import type { SelectOption } from '../../types/select.types';

type EntitySelectorProps = {
    label?: string;
    onQuickAdd?: () => void;
    options: SelectOption[];
    placeholder: string;
};

export function EntitySelector({ label, onQuickAdd, options, placeholder }: EntitySelectorProps) {
    return (
        <div className="space-y-2">
            {label ? <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{label}</label> : null}
            <div className="flex gap-2">
                <Select options={options} placeholder={placeholder} />
                {onQuickAdd ? (
                    <Button aria-label={`Quick add ${label ?? 'entity'}`} variant="secondary">
                        +
                    </Button>
                ) : null}
            </div>
        </div>
    );
}
