import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { PurchaseDocumentShell, PurchasePageHeader } from './PurchaseDocumentShell';

describe('PurchaseDocumentShell', () => {
    it('uses a full-width content area when no summary is supplied', () => {
        const { container } = render(
            <PurchaseDocumentShell header={<PurchasePageHeader title="Supplier invoice" />}>
                <section>Main document content</section>
            </PurchaseDocumentShell>,
        );

        expect(screen.getByText('Main document content')).toBeInTheDocument();
        expect(container.querySelector('aside')).not.toBeInTheDocument();
        expect(container.querySelector('.xl\\:grid-cols-\\[minmax\\(0\\,1fr\\)_320px\\]')).not.toBeInTheDocument();
    });

    it('renders the summary column only when summary content exists', () => {
        const { container } = render(
            <PurchaseDocumentShell
                header={<PurchasePageHeader title="Supplier payment" />}
                summary={<section>Payment Total</section>}
            >
                <section>Payment methods</section>
            </PurchaseDocumentShell>,
        );

        expect(screen.getByText('Payment Total')).toBeInTheDocument();
        expect(container.querySelector('aside')).toBeInTheDocument();
    });
});
