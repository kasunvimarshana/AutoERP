import { useState } from 'react';
import { Button } from './Button';

interface CopyButtonProps {
    value: string;
    label?: string;
    copiedLabel?: string;
    disabled?: boolean;
}

export function CopyButton({ value, label = 'Copy', copiedLabel = 'Copied', disabled = false }: CopyButtonProps) {
    const [copied, setCopied] = useState(false);

    async function copy() {
        if (disabled || value === '') return;
        await navigator.clipboard.writeText(value);
        setCopied(true);
        window.setTimeout(() => setCopied(false), 2_000);
    }

    return (
        <Button type="button" variant="secondary" disabled={disabled || value === ''} onClick={() => void copy()}>
            {copied ? copiedLabel : label}
        </Button>
    );
}
