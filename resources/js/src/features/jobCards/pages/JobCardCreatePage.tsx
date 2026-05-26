import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo, useState } from 'react';
import { useForm, type Resolver } from 'react-hook-form';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { ConfirmModal } from '../../../components/feedback/ConfirmModal';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { PageHeader } from '../../../components/layout/PageHeader';
import { DataTable, StatusBadge, type DataTableColumn } from '../../../components/tables';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { useCustomers } from '../../customers/hooks';
import type { EmployeeRecord } from '../../employees/types';
import { useEmployees } from '../../employees/hooks';
import { ProductAutocomplete } from '../../products/components/ProductAutocomplete';
import { useProducts } from '../../products/hooks';
import type { Product } from '../../products/types';
import { formatCurrency } from '../../shared/utils';
import { useVehicles } from '../../vehicles/hooks';
import { vehicleTitle } from '../../vehicles/utils';
import { useCreateVehicleJobCard } from '../hooks';
import { jobCardFormSchema, type JobCardFormValues } from '../schemas';
import type { JobCardAssignedSubItem, JobCardOrderLine, JobCardSubItem, VehicleJobCardPayload } from '../types';

const defaultSubItems: JobCardSubItem[] = [
    { id: 'wash', sub_item_id: '270', sub_item_name: 'Body Wash with Carpets', price: 35 },
    { id: 'finish', sub_item_id: '271', sub_item_name: 'Finishing', price: 100 },
    { id: 'technical', sub_item_id: '272', sub_item_name: 'Technical', price: 100 },
];

function moneyValue(value: unknown) {
    const amount = Number(value);
    return Number.isFinite(amount) ? amount : 0;
}

function productPrice(product: Product | undefined) {
    if (!product) {
        return 0;
    }

    return moneyValue(product.metadata?.sales_price ?? product.standard_cost);
}

function employeeName(employee: EmployeeRecord | undefined) {
    if (!employee) {
        return 'Unassigned employee';
    }

    return employee.employee_code ?? (employee.job_title ? `${employee.job_title} #${employee.id}` : `Employee #${employee.id}`);
}

