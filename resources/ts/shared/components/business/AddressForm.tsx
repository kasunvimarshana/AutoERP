import { Input } from '../ui/Input';

export function AddressForm() {
    return (
        <div className="grid gap-4 md:grid-cols-2">
            <Input placeholder="Address line 1" />
            <Input placeholder="City" />
            <Input placeholder="Region" />
            <Input placeholder="Postal code" />
        </div>
    );
}
