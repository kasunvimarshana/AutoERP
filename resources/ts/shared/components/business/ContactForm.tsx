import { Input } from '../ui/Input';

export function ContactForm() {
    return (
        <div className="grid gap-4 md:grid-cols-2">
            <Input placeholder="Contact name" />
            <Input placeholder="Phone" />
            <Input placeholder="Email" />
            <Input placeholder="Designation" />
        </div>
    );
}
