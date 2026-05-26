import { cn } from '../../lib/cn';

type LoadingStateProps = {
    lines?: number;
    className?: string;
};

export function LoadingState({ className, lines = 4 }: LoadingStateProps) {
    return (
        <div className={cn('animate-pulse space-y-3 rounded-3xl border border-stone-200/80 bg-white p-6', className)}>
            {Array.from({ length: lines }).map((_, index) => (
                <div key={index} className={cn('h-4 rounded-full bg-stone-100', index === 0 ? 'w-1/3' : 'w-full')} />
            ))}
        </div>
    );
}
