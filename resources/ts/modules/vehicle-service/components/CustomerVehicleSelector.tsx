import { Button } from '../../../shared/components/ui/Button';
import { Select } from '../../../shared/components/ui/Select';
import type { SelectOption } from '../../../shared/types/select.types';

type CustomerVehicleSelectorProps = {
    label: string;
    options: SelectOption[];
    placeholder: string;
};

export function CustomerVehicleSelector({ label, options, placeholder }: CustomerVehicleSelectorProps) {
    return (
        <label className="space-y-2">
            <span className="text-xs font-bold uppercase tracking-wide text-slate-500">{label}</span>
            <div className="flex gap-2">
                <Select options={options} placeholder={placeholder} />
                <Button aria-label={`Add ${label.toLowerCase()}`} className="h-11 w-11 shrink-0 px-0 text-xl" variant="secondary">
                    +
                </Button>
            </div>
        </label>
    );
}
