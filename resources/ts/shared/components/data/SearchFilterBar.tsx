import { Input } from '../ui/Input';

type SearchFilterBarProps = {
    onSearch?: (value: string) => void;
    placeholder?: string;
};

export function SearchFilterBar({ onSearch, placeholder = 'Search records...' }: SearchFilterBarProps) {
    return (
        <div className="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
            <Input
                className="md:max-w-md"
                onChange={(event) => onSearch?.(event.target.value)}
                placeholder={placeholder}
                type="search"
            />
            <div className="flex items-center gap-2 text-sm text-slate-500">
                <span className="rounded-full bg-slate-100 px-3 py-1">Filters</span>
                <span className="rounded-full bg-slate-100 px-3 py-1">Saved views</span>
            </div>
        </div>
    );
}
