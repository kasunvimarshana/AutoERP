import { Input } from '../ui/Input';

export function BankAccountForm() {
    return (
        <div className="grid gap-4 md:grid-cols-2">
            <Input placeholder="Bank name" />
            <Input placeholder="Branch" />
            <Input placeholder="Account number" />
            <Input placeholder="Account holder" />
        </div>
    );
}