function makeLineId() {
    return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function StepIndicator({ step }: { step: 1 | 2 }) {
    return (
        <div className="flex border-b border-stone-200">
            {[
                { id: 1, label: 'New Job' },
                { id: 2, label: 'Crew Members' },
            ].map((item) => {
                const isActive = item.id === step;

                return (
                    <button
                        className={`flex min-w-[9rem] items-center gap-3 border-b-2 px-4 py-4 text-sm font-semibold transition ${
                            isActive ? 'border-stone-950 text-stone-950' : 'border-transparent text-stone-500'
                        }`}
                        key={item.id}
                        type="button"
                    >
                        <span className={`inline-flex h-6 w-6 items-center justify-center rounded-full text-xs ${isActive ? 'bg-stone-950 text-white' : 'bg-stone-100 text-stone-500'}`}>
                            {item.id}
                        </span>
                        {item.label}
                    </button>
                );
            })}
        </div>
    );
}

export function JobCardCreatePage() {
    const { tenantId } = useTenant();
    const { showToast } = useToast();
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const initialVehicleId = Number(searchParams.get('vehicle_id') ?? 0);
    const [step, setStep] = useState<1 | 2>(1);
    const [discardOpen, setDiscardOpen] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);
    const [productSelectionError, setProductSelectionError] = useState<string | null>(null);
    const [orderLines, setOrderLines] = useState<JobCardOrderLine[]>([]);
    const [subItems, setSubItems] = useState<JobCardSubItem[]>(defaultSubItems);
    const [assignedSubItems, setAssignedSubItems] = useState<JobCardAssignedSubItem[]>([]);
    const [subItemDraft, setSubItemDraft] = useState({ sub_item_id: '', sub_item_name: '', price: '' });
    const [submitError, setSubmitError] = useState<string | null>(null);

    const vehiclesQuery = useVehicles({ tenant_id: tenantId, page: 1, per_page: 100, sort: '-created_at' });
    const customersQuery = useCustomers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const productsQuery = useProducts({ tenant_id: tenantId, page: 1, per_page: 100, is_active: true, sort: 'name' });
    const employeesQuery = useEmployees({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'employee_code' });
    const createJobCard = useCreateVehicleJobCard();

    const {
        formState: { errors },
        handleSubmit,
        register,
        setError,
        watch,
    } = useForm<JobCardFormValues>({
        defaultValues: {
            vehicle_id: initialVehicleId > 0 ? initialVehicleId : 0,
            customer_id: 0,
            job_card_no: '',
            service_type: 'maintenance',
            mileage: 0,
            monthly_mileage: 0,
            scheduled_at: '',
            order_discount_type: 'none',
            order_discount_value: 0,
            workflow_status: 'scheduled',
            payment_status: 'unpaid',
            service_item: 'Full service',
            supervisor_user_id: 0,
        },
        mode: 'onBlur',
        resolver: zodResolver(jobCardFormSchema) as Resolver<JobCardFormValues>,
    });

    const serviceItem = watch('service_item');
    const selectedSupervisorUserId = watch('supervisor_user_id');
    const employees = employeesQuery.data?.items ?? [];
    const products = productsQuery.data?.items ?? [];

    const orderSubtotal = orderLines.reduce((total, line) => total + line.sub_total, 0);
    const orderDiscountValue = moneyValue(watch('order_discount_value'));
    const orderDiscountType = watch('order_discount_type');
    const orderDiscountAmount = orderDiscountType === 'percentage' ? orderSubtotal * (orderDiscountValue / 100) : orderDiscountType === 'fixed' ? orderDiscountValue : 0;
    const grandTotal = Math.max(0, orderSubtotal - orderDiscountAmount);

    const orderColumns = useMemo<DataTableColumn<JobCardOrderLine>[]>(
        () => [
            { key: 'product', header: 'Product', render: (line) => <span className="font-medium text-stone-800">{line.product_name}</span> },
            {
                key: 'quantity',
                header: 'Quantity',
                render: (line) => (
                    <Input
                        aria-label="Quantity"
                        className="w-24"
                        min="0"
                        onChange={(event) => updateOrderLine(line.id, { quantity: moneyValue(event.target.value) })}
                        type="number"
                        value={line.quantity}
                    />
                ),
            },
            {
                key: 'price',
                header: 'Net Unit Price',
                render: (line) => (
                    <Input
                        aria-label="Net unit price"
                        className="w-32"
                        min="0"
                        onChange={(event) => updateOrderLine(line.id, { net_unit_price: moneyValue(event.target.value) })}
                        type="number"
                        value={line.net_unit_price}
                    />
                ),
            },
            {
                key: 'discount',
                header: 'Discount',
                render: (line) => (
                    <Input
                        aria-label="Discount percentage"
                        className="w-28"
                        min="0"
                        onChange={(event) => updateOrderLine(line.id, { discount: moneyValue(event.target.value) })}
                        type="number"
                        value={line.discount}
                    />
                ),
            },
            { key: 'subtotal', header: 'Sub Total', render: (line) => <span className="font-semibold text-stone-950">{formatCurrency(line.sub_total)}</span> },
            {
                key: 'actions',
                header: '',
                render: (line) => (
                    <Button className="h-9 px-3 text-xs" onClick={() => setOrderLines((current) => current.filter((item) => item.id !== line.id))} type="button" variant="secondary">
                        Remove
                    </Button>
                ),
            },
        ],
        [],
    );

    const subItemColumns = useMemo<DataTableColumn<JobCardSubItem>[]>(
        () => [
            { key: 'id', header: 'Sub Item ID', render: (item) => item.sub_item_id },
            { key: 'name', header: 'Sub Item Name', render: (item) => item.sub_item_name },
            { key: 'allow', header: 'Allow', render: (item) => formatCurrency(item.price) },
        ],
        [],
    );

    const crewColumns = useMemo<DataTableColumn<EmployeeRecord>[]>(
        () => [
            { key: 'id', header: 'Crew ID', render: (employee) => employee.employee_code ?? employee.id },
            { key: 'name', header: 'Crew Name', render: (employee) => employeeName(employee) },
            {
                key: 'allow',
                header: 'Allow',
                render: (employee) => (
                    <Button className="h-8 px-3 text-xs" onClick={() => assignSubItemToEmployee(employee, subItems[0])} type="button" variant="secondary">
                        Add
                    </Button>
                ),
            },
        ],
        [subItems],
    );

    const assignedColumns = useMemo<DataTableColumn<JobCardAssignedSubItem>[]>(
        () => [
            { key: 'employee', header: 'Employee Name', render: (item) => item.employee_name },
            { key: 'service', header: 'Service Item', render: (item) => item.service_item },
            { key: 'sub_item', header: 'Sub Item', render: (item) => item.sub_item },
            { key: 'sub_item_id', header: 'Sub Item ID', render: (item) => item.sub_item_id },
            { key: 'incentive', header: 'Incentive Amount', render: (item) => <span className="font-semibold text-stone-950">{formatCurrency(item.incentive_amount)}</span> },
        ],
        [],
    );

    function recalculateLine(line: JobCardOrderLine, updates: Partial<JobCardOrderLine>) {
        const next = { ...line, ...updates };
        const gross = next.quantity * next.net_unit_price;
        next.sub_total = Math.max(0, gross - gross * (next.discount / 100));
        return next;
    }

    function updateOrderLine(lineId: string, updates: Partial<JobCardOrderLine>) {
        setOrderLines((current) => current.map((line) => (line.id === lineId ? recalculateLine(line, updates) : line)));
    }

    function addProductLine() {
        if (!selectedProduct) {
            setProductSelectionError('Select a product before adding it to the order.');
            return;
        }

        setProductSelectionError(null);
        setOrderLines((current) => [
            ...current,
            {
                id: makeLineId(),
                product_id: selectedProduct.id,
                product_name: selectedProduct.name,
                quantity: 1,
                net_unit_price: productPrice(selectedProduct),
                discount: 0,
                sub_total: productPrice(selectedProduct),
            },
        ]);
        setSelectedProduct(null);
    }

    function addSubItem() {
        if (!subItemDraft.sub_item_id.trim() || !subItemDraft.sub_item_name.trim()) {
            return;
        }

        setSubItems((current) => [
            ...current,
            {
                id: makeLineId(),
                sub_item_id: subItemDraft.sub_item_id.trim(),
                sub_item_name: subItemDraft.sub_item_name.trim(),
                price: moneyValue(subItemDraft.price),
            },
        ]);
        setSubItemDraft({ sub_item_id: '', sub_item_name: '', price: '' });
    }

    function assignSubItemToEmployee(employee: EmployeeRecord | undefined, subItem: JobCardSubItem | undefined) {
        if (!employee || !subItem) {
            return;
        }

        setAssignedSubItems((current) => [
            ...current,
            {
                id: makeLineId(),
                employee_name: employeeName(employee),
                service_item: serviceItem,
                sub_item: subItem.sub_item_name,
                sub_item_id: subItem.sub_item_id,
                incentive_amount: subItem.price,
            },
        ]);
    }

    async function handleNext() {
        setStep(2);
    }

    async function onSubmit(values: JobCardFormValues) {
        setSubmitError(null);

        if (!values.supervisor_user_id) {
            setError('supervisor_user_id', { message: 'Supervisor is required to submit this job card.' });
            return;
        }

        const supervisor = employees.find((employee) => employee.user_id === values.supervisor_user_id);
        const payload: VehicleJobCardPayload = {
            tenant_id: tenantId,
            vehicle_id: values.vehicle_id,
            customer_id: values.customer_id,
            assigned_mechanic_id: values.supervisor_user_id,
            job_card_no: values.job_card_no,
            workflow_status: values.workflow_status,
            service_type: values.service_type,
            scheduled_at: values.scheduled_at || null,
            tasks: [{ task_name: values.service_item, task_status: 'pending' }],
            parts: orderLines.map((line) => ({
                product_id: line.product_id ?? null,
                quantity: line.quantity,
                unit_cost: line.net_unit_price,
                line_total: line.sub_total,
                description: line.product_name,
            })),
            parts_cost_total: orderSubtotal,
            subtotal: orderSubtotal,
            grand_total: grandTotal,
            metadata: {
                mileage: values.mileage,
                monthly_mileage: values.monthly_mileage,
                next_service_date: values.scheduled_at,
                order_discount_type: values.order_discount_type,
                order_discount_value: values.order_discount_value,
                payment_status: values.payment_status,
                service_item: values.service_item,
                supervisor_user_id: values.supervisor_user_id,
                supervisor_employee_id: supervisor?.id ?? null,
                supervisor_name: employeeName(supervisor),
                order_lines: orderLines,
                sub_items: subItems,
                assigned_sub_items: assignedSubItems,
            },
        };

        try {
            const jobCard = await createJobCard.mutateAsync(payload);
            showToast({
                title: 'Job card saved',
                description: `${jobCard.job_card_no} was created successfully.`,
                tone: 'success',
            });
            navigate(`/vehicles/${jobCard.vehicle_id}/job-cards/${jobCard.id}`);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors<JobCardFormValues>(error.errors, setError);
                setSubmitError(error.message);
                return;
            }

            setSubmitError(error instanceof Error ? error.message : 'Unable to save the job card.');
        }
    }

    function renderStepOne() {
        return (
            <div className="space-y-8">
                <FormGrid className="xl:grid-cols-2">
                    <div className="grid gap-3 sm:grid-cols-[1fr_auto]">
                        <FormField error={errors.vehicle_id?.message} label="Vehicle" required>
                            <Select error={errors.vehicle_id?.message} {...register('vehicle_id', { valueAsNumber: true })}>
                                <option value={0}>Select Vehicle</option>
                                {(vehiclesQuery.data?.items ?? []).map((vehicle) => (
                                    <option key={vehicle.id} value={vehicle.id}>
                                        {vehicleTitle(vehicle)}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                        <Link className="mt-7" to="/vehicles/create">
                            <Button aria-label="Add vehicle" className="h-11 w-11 px-0" type="button" variant="secondary">
                                +
                            </Button>
                        </Link>
                    </div>
                    <FormField error={errors.job_card_no?.message} label="Manual Job Card" required>
                        <Input error={errors.job_card_no?.message} placeholder="Job Card #" {...register('job_card_no')} />
                    </FormField>
                    <div className="grid gap-3 sm:grid-cols-[1fr_auto]">
                        <FormField error={errors.customer_id?.message} label="Customer" required>
                            <Select error={errors.customer_id?.message} {...register('customer_id', { valueAsNumber: true })}>
                                <option value={0}>Select Customer</option>
                                {(customersQuery.data?.items ?? []).map((customer) => (
                                    <option key={customer.id} value={customer.id}>
                                        {customer.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                        <Link className="mt-7" to="/customers/new">
                            <Button aria-label="Add customer" className="h-11 w-11 px-0" type="button" variant="secondary">
                                +
                            </Button>
                        </Link>
                    </div>
                    <FormField error={errors.service_type?.message} label="Job Type" required>
                        <Select error={errors.service_type?.message} {...register('service_type')}>
                            <option value="maintenance">Maintenance</option>
                            <option value="repair">Repair</option>
                            <option value="inspection">Inspection</option>
                            <option value="accident">Accident</option>
                            <option value="other">Other</option>
                        </Select>
                    </FormField>
                    <FormField error={errors.mileage?.message} label="Mileage" required>
                        <Input error={errors.mileage?.message} min="0" placeholder="Current mileage" type="number" {...register('mileage')} />
                    </FormField>
                    <FormField error={errors.monthly_mileage?.message} label="Monthly Mileage" required>
                        <Input error={errors.monthly_mileage?.message} min="0" placeholder="Avg monthly mileage" type="number" {...register('monthly_mileage')} />
                    </FormField>
                    <FormField error={errors.scheduled_at?.message} label="Next Service" required>
                        <Input error={errors.scheduled_at?.message} type="date" {...register('scheduled_at')} />
                    </FormField>
                </FormGrid>

                <div className="border-t border-stone-100 pt-7">
                    <FormField error={productSelectionError ?? undefined} label="Product Search / Select">
                        <div className="grid gap-3 md:grid-cols-[1fr_auto]">
                            <ProductAutocomplete
                                error={productSelectionError ?? undefined}
                                isLoading={productsQuery.isPending}
                                onChange={(product) => {
                                    setSelectedProduct(product);
                                    setProductSelectionError(null);
                                }}
                                products={products}
                                value={selectedProduct}
                            />
                            <Button disabled={!selectedProduct} onClick={addProductLine} type="button" variant="secondary">
                                Add Product
                            </Button>
                        </div>
                    </FormField>
                </div>

                <div className="space-y-4">
                    <h3 className="text-base font-semibold text-stone-950">Order Table</h3>
                    <div className="overflow-hidden rounded-xl border border-stone-200">
                        <DataTable
                            columns={orderColumns}
                            emptyState={<div className="px-4 py-8 text-sm text-stone-500">Select a product to start the order table.</div>}
                            getRowKey={(line) => line.id}
                            rows={orderLines}
                        />
                    </div>
                </div>

                <FormGrid className="xl:grid-cols-4">
                    <FormField error={errors.order_discount_type?.message} label="Order Discount Type" required>
                        <Select error={errors.order_discount_type?.message} {...register('order_discount_type')}>
                            <option value="none">No Discount</option>
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
                        </Select>
                    </FormField>
                    <FormField error={errors.order_discount_value?.message} label="Order Discount Value" required>
                        <Input error={errors.order_discount_value?.message} min="0" placeholder="Value" type="number" {...register('order_discount_value')} />
                    </FormField>
                    <FormField error={errors.workflow_status?.message} label="Job Status" required>
                        <Select error={errors.workflow_status?.message} {...register('workflow_status')}>
                            <option value="draft">Draft</option>
                            <option value="scheduled">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="awaiting_parts">Awaiting Parts</option>
                            <option value="quality_check">Quality Check</option>
                            <option value="completed">Completed</option>
                        </Select>
                    </FormField>
                    <FormField error={errors.payment_status?.message} label="Payment Status" required>
                        <Select error={errors.payment_status?.message} {...register('payment_status')}>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial_paid">Partial Paid</option>
                            <option value="paid">Paid</option>
                        </Select>
                    </FormField>
                </FormGrid>
            </div>
        );
    }

    function renderStepTwo() {
        return (
            <div className="space-y-8">
                <div className="rounded-xl border border-stone-200 p-5">
                    <FormGrid className="xl:grid-cols-2">
                        <FormField error={errors.service_item?.message} label="Service Item" required>
                            <Select error={errors.service_item?.message} {...register('service_item')}>
                                <option value="Full service">Full service</option>
                                <option value="Engine service">Engine service</option>
                                <option value="Brake service">Brake service</option>
                                <option value="Inspection">Inspection</option>
                            </Select>
                        </FormField>
                        <FormField error={errors.supervisor_user_id?.message} label="Supervisor name" required>
                            <Select error={errors.supervisor_user_id?.message} {...register('supervisor_user_id', { valueAsNumber: true })}>
                                <option value={0}>Select</option>
                                {employees
                                    .filter((employee) => employee.user_id)
                                    .map((employee) => (
                                        <option key={employee.id} value={employee.user_id}>
                                            {employeeName(employee)}
                                        </option>
                                    ))}
                            </Select>
                        </FormField>
                    </FormGrid>
                </div>

                <div className="grid gap-7 xl:grid-cols-2">
                    <div className="space-y-4">
                        <h3 className="text-base font-semibold text-stone-950">Sub items</h3>
                        <div className="overflow-hidden rounded-xl border border-stone-200">
                            <DataTable columns={subItemColumns} getRowKey={(item) => item.id} rows={subItems} />
                        </div>
                    </div>
                    <div className="space-y-4">
                        <h3 className="text-base font-semibold text-stone-950">All Crew members</h3>
                        <div className="overflow-hidden rounded-xl border border-stone-200">
                            <DataTable
                                columns={crewColumns}
                                emptyState={<div className="px-4 py-8 text-sm text-stone-500">No crew members found for this tenant.</div>}
                                getRowKey={(employee) => employee.id}
                                rows={employees}
                            />
                        </div>
                    </div>
                </div>

                <div className="grid items-end gap-4 md:grid-cols-[0.7fr_1.4fr_1fr_auto]">
                    <FormField label="Sub Item ID">
                        <Input onChange={(event) => setSubItemDraft((current) => ({ ...current, sub_item_id: event.target.value }))} value={subItemDraft.sub_item_id} />
                    </FormField>
                    <FormField label="Sub Item Name">
                        <Input onChange={(event) => setSubItemDraft((current) => ({ ...current, sub_item_name: event.target.value }))} value={subItemDraft.sub_item_name} />
                    </FormField>
                    <FormField label="Price / Incentive">
                        <Input min="0" onChange={(event) => setSubItemDraft((current) => ({ ...current, price: event.target.value }))} type="number" value={subItemDraft.price} />
                    </FormField>
                    <Button onClick={addSubItem} type="button">
                        Add
                    </Button>
                </div>

                <div className="space-y-4">
                    <h3 className="text-base font-semibold text-stone-950">Assigned Sub items</h3>
                    <div className="overflow-hidden rounded-xl border border-stone-200">
                        <DataTable
                            columns={assignedColumns}
                            emptyState={<div className="px-4 py-8 text-sm text-stone-500">Add a crew member to assign service sub items.</div>}
                            getRowKey={(item) => item.id}
                            rows={assignedSubItems}
                        />
                    </div>
                </div>

                {selectedSupervisorUserId ? null : <p className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Supervisor is required to submit this job card.</p>}
            </div>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <>
                        <Button type="button" variant="secondary">
                            Drafts (0)
                        </Button>
                        <Button onClick={() => setStep(1)} type="button" variant="secondary">
                            Save Progress
                        </Button>
                    </>
                }
                breadcrumbs={[{ label: 'Job Cards', href: '/job-cards' }, { label: 'New Job Card' }]}
                description="Initiate a new service record for a customer vehicle"
                title="New Job Card"
            />

            <ContentCard className="p-0">
                <StepIndicator step={step} />
                <form className="space-y-8 p-6 lg:p-8" onSubmit={handleSubmit(onSubmit)}>
                    {step === 1 ? renderStepOne() : renderStepTwo()}

                    {submitError ? <p className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{submitError}</p> : null}

                    <div className="flex flex-col gap-3 border-t border-stone-200 pt-6 sm:flex-row sm:justify-end">
                        {step === 2 ? (
                            <Button onClick={() => setStep(1)} type="button" variant="secondary">
                                Back
                            </Button>
                        ) : null}
                        <Button onClick={() => setDiscardOpen(true)} type="button" variant="secondary">
                            Discard
                        </Button>
                        {step === 1 ? (
                            <Button onClick={() => void handleNext()} type="button">
                                Next
                            </Button>
                        ) : (
                            <Button disabled={createJobCard.isPending} type="submit">
                                {createJobCard.isPending ? 'Saving...' : 'Save Job Card'}
                            </Button>
                        )}
                    </div>
                </form>
            </ContentCard>

            <ConfirmModal
                confirmLabel="Discard"
                description="Discard this job card draft and return to the job card list?"
                onCancel={() => setDiscardOpen(false)}
                onConfirm={() => navigate('/job-cards')}
                open={discardOpen}
                title="Discard job card"
            />
        </div>
    );
}
