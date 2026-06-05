import { Input } from '../ui/Input';
import { Select } from '../ui/Select';

type DynamicField = {
    label: string;
    name: string;
    type?: 'text' | 'select';
};

type DynamicFieldRendererProps = {
    fields: DynamicField[];
};

export function DynamicFieldRenderer({ fields }: DynamicFieldRendererProps) {
    return (
        <div className="grid gap-4 md:grid-cols-2">
            {fields.map((field) => (
                <label className="space-y-2" key={field.name}>
                    <span className="text-xs font-bold uppercase tracking-wide text-slate-500">{field.label}</span>
                    {field.type === 'select' ? <Select placeholder={`Select ${field.label}`} /> : <Input placeholder={field.label} />}
                </label>
            ))}
        </div>
    );
}
