import { Input } from '../ui/Input';

export function MoneyInput(props: React.InputHTMLAttributes<HTMLInputElement>) {
    return <Input inputMode="decimal" placeholder="0.00" type="number" {...props} />;
}
