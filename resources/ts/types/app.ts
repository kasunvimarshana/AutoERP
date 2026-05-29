export interface AppUser {
    id: number;
    name: string;
    email?: string | null;
    role?: string | null;
    avatarUrl?: string | null;
}

export interface AppBootstrap {
    appName: string;
    apiBaseUrl: string;
    user: AppUser | null;
}
