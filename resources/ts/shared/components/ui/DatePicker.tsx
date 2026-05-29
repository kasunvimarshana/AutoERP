import type { InputHTMLAttributes } from 'react';
import { Input } from './Input';

export function DatePicker(props: InputHTMLAttributes<HTMLInputElement>) {
    return <Input type="date" {...props} />;
}
