import { forwardRef, useEffect, useState, type ChangeEvent, type InputHTMLAttributes } from 'react';
import { finalizeDecimalInput, formatDecimalInputDisplay, isDecimalString, normalizeDecimalInput } from '@/shared/utils/decimal';
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
    { onChange, onBlur, onFocus, value, maxFractionDigits, ...props },
    ref,
) {
    const [focused, setFocused] = useState(false);
    const [inputValue, setInputValue] = useState(() => formatDecimalInputDisplay(value as string | number | undefined));

    useEffect(() => {
        if (!focused) setInputValue(formatDecimalInputDisplay(value as string | number | undefined));
    }, [focused, value]);

    return (
        <Input
            ref={ref}
            type="text"
            inputMode="decimal"
            {...props}
            value={inputValue}
            onFocus={(event) => {
                setFocused(true);
                onFocus?.(event);
            }}
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
                setInputValue(next);
                onChange(event);
            }}
            onBlur={(event) => {
                const finalized = finalizeDecimalInput(event.target.value);
                if (finalized !== event.target.value) {
                    event.target.value = finalized;
                    onChange(event);
                }
                setInputValue(formatDecimalInputDisplay(finalized));
                setFocused(false);
                onBlur?.(event);
            }}
        />
    );
});
