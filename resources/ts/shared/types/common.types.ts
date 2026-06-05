export type Id = string | number;

export type StatusTone = 'default' | 'success' | 'warning' | 'danger' | 'info' | 'dark';

export type EntityStatus = 'draft' | 'pending' | 'active' | 'posted' | 'closed' | 'cancelled' | 'reversed';

export type ModuleSummaryCard = {
    label: string;
    value: string;
    helper: string;
    tone?: StatusTone;
};
