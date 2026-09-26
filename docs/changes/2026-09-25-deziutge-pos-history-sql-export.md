# Deziutge POS service history SQL export

Date: 2026-09-25

## Request

Generated a directly importable SQL artifact containing only active Deziutge POS `service` history for the 996 source records with a unique current vehicle match.

## Artifact

- File: `storage/app/private/imports/deziutge-pos-service-history-996.sql`
- File SHA-256: `7a9c4e4746ec819f6e850d60aeda9f8c4b40fbbdd52711d0fe2d92ee01fbf6c1`
- Source dump SHA-256: `D06065D44432F08EAC3CE287AE4F936374CF7473AB3AD0657A27F3894DA35DA2`
- Target context used to build and validate the file: MySQL database `laravel`, tenant 1.
- Imported content: 996 immutable history headers and 6,609 immutable service-item snapshots.
- Excluded: 55 soft-deleted service sales, 103 active service sales without a unique current vehicle match, and 1 active sale with no source vehicle.
- Vehicle IDs in the file are guarded by tenant and expected registration values. The file must be imported into the same target vehicle data set.
- Repeated imports are idempotent through existing source identity constraints and duplicate-key no-op handling.

## Verification

- Executed all 7,608 SQL statements twice inside one explicit transaction against the configured MySQL database.
- First pass produced exactly 1 batch, 996 history headers, and 6,609 item snapshots.
- Second pass produced no additional batch, header, or item rows.
- Item-to-history orphan count was zero.
- Rolled back the transaction; post-rollback batch, history, and item counts returned to zero for this source system.
- The SQL writes only Vehicle Service legacy batch/history/item tables. It creates no live jobs or finance, invoice, payment, or inventory records.
- No import was applied to persistent database state.
