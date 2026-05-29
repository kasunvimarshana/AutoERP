import type { AppBootstrap } from './app';

declare global {
    interface Window {
        __ERP_BOOTSTRAP__?: AppBootstrap;
    }
}

export {};
