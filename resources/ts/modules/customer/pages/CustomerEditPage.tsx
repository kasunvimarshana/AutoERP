import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { CustomerForm } from '../components/CustomerForm';
import { customerApi } from '../services/customerApi';
import type { Customer } from '../types/customer.types';

export function CustomerEditPage() {
    const { id } = useParams();
    const [customer, setCustomer] = useState<Customer | null>(null);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;

        customerApi
            .getCustomer(id ?? '')
            .then((response) => {
                if (mounted) {
                    setCustomer(response.data);
                }
            })
            .catch((caught: unknown) => {
                if (mounted) {
                    setError(caught instanceof Error ? caught.message : 'Unable to load customer.');
                }
            })
            .finally(() => {
                if (mounted) {
                    setIsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, [id]);

    if (isLoading) {
        return <EmptyState description="Loading customer profile..." title="Loading customer" />;
    }

    if (error || !customer) {
        return <EmptyState description={error || 'Customer was not found.'} title="Unable to edit customer" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Master Data"
                subtitle="Edit permitted customer inputs. Backend will validate tenant scope, tax profile, finance references, and status rules later."
                title={`Edit ${customer.name}`}
            />
            <CustomerForm customer={customer} mode="edit" />
        </div>
    );
}
