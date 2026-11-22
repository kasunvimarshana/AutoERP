/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import type { ReactNode } from 'react';
import { AuthProvider } from '@/modules/auth/AuthProvider';

export function AppProviders({ children }: { children: ReactNode }) {
    return <AuthProvider>{children}</AuthProvider>;
}
