import { useLayoutEffect, useState } from 'react';

const INITIAL_ADMINISTRATOR_TOKEN_PATTERN = /^[a-f0-9]{64}$/;
const PLATFORM_OPERATOR_TOKEN_PATTERN = /^[A-Za-z0-9_-]{72}$/;

export function useInitialAdministratorInvitationToken(): string | null {
    return useInvitationToken(INITIAL_ADMINISTRATOR_TOKEN_PATTERN);
}

export function usePlatformOperatorInvitationToken(): string | null {
    return useInvitationToken(PLATFORM_OPERATOR_TOKEN_PATTERN);
}

function useInvitationToken(pattern: RegExp): string | null {
    const [token] = useState(() => readFragmentToken(pattern));

    useLayoutEffect(() => {
        const url = new URL(window.location.href);
        const containsSensitiveLocationData = url.hash !== '' || url.searchParams.has('token');
        if (!containsSensitiveLocationData) return;

        url.hash = '';
        url.searchParams.delete('token');
        window.history.replaceState(
            window.history.state,
            '',
            `${url.pathname}${url.search}`,
        );
    }, []);

    return token;
}

function readFragmentToken(pattern: RegExp): string | null {
    const value = (new URLSearchParams(window.location.hash.replace(/^#/, '')).get('token') ?? '').trim();

    return pattern.test(value) ? value : null;
}
