import { CustomerVehicleSelector } from './CustomerVehicleSelector';
import { OrderItemsTable } from './OrderItemsTable';
import { Button } from '../../../shared/components/ui/Button';
import { DatePicker } from '../../../shared/components/ui/DatePicker';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { useVehicleServiceMock } from '../hooks/useVehicleServiceMock';

export function JobCardForm() {
    const data = useVehicleServiceMock();
    const vehicleOptions = data.vehicles.map((vehicle) => ({ label: vehicle.label, value: vehicle.id }));
    const customerOptions = data.customers.map((customer) => ({ label: customer.label, value: customer.id }));
    const jobTypeOptions = data.jobTypes.map((jobType) => ({ label: jobType.label, value: jobType.id }));
    const productOptions = data.products.map((product) => ({ label: product.label, value: product.id }));

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm md:p-8">
            <div className="grid gap-8">
                <div className="grid gap-6 lg:grid-cols-2">
                    <CustomerVehicleSelector label="Vehicle" options={vehicleOptions} placeholder="Select Vehicle" />
                    <label className="space-y-2">
                        <span className="text-xs font-bold uppercase tracking-wide text-slate-500">Manual Job Card</span>
                        <Input placeholder="Job Card #" />
                    </label>
                    <CustomerVehicleSelector label="Customer" options={customerOptions} placeholder="Select Customer" />
                    <label className="space-y-2">
                        <span className="text-xs font-bold uppercase tracking-wide text-slate-500">Job Type</span>
                        <Select options={jobTypeOptions} placeholder="Select Job Type" />
                    </label>
                </div>

                <div className="border-t border-slate-100" />

                <div className="grid gap-6 lg:grid-cols-2">
                    <label className="space-y-2">
                        <span className="text-xs font-bold uppercase tracking-wide text-slate-500">Mileage</span>
                        <Input placeholder="Current mileage" />
                    </label>
                    <label className="space-y-2">
                        <span className="text-xs font-bold uppercase tracking-wide text-slate-500">Monthly Mileage</span>
                        <Input placeholder="Avg monthly mileage" />
                    </label>
                    <label className="space-y-2">
                        <span className="text-xs font-bold uppercase tracking-wide text-slate-500">Next Service</span>
                        <DatePicker />
                    </label>
                </div>

                <div className="border-t border-slate-100" />

                <Select options={productOptions} placeholder="Select Product (keyword)" />

                <section className="space-y-4">
                    <h2 className="text-base font-bold text-slate-950">Order Table</h2>
                    <OrderItemsTable items={data.orderItems} />
                </section>

                <div className="grid gap-4 lg:grid-cols-4">
                    <label className="space-y-2">
                        <span className="text-xs font-bold uppercase tracking-wide text-slate-500">Order Discount Type</span>
                        <Select
                            options={[
                                { label: 'Percentage', value: 'percentage' },
                                { label: 'Fixed', value: 'fixed' },
                            ]}
                            placeholder="Order Discount..."
                        />
                    </label>
                    <label className="space-y-2">
                        <span className="text-xs font-bold uppercase tracking-wide text-slate-500">Order Discount Value</span>
                        <Input placeholder="Value" />
                    </label>
                    <label className="space-y-2">
                        <span className="text-xs font-bold uppercase tracking-wide text-slate-500">Job Status</span>
                        <Select options={data.statuses.job} placeholder="Pending" />
                    </label>
                    <label className="space-y-2">
                        <span className="text-xs font-bold uppercase tracking-wide text-slate-500">Payment Status</span>
                        <Select options={data.statuses.payment} placeholder="Unpaid" />
                    </label>
                </div>

                <div className="flex justify-end pt-4">
                    <Button>Next</Button>
                </div>
            </div>
        </div>
    );
}
