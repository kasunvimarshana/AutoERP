const WHATSAPP_SHARE_ORIGIN = 'https://wa.me';

export function openPendingWhatsAppWindow(): Window | null {
    return window.open('', '_blank');
}

export function navigateToWhatsApp(value: string, pendingWindow: Window | null): boolean {
    const url = resolveWhatsAppUrl(value);
    if (!url) {
        pendingWindow?.close();
        return false;
    }

    if (pendingWindow) {
        pendingWindow.opener = null;
        pendingWindow.location.href = url;
    } else {
        window.location.assign(url);
    }

    return true;
}

export function closePendingWhatsAppWindow(pendingWindow: Window | null): void {
    pendingWindow?.close();
}

function resolveWhatsAppUrl(value: string): string | null {
    try {
        const url = new URL(value);
        return url.origin === WHATSAPP_SHARE_ORIGIN && url.protocol === 'https:' ? url.href : null;
    } catch {
        return null;
    }
}
