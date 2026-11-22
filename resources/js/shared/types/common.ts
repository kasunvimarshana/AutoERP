/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

export type Identifier = number;

export interface NamedResource {
    id: Identifier;
    code?: string | null;
    name: string;
    symbol?: string | null;
    is_default?: boolean;
}

export type UnknownRecord = Record<string, unknown>;

export interface SelectOption {
    value: string | number;
    label: string;
}
