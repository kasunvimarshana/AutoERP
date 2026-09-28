import { forwardRef, type ChangeEvent, type InputHTMLAttributes } from 'react';
import { isDecimalString, normalizeDecimalInput } from '@/shared/utils/decimal';
import { Input } from './Input';

const incompleteDecimalPattern = /^-?\d+\.$/;

export interface DecimalInputProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'inputMode' | 'onChange'> {
    error?: string;
    label?: string;
    hint?: string;
    maxFractionDigits?: number;
    onChange: (event: ChangeEvent<HTMLInputElement>) => void;
}

export const DecimalInput = forwardRef<HTMLInputElement, DecimalInputProps>(function DecimalInput(
    { onChange, maxFractionDigits, ...props },
    ref,
) {
    return (
        <Input
            ref={ref}
            type="text"
            inputMode="decimal"
            onChange={(event) => {
                let next = normalizeDecimalInput(event.target.value);
                if (maxFractionDigits !== undefined) {
                    const [integer, fraction] = next.split('.');
                    if (fraction !== undefined) {
                        next = `${integer}.${fraction.slice(0, Math.max(0, maxFractionDigits))}`;
                    }
                }
                if (!isDecimalString(next) && !incompleteDecimalPattern.test(next)) return;
                event.target.value = next;
                onChange(event);
            }}
            {...props}
        />
    );
});
