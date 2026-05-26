import { cn } from '../../lib/cn';

type JsonPreviewProps = {
    value: unknown;
    className?: string;
};

export function JsonPreview({ className, value }: JsonPreviewProps) {
    if (value === null || value === undefined || value === '') {
        return <span className="text-sm text-stone-500">No data</span>;
    }

    const content = typeof value === 'string' ? value : JSON.stringify(value, null, 2);

    return <pre className={cn('max-h-72 overflow-auto rounded-2xl bg-stone-950 px-4 py-3 text-xs leading-6 text-stone-100', className)}>{content}</pre>;
}
