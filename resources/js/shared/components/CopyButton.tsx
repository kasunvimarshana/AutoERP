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
            {copied ? (
                <>
                    <svg className="h-4 w-4 shrink-0 text-emerald-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fillRule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clipRule="evenodd" />
                    </svg>
                    <span>{copiedLabel}</span>
                </>
            ) : (
                <>
                    <svg className="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M7 3.5A1.5 1.5 0 018.5 2h3.879a1.5 1.5 0 011.06.44l3.122 3.12a1.5 1.5 0 01.439 1.061V16.5A1.5 1.5 0 0115.5 18h-7A1.5 1.5 0 017 16.5v-13z" />
                        <path d="M4.5 6A1.5 1.5 0 003 7.5v10A1.5 1.5 0 004.5 19h7a1.5 1.5 0 001.5-1.5v-.5H8.5A2.5 2.5 0 016 14.5v-7h-.5A1 1 0 004.5 6z" />
                    </svg>
                    <span>{label}</span>
                </>
            )}
        </Button>
    );
}
