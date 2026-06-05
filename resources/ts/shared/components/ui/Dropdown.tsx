import type { ReactNode } from 'react';

type DropdownProps = {
    children: ReactNode;
    trigger: ReactNode;
};

export function Dropdown({ children, trigger }: DropdownProps) {
    return (
        <div className="group relative inline-flex">
            {trigger}
            <div className="pointer-events-none absolute right-0 top-full z-20 mt-2 min-w-48 rounded-lg border border-slate-200 bg-white p-2 opacity-0 shadow-xl transition group-focus-within:pointer-events-auto group-focus-within:opacity-100 group-hover:pointer-events-auto group-hover:opacity-100">
                {children}
            </div>
        </div>
    );
}
