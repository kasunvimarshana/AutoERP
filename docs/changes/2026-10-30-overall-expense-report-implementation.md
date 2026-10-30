# Overall expense report implementation

Date: 2026-10-30

## Why

The Expense Report read only `expenses`, so posted consumable usage and other Profit and Loss expense entries were absent. The payment-method panel was also unclear when all activity came from consumable usage, which has no payment method.

## Changed

- The Expense Report now reads dated expense-account entries from the Finance ledger, using the same account classification and net debit/credit basis as Finance Profit and Loss. Original postings and reversal journals appear on their respective ledger dates.
- The headline splits the selected total into paid expenses, consumable usage, and other P&L expenses. Paid Expense Type and payment-method summaries use saved expense snapshots. Consumable usage is summarized by item and net quantity. Other entries are summarized by Finance account.
- Added source and item filters, a combined activity table with Finance journal links, and consistent export rows from the same ledger query. The primary UI shows date and source; detailed filters remain expandable.
- The Summary Report's expense snapshot now uses the combined ledger total and labels its type breakdown as paid expenses only.

## Verification

- Paid expense posting and reversal test: report total, Expense Type, and payment method remain correct.
- Consumable usage posting and reversal test: item quantity and amount appear, match Finance Profit and Loss, and net to zero after reversal; export rows use the same amount.
- Expense Report page test, TypeScript typecheck, and targeted ESLint checks.

No migrations or transactional data were changed. As with the existing Finance Profit and Loss statement, ledger amounts follow Finance's current currency handling; multi-currency journal normalization belongs in Finance rather than this report.
