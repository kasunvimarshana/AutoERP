import type { ReactNode } from 'react';
import { cn } from '../../utils/cn';

export type TabItem = {
    label: string;
    value: string;
};

type TabsProps = {
    active: string;
    items: TabItem[];
    onChange: (value: string) => void;
    trailing?: ReactNode;
};

export function Tabs({ active, items, onChange, trailing }: TabsProps) {
    return (
        <div className="flex items-center justify-between border-b border-slate-200">
            <div className="flex items-center gap-6">
                {items.map((item) => (
                    <button
                        className={cn(
                            '-mb-px inline-flex h-12 items-center border-b-2 text-sm font-semibold transition',
                            active === item.value
                                ? 'border-black text-slate-950'
                                : 'border-transparent text-slate-400 hover:text-slate-700',
                        )}
                        key={item.value}
                        onClick={() => onChange(item.value)}
                        type="button"
                    >
                        {item.label}
                    </button>
                ))}
            </div>
            {trailing}
        </div>
    );
}
