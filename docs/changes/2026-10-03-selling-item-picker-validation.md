# Selling item picker validation

## Request

Allow posting a sale after adding stocked item lines without requiring another item selection in the search field.

## Changes

- Removed required-field validation from the Selling item lookup. It is an add-item control, while the selected sale items are tracked as sale lines; an empty lookup must not block submission.

## Verification

- Confirmed the item lookup is separate from the sale lines state and that the sale submit handler validates the lines.
- Automated tests were not run.
