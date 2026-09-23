# Post-invoice cancellation workflow clarification

Date: 2026-09-03

## Scope

Read-only application-code review prompted by the user's question about the proposed payment reversal -> invoice reversal -> completed-job cancellation workflow. No application behavior or database records were changed.

## Confirmed gap

- The implemented cancellation service accepts draft, inspected, in-progress, and completed jobs, subject to permissions and document protection. It rejects invoiced, partially paid, and paid job statuses regardless of administrator permissions.
- `VehicleServiceInvoiceRestorationHandler::restore()` updates invoice-link status only; it does not restore the job's status to completed.
- `VehicleServicePaymentIntegrationService::syncJobStatus()` advances billing/payment status and returns unchanged when no active invoice links remain. It does not restore the job to completed.
- The status service transition map has no invoiced/partially-paid/paid -> completed transition.

Consequently, reversing documents alone does not currently make a previously fully invoiced job cancellable. Partial invoices on jobs that remain completed are a different case: after their blocking documents are resolved, the existing completed-job cancellation path can apply.

## Required follow-up design

The proposed end-to-end unwind flow is not yet fully implemented. Payment correction/refund and invoice reversal must stay within their owning modules and their respective permissions. The Vehicle Service integration must reconcile the job's billing state after those operations, checking all linked documents before restoring completed status with locks, version handling, and status history. Only then should the existing elevated completed-job cancellation reverse stock and issue journals and remove commissions from payable totals.

Do not remove invoice/payment protection, manually change job status in the database, or treat a payment reversal as proof that an actual cash refund occurred. Further implementation requires a change request; this review only documents the missing integration.
