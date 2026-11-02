import { forwardRef, type ChangeEvent, type InputHTMLAttributes } from 'react';
import { isDecimalString, normalizeDecimalInput } from '@/shared/utils/decimal';
import { Input } from './Input';

export interface DecimalInputProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'inputMode' | 'onChange'> {
    error?: string;
    label?: string;
    hint?: string;
    onChange: (event: ChangeEvent<HTMLInputElement>) => void;
}

export const DecimalInput = forwardRef<HTMLInputElement, DecimalInputProps>(function DecimalInput(
    { onChange, ...props },
    ref,
) {
    return (
        <Input
            ref={ref}
            type="text"
            inputMode="decimal"
            onChange={(event) => {
                const next = normalizeDecimalInput(event.target.value);
                if (!isDecimalString(next)) return;
                event.target.value = next;
                onChange(event);
            }}
            {...props}
        />
    );
});
