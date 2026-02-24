# Media Module
Tenant-scoped file upload and media management with multi-disk storage support.
## Features
- Upload files to local, S3, GCS, Azure disks
- Per-tenant file isolation
- Polymorphic attachment to any model
- Temporary URL generation
- MIME type and size validation
## Routes
- GET /api/v1/media
- POST /api/v1/media/upload
- GET /api/v1/media/{id}
- DELETE /api/v1/media/{id}
- GET /api/v1/media/{id}/temporary-url
