interface WhatsAppContactCandidate {
    contact_name?: string | null;
    mobile?: string | null;
    is_primary?: boolean;
    is_active?: boolean;
}

export function selectWhatsAppVerificationPhone(
    masterMobile: string | null | undefined,
    contacts: WhatsAppContactCandidate[],
): string | null {
    const contact = contacts
        .filter((row) => row.is_active !== false && Boolean(row.mobile?.trim()))
        .sort((left, right) => Number(Boolean(right.is_primary)) - Number(Boolean(left.is_primary))
            || (left.contact_name ?? '').localeCompare(right.contact_name ?? ''))[0];

    return contact?.mobile?.trim() || masterMobile?.trim() || null;
}

