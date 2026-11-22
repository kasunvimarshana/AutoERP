/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

export interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    path?: string;
    per_page: number;
    to: number | null;
    total: number;
}

export interface PaginationLinks {
    first?: string | null;
    last?: string | null;
    prev?: string | null;
    next?: string | null;
}
