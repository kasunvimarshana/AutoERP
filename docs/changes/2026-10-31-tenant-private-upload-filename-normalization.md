# Normalize untrusted tenant-private upload filenames — 2026-10-31

## Root cause

`TenantPrivateFileService::storeFile()` persisted the original upload filename using PHP `basename(trim(...))`. On POSIX PHP, `basename()` does **not** recognize Windows backslashes as directory separators. An untrusted client-supplied filename such as `C:\\Users\\Accountant\\Private\\receipt.pdf` could therefore persist a client-side path rather than the intended leaf name. ASCII control characters could also remain inside persisted and subsequently presented attachment names.

This is owned by Tenant's private file storage service, not by Rental's incident or HTTP presentation modules.

## Minimal implementation

- Normalize both Windows and POSIX directory separators before taking the leaf filename.
- Remove ASCII control characters before persisting the filename.
- Retain the existing fallback `file.bin` for empty names and unsafe dot-only leaf names; preserve Unicode leaf names and the existing 255-character limit.
- Leave object-key generation, tenant isolation, file contents, authorization, allocation and financial code unchanged.
- Add a focused application regression test under `tests/Feature/Tenant/TenantPrivateFileServiceTest.php` for Windows/POSIX paths, CR/LF, blank filenames, Unicode, and private object storage.

## Verification actually performed

- Read the exact source implementation and the Tenant storage path policy, existing PrivateObject tests, and Rental incident attachment integration tests before changing code.
- Executed PHP 8.4.24 standalone normalization cases against the same expression and inputs: **5/5 passed**, with `php -l` passing for the standalone check script.
- The repository's PHP dependencies, source checkout and disposable database are unavailable to this execution environment; the newly added Laravel test, full backend/frontend suites, SQLite/MySQL migrations, UAT and production smoke were **not executed** here. They must be run on this changed branch/commit before integration or release.
- Published to isolated `fix/tenant-private-upload-filenames-20261031` instead of updating PR #127 or `worktree-0.0.8`, because those routes trigger GitHub Actions, which the user prohibits. No Actions runs, paid services or production mutations were used.

## Release status

This fixes one independently evidenced file-metadata sanitization issue. It does not close Rental TODO §R's nine financial release gates, draft PR #127, open issues #124/#128, or the need for exact-head tests, external accounting acceptance and deployment smoke. Do not describe the tested pre-change integration candidate as the tested status of this changed branch.
