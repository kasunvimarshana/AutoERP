import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';

export function SubItemForm() {
    return (
        <div className="grid gap-4 lg:grid-cols-[8rem_1fr_1fr_auto] lg:items-end">
            <label className="space-y-2">
                <span className="text-sm font-semibold text-slate-500">Sub Item ID</span>
                <Input defaultValue="271" />
            </label>
            <label className="space-y-2">
                <span className="text-sm font-semibold text-slate-500">Sub Item Name</span>
                <Input defaultValue="Finishing" />
            </label>
            <label className="space-y-2">
                <span className="text-sm font-semibold text-slate-500">Price</span>
                <Input defaultValue="100.00" />
            </label>
            <Button className="px-10">ADD</Button>
        </div>
    );
}
