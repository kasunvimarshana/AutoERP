import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { WhatsAppContactCell } from './WhatsAppContactCell';
import { selectWhatsAppVerificationPhone } from '@/shared/utils/whatsAppVerificationTarget';

describe('WhatsApp contact presentation', () => {
    it('selects the active primary contact before the master number', () => {
        expect(selectWhatsAppVerificationPhone('0711111111', [
            { contact_name: 'Secondary', mobile: '0722222222', is_primary: false, is_active: true },
            { contact_name: 'Primary', mobile: '0771234567', is_primary: true, is_active: true },
        ])).toBe('0771234567');
    });

    it('shows the normalized number and an accessible verified indicator', () => {
        render(<WhatsAppContactCell
            contact={{ name: 'Primary', phone: '94771234567', source: 'customer_primary_contact_mobile', status: 'verified' }}
            email="customer@example.test"
        />);

        expect(screen.getByText('94771234567')).toBeInTheDocument();
        expect(screen.getByLabelText('Manually verified')).toBeInTheDocument();
        expect(screen.getByText('customer@example.test')).toBeInTheDocument();
    });
});

