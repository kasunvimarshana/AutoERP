export type ReferenceCatalog = 'countries' | 'currencies' | 'languages' | 'timezones';

export interface ReferenceRecord {
    id: number;
    code?: string;
    name: string;
    display_name?: string | null;
    phone_code?: string | null;
    symbol?: string | null;
    decimal_places?: number;
    native_name?: string | null;
    current_utc_offset?: string;
    is_active: boolean;
    row_version: number;
    updated_at: string | null;
}
