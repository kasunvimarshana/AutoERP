import { Input } from '../ui/Input';

export function QuantityInput(props: React.InputHTMLAttributes<HTMLInputElement>) {
    return <Input inputMode="decimal" placeholder="Qty" type="number" {...props} />;
}
