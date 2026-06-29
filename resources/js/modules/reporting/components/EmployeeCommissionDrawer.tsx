import { Button, LinkButton } from '@/shared/components/Button';
import { Drawer } from '@/shared/components/Drawer';
import { Panel } from '@/shared/components/Panel';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { formatQuantity } from '@/shared/utils/formatQuantity';
import { humanize } from '@/shared/utils/object';
import type {
    EmployeeCommissionGroup,
    EmployeeCommissionReportRow,
} from '../reportingTypes';

interface EmployeeCommissionDrawerProps {
    row: EmployeeCommissionReportRow | null;
    group?: EmployeeCommissionGroup;
    onClose: () => void;
    onFocus: (row: EmployeeCommissionReportRow) => void;
}

export function EmployeeCommissionDrawer({
    row,
    group,
    onClose,
    onFocus,
}: EmployeeCommissionDrawerProps) {
    return (
        <Drawer open={row !== null} title={row?.employee_name ?? 'Employee'} onClose={onClose}>
            {row && (
                <div className="space-y-5">
                    <Panel title="Employee">
                        <dl className="grid gap-3 sm:grid-cols-2">
                            <Detail label="Code" value={row.employee_code} />
                            <Detail label="Department" value={row.department_name} />
                            <Detail label="Designation" value={row.designation_name} />
                            <Detail label="Role" value={humanize(row.role_type)} />
                        </dl>
                        <div className="mt-4 flex gap-2">
                            <LinkButton to={`/hr/employees/${row.employee.id}`} variant="secondary">Employee record</LinkButton>
                            <Button type="button" onClick={() => onFocus(row)}>Focus report</Button>
                        </div>
                    </Panel>
                    {group && (
                        <Panel title="Filtered totals">
                            <dl className="grid gap-3 sm:grid-cols-2">
                                <Detail label="Jobs" value={String(group.total_jobs)} />
                                <Detail label="Hours" value={formatQuantity(group.total_hours)} />
                                <Detail label="Labour value" value={formatMoney(group.total_labour_value)} />
                                <Detail label="Commission" value={formatMoney(group.total_commission)} />
                            </dl>
                        </Panel>
                    )}
                    <Panel title="Selected commission">
                        <dl className="grid gap-3 sm:grid-cols-2">
                            <Detail label="Job" value={row.job_number} />
                            <Detail label="Date" value={formatDate(row.job_date)} />
                            <Detail label="Source" value={humanize(row.commission_source)} />
                            <Detail label="Commission status" value={humanize(row.commission_status)} />
                            <Detail label="Labour value" value={formatMoney(row.labour_amount)} />
                            <Detail label="Commission base" value={formatMoney(row.commission_base)} />
                            <Detail label="Commission" value={formatMoney(row.commission_amount)} />
                            <Detail label="Invoice progress" value={humanize(row.invoice_progress)} />
                            <Detail label="Payment progress" value={humanize(row.payment_progress)} />
                            <Detail label="Balance due" value={formatMoney(row.balance_due)} />
                        </dl>
                    </Panel>
                </div>
            )}
        </Drawer>
    );
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return (
        <div>
            <dt className="text-xs font-semibold uppercase text-slate-500">{label}</dt>
            <dd className="mt-1 text-slate-900">{value || '-'}</dd>
        </div>
    );
}
