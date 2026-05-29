import { Button } from '../../../components/ui/Button';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/ui/Input';
import { Select } from '../../../components/ui/Select';
import { SectionCard } from '../../../components/forms/SectionCard';
import { CustomerVehicleSelector } from './CustomerVehicleSelector';
import { JobItemsTable } from './JobItemsTable';
import { vehicleOptions, orderLines } from '../mockData';

export function JobCardForm() {
    return (
        <div className="space-y-6">
            <SectionCard title="New Job" description="Start the service record using the same clean ERP spacing and card language from the reference.">
                <div className="space-y-5">
                    <CustomerVehicleSelector vehicles={vehicleOptions} />

                    <FormGrid className="xl:grid-cols-2">
                        <FormField label="Manual Job Card" required>
                            <Input placeholder="Job Card #" />
                        </FormField>
                        <FormField label="Job Type" required>
                            <Select defaultValue="maintenance">
                                <option value="maintenance">Maintenance</option>
                                <option value="repair">Repair</option>
                                <option value="inspection">Inspection</option>
                                <option value="accident">Accident</option>
                                <option value="other">Other</option>
                            </Select>
                        </FormField>
                        <FormField label="Mileage" required>
                            <Input placeholder="Current mileage" type="number" />
                        </FormField>
                        <FormField label="Monthly Mileage" required>
                            <Input placeholder="Avg monthly mileage" type="number" />
                        </FormField>
                        <FormField label="Next Service" required>
                            <Input type="date" />
                        </FormField>
                    </FormGrid>

                    <JobItemsTable lines={orderLines} />

                    <FormGrid className="xl:grid-cols-4">
                        <FormField label="Order Discount Type" required>
                            <Select defaultValue="none">
                                <option value="none">No Discount</option>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </Select>
                        </FormField>
                        <FormField label="Order Discount Value" required>
                            <Input placeholder="Value" type="number" defaultValue="0" />
                        </FormField>
                        <FormField label="Job Status" required>
                            <Select defaultValue="scheduled">
                                <option value="draft">Draft</option>
                                <option value="scheduled">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="awaiting_parts">Awaiting Parts</option>
                                <option value="quality_check">Quality Check</option>
                                <option value="completed">Completed</option>
                            </Select>
                        </FormField>
                        <FormField label="Payment Status" required>
                            <Select defaultValue="unpaid">
                                <option value="unpaid">Unpaid</option>
                                <option value="partial_paid">Partial Paid</option>
                                <option value="paid">Paid</option>
                            </Select>
                        </FormField>
                    </FormGrid>
                </div>
            </SectionCard>

            <div className="flex justify-end gap-2 border-t border-slate-200/80 pt-5">
                <Button variant="secondary">Discard</Button>
                <Button variant="accent">Next</Button>
            </div>
        </div>
    );
}
