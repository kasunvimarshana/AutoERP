# Shared Attachment Layer

`FileStorageServiceInterface` is the platform storage abstraction. The Extension module's
`AttachmentService` is the only owner of business-document binaries and their metadata.
Modules must not write files directly or persist client-supplied storage paths.

## Ownership

Every attachment uses a configured polymorphic alias from
`extension.attachments.attachables`. The target resolver loads that model and verifies its
tenant and organization-unit ownership before a file is stored. New modules add an alias
to that registry instead of creating another file service or document table.

Domain records such as customer, supplier, employee, and vehicle compliance documents may
still track document number, validity dates, and status. They are business metadata, not
file stores. Binary files attach either to that compliance record or to its parent entity.

## Security

- `public`: visible to authenticated users in the owning tenant, across organization units.
- `private`: visible to the owning organization unit; tenant-level targets remain tenant-wide.
- `restricted`: private scope plus uploader-only access.
- Storage paths, disk names, MIME types, sizes, checksums, and version identifiers are
  server-derived and are not accepted from clients.
- Files are stored on the configured private disk by default and downloaded through an
  authenticated, scoped endpoint.
- Deletes are soft deletes. Physical content is retained for audit and version recovery.

## API

Base path: `/api/extension/attachments`

- `POST /` uploads a file for `attachable_type` and `attachable_id`.
- `GET /` filters by target, module, category, visibility, and current/all versions.
- `PATCH /{id}` updates mutable business metadata with `row_version`.
- `POST /{id}/versions` uploads a new immutable file version.
- `GET /{id}/versions` returns the version chain.
- `GET /{id}/download` streams the file.
- `GET /{id}/preview` streams supported image, PDF, or text content inline.
- `DELETE /{id}` soft-deletes metadata without deleting the stored object.

`size_bytes` is returned as a decimal string. The database stores integer bytes, and no
floating-point conversion is used.

Preview support intentionally reuses the original content for safe browser-supported MIME
types. `thumbnail_path` is reserved for a future asynchronous image/PDF renderer; no
renderer dependency is required by the core document layer.
