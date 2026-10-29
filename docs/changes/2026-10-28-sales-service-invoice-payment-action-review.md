# Sales and service invoice payment action review

Reviewed invoice detail, Selling sale detail, Finance payment entry, Vehicle Service job detail, and Vehicle Service payment preparation without changing application code.

The invoice detail `Create Payment` action is limited to payable purchase invoices and does not depend on whether the invoice was opened through Finance or Purchase. Sales invoices have `Receive payment` on the Selling sale detail, which opens Finance payment entry with the invoice selected. The Finance invoice detail itself has no sales payment action. Service invoices have `Pay this invoice` on Finance invoice detail when a linked vehicle service job can be resolved, the invoice is payable, and the user has Vehicle Service payment permission. The job detail also has `Receive payment`. The service invoice action opens the job payment screen without an invoice parameter; if multiple linked invoices are payable, the user must choose the intended invoice there.

This was a read-only code review. No source changes or test runs were needed.
