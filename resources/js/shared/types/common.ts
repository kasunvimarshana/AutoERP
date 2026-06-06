export type Identifier = number;

export interface NamedResource {
    id: Identifier;
    code?: string | null;
    name: string;
}

export type UnknownRecord = Record<string, unknown>;

export interface SelectOption {
    value: string | number;
    label: string;
}
