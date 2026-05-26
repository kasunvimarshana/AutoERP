import type { PropsWithChildren } from 'react';
import type { AppIconName } from '../../app/router/app-navigation';

type IconProps = {
    className?: string;
};

function Svg({ children, className }: PropsWithChildren<IconProps>) {
    return (
        <svg
            aria-hidden="true"
            className={className}
            fill="none"
            stroke="currentColor"
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="1.8"
            viewBox="0 0 24 24"
        >
            {children}
        </svg>
    );
}

export function AppIcon({ className, name }: { name: AppIconName; className?: string }) {
    switch (name) {
        case 'dashboard':
            return (
                <Svg className={className}>
                    <path d="M4 13h7V4H4zM13 20h7v-9h-7zM13 11h7V4h-7zM4 20h7v-5H4z" />
                </Svg>
            );
        case 'tenant':
            return (
                <Svg className={className}>
                    <path d="M4 10 12 4l8 6" />
                    <path d="M5 10v8h14v-8" />
                    <path d="M9 18v-4h6v4" />
                </Svg>
            );
        case 'users':
            return (
                <Svg className={className}>
                    <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                    <path d="M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                    <path d="M20 8v6" />
                    <path d="M23 11h-6" />
                </Svg>
            );
        case 'organization':
            return (
                <Svg className={className}>
                    <path d="M12 3v6" />
                    <path d="M6 9h12" />
                    <path d="M6 9v5" />
                    <path d="M18 9v5" />
                    <path d="M10 14H4v7h6zM20 14h-6v7h6z" />
                </Svg>
            );
        case 'employees':
            return (
                <Svg className={className}>
                    <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                    <path d="M5 20a7 7 0 0 1 14 0" />
                    <path d="M19 4v4" />
                    <path d="M21 6h-4" />
                </Svg>
            );
        case 'customers':
            return (
                <Svg className={className}>
                    <path d="M4 7h16" />
                    <path d="M4 12h10" />
                    <path d="M4 17h7" />
                    <path d="M17 18a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                    <path d="M21 21l-2.5-2.5" />
                </Svg>
            );
        case 'suppliers':
            return (
                <Svg className={className}>
                    <path d="M3 10h18" />
                    <path d="M5 10V6h14v4" />
                    <path d="M6 10v8h12v-8" />
                    <path d="M10 14h4" />
                </Svg>
            );
        case 'products':
            return (
                <Svg className={className}>
                    <path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z" />
                    <path d="m12 12 8-4.5" />
                    <path d="m12 12-8-4.5" />
                    <path d="M12 12v9" />
                </Svg>
            );
        case 'pricing':
            return (
                <Svg className={className}>
                    <path d="M12 2v20" />
                    <path d="M17 6.5a4.5 4.5 0 0 0-9 0c0 6 9 3 9 9a4.5 4.5 0 0 1-9 0" />
                </Svg>
            );
        case 'tax':
            return (
                <Svg className={className}>
                    <path d="M7 4h10l-1 5H8L7 4Z" />
                    <path d="M8 9v11" />
                    <path d="M16 9v11" />
                    <path d="M5 20h14" />
                </Svg>
            );
        case 'jobCards':
            return (
                <Svg className={className}>
                    <path d="M9 4h6" />
                    <path d="M10 2h4v4h-4z" />
                    <path d="M6 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1" />
                    <path d="M8 11h8" />
                    <path d="M8 15h5" />
                    <path d="M8 19h7" />
                </Svg>
            );
        case 'warehouses':
            return (
                <Svg className={className}>
                    <path d="m3 10 9-6 9 6" />
                    <path d="M4 10v10h16V10" />
                    <path d="M9 20v-6h6v6" />
                </Svg>
            );
        case 'inventory':
            return (
                <Svg className={className}>
                    <path d="M4 7h16" />
                    <path d="M6 7V5h12v2" />
                    <path d="M6 7v12h12V7" />
                    <path d="M9 11h6" />
                    <path d="M9 15h6" />
                </Svg>
            );
        case 'purchase':
            return (
                <Svg className={className}>
                    <path d="M6 4h14" />
                    <path d="M6 4v16" />
                    <path d="M6 8h10" />
                    <path d="M10 13h6" />
                    <path d="M10 17h6" />
                    <path d="m3 13 2 2 4-4" />
                </Svg>
            );
        case 'sales':
            return (
                <Svg className={className}>
                    <path d="M4 17 10 11l4 4 6-8" />
                    <path d="M20 7v5h-5" />
                </Svg>
            );
        case 'finance':
            return (
                <Svg className={className}>
                    <path d="M4 18h16" />
                    <path d="M6 18V9" />
                    <path d="M12 18V5" />
                    <path d="M18 18v-6" />
                </Svg>
            );
        case 'audit':
            return (
                <Svg className={className}>
                    <path d="M12 7v5l3 3" />
                    <path d="M20 12a8 8 0 1 1-2.34-5.66" />
                </Svg>
            );
        case 'settings':
            return (
                <Svg className={className}>
                    <path d="M12 3v3" />
                    <path d="M12 18v3" />
                    <path d="m4.93 4.93 2.12 2.12" />
                    <path d="m16.95 16.95 2.12 2.12" />
                    <path d="M3 12h3" />
                    <path d="M18 12h3" />
                    <path d="m4.93 19.07 2.12-2.12" />
                    <path d="m16.95 7.05 2.12-2.12" />
                    <path d="M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                </Svg>
            );
        default:
            return null;
    }
}
