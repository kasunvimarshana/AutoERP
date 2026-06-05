import { DataToolbar } from './DataToolbar';

type SearchFilterBarProps = {
    onSearch?: (value: string) => void;
    placeholder?: string;
    value?: string;
};

export function SearchFilterBar({ onSearch, placeholder = 'Search records...', value = '' }: SearchFilterBarProps) {
    return <DataToolbar onSearchChange={onSearch} searchPlaceholder={placeholder} searchValue={value} />;
}
