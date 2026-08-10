# Direct Vehicle Service job-line item search

Date: 2026-08-11

## Purpose

Remove the technical line-type decision from the primary Add Line workflow so users can search for the item they need directly.

## Changes

- Replaced the visible Line type selector with one search covering eligible inventory, service, labour, combo, and package items.
- Derives the required Vehicle Service line source from the selected item's authoritative item type and stockability metadata.
- Shows warehouse, location, and Add & issue stock controls only after the user selects an inventory item.
- Keeps external and customer-supplied lines available through a secondary action instead of the primary workflow.
- Added the missing required description input for external lines and a clear way to return to registered-item search.
- Preserved backend line-type and item validation as the authoritative enforcement layer.

## Verification

- Focused LineItemFields tests passed: 7 tests.
- TypeScript typecheck passed.
- Targeted ESLint passed with no warnings.
- Production Vite build passed.
- The pre-existing Vehicle Service inventory-flow source-contract suite still contains an unrelated stale assertion expecting `expectedVersion + 1`; the current committed implementation uses the authoritative mutation `rowVersion`.
