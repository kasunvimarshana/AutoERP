# Document Module

Enterprise-grade document management system with version control, access control, and sharing capabilities.

## Features

### Core Features
- **File Upload/Download**: Stream-based file handling with native Laravel Storage
- **Version Control**: Automatic versioning with restore capabilities
- **Folder Management**: Hierarchical folder structure with nested folders
- **Access Control**: Private, shared, and public access levels
- **Document Sharing**: Share documents with granular permissions
- **Tagging System**: Organize documents with tags
- **Full-Text Search**: Search documents by name, description, and content
- **Soft Delete**: Recover deleted documents
- **Activity Tracking**: Comprehensive audit logging

### Advanced Features
- **Permission Types**: View, Download, Edit, Delete, Share
- **Share Expiration**: Time-limited document access
- **File Metadata**: Extract and store file metadata
- **File Type Validation**: Configurable MIME type restrictions
- **Size Limits**: Configurable maximum file sizes
- **Streaming**: Efficient streaming for large files
- **Copy/Move**: Document organization operations

## Architecture

### Models
- **Document**: Main document entity with versioning
- **Folder**: Hierarchical folder structure
- **DocumentVersion**: Version history tracking
- **DocumentTag**: Tagging system
- **DocumentShare**: Sharing and permissions
- **DocumentActivity**: Activity logging

### Services
- **DocumentStorageService**: File storage operations (upload, download, delete, move, copy)
- **DocumentVersionService**: Version management (create, list, restore, compare)
- **FolderService**: Folder operations (create, move, delete, tree)
- **DocumentShareService**: Sharing and permissions
- **DocumentSearchService**: Advanced search capabilities

### Repositories
- Clean data access layer for all models
- Scoped queries with filters
- Pagination support

## API Endpoints

### Documents
```
GET    /api/documents/documents           - List documents
POST   /api/documents/documents           - Upload document
GET    /api/documents/documents/{id}      - Get document
PATCH  /api/documents/documents/{id}      - Update document
DELETE /api/documents/documents/{id}      - Delete document
GET    /api/documents/documents/{id}/download - Download document
GET    /api/documents/documents/{id}/stream   - Stream document
POST   /api/documents/documents/{id}/move     - Move document
POST   /api/documents/documents/{id}/copy     - Copy document
GET    /api/documents/documents/{id}/url      - Get temporary URL
POST   /api/documents/documents/{id}/restore  - Restore deleted
```

### Folders
```
GET    /api/documents/folders              - List folders
POST   /api/documents/folders              - Create folder
GET    /api/documents/folders/{id}         - Get folder
PATCH  /api/documents/folders/{id}         - Update folder
DELETE /api/documents/folders/{id}         - Delete folder
GET    /api/documents/folders/{id}/breadcrumbs - Get path
GET    /api/documents/folders/{id}/children    - Get children
POST   /api/documents/folders/{id}/move        - Move folder
```

### Versions
```
GET    /api/documents/documents/{id}/versions                        - List versions
GET    /api/documents/documents/{id}/versions/{number}               - Get version
POST   /api/documents/documents/{id}/versions/{number}/restore       - Restore version
GET    /api/documents/documents/{id}/versions/{v1}/compare/{v2}      - Compare versions
GET    /api/documents/documents/{id}/versions/{vid}/download         - Download version
POST   /api/documents/documents/{id}/versions/cleanup                - Cleanup old versions
```

### Sharing
```
GET    /api/documents/documents/{id}/shares        - List shares
POST   /api/documents/documents/{id}/shares        - Share document
POST   /api/documents/documents/{id}/shares/bulk   - Bulk share
PATCH  /api/documents/shares/{id}                  - Update share
DELETE /api/documents/shares/{id}                  - Revoke share
GET    /api/documents/shares/shared-with-me        - Get shared documents
GET    /api/documents/documents/{id}/permissions   - Get user permissions
POST   /api/documents/documents/{id}/check-permission - Check permission
```

### Tags
```
GET    /api/documents/tags              - List tags
POST   /api/documents/tags              - Create tag
GET    /api/documents/tags/{id}         - Get tag
PATCH  /api/documents/tags/{id}         - Update tag
DELETE /api/documents/tags/{id}         - Delete tag
GET    /api/documents/tags/{id}/documents - Get tagged documents
```

## Usage Examples

### Upload Document
```php
POST /api/documents/documents
Content-Type: multipart/form-data

file: [binary file]
folder_id: "01HQXXX"
description: "Project documentation"
status: "published"
access_level: "private"
tags: ["project", "documentation"]
```

### Share Document
```php
POST /api/documents/documents/{id}/shares

{
    "user_id": "01HQYYY",
    "permission_type": "edit",
    "expires_at": "2024-12-31T23:59:59Z"
}
```

### Search Documents
```php
GET /api/documents/documents?search=contract&status=published&from_date=2024-01-01
```

### Restore Version
```php
POST /api/documents/documents/{id}/versions/3/restore

{
    "comment": "Restored from version 3"
}
```

## Configuration

Edit `config/document.php`:

```php
'storage' => [
    'disk' => 'local',           // Storage disk
    'url_expiry' => 60,          // Temporary URL expiry (minutes)
],

'upload' => [
    'max_size' => 10485760,      // Max file size (10MB)
    'allowed_mimes' => [],       // Allowed MIME types (empty = all)
    'allowed_extensions' => [],  // Allowed extensions (empty = all)
],

'versioning' => [
    'enabled' => true,           // Enable versioning
    'max_versions' => 0,         // Max versions to keep (0 = unlimited)
],

'sharing' => [
    'default_expiry_days' => null,  // Default expiration (null = none)
    'max_expiry_days' => 365,       // Maximum expiration
],
```

## Environment Variables

```bash
# Storage
DOCUMENT_STORAGE_DISK=local
DOCUMENT_URL_EXPIRY=60

# Upload limits
DOCUMENT_MAX_SIZE=10485760
DOCUMENT_ALLOWED_MIMES=
DOCUMENT_ALLOWED_EXTENSIONS=

# Versioning
DOCUMENT_VERSIONING_ENABLED=true
DOCUMENT_MAX_VERSIONS=0

# Sharing
DOCUMENT_SHARE_EXPIRY_DAYS=
DOCUMENT_SHARE_MAX_EXPIRY_DAYS=365

# Access control
DOCUMENT_ACCESS_CONTROL=true
DOCUMENT_DEFAULT_ACCESS=private

# Activity logging
DOCUMENT_ACTIVITY_LOG=true
DOCUMENT_ACTIVITY_CLEANUP_DAYS=365
```

## Storage Configuration

Configure Laravel Storage in `config/filesystems.php`:

```php
'disks' => [
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app/documents'),
    ],
    
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
    ],
],
```

## Security

### Access Control
- **Private**: Only owner can access
- **Shared**: Owner + explicitly shared users
- **Public**: Everyone can view/download

### Permissions
- **View**: Can view document details
- **Download**: Can download document
- **Edit**: Can update document metadata
- **Delete**: Can delete document
- **Share**: Can share with others

### Authorization
All operations are protected by Laravel Policies:
- Automatic owner checks
- Share-based permissions
- Public document access
- Tenant isolation

## Database Tables

1. **folders** - Folder hierarchy
2. **documents** - Main documents table
3. **document_versions** - Version history
4. **document_tags** - Tag definitions
5. **document_tag_relations** - Document-tag M:N
6. **document_shares** - Sharing records
7. **document_activities** - Activity log

## Events

- `DocumentUploaded` - When document is uploaded
- `DocumentDownloaded` - When document is downloaded
- `DocumentShared` - When document is shared
- `DocumentDeleted` - When document is deleted
- `VersionCreated` - When new version is created

## Best Practices

1. **Use streaming for large files** - Efficient memory usage
2. **Enable versioning** - Track document history
3. **Set share expiration** - Limit access duration
4. **Use tags for organization** - Better searchability
5. **Implement virus scanning** - Secure uploads
6. **Configure storage limits** - Prevent abuse
7. **Monitor activity logs** - Audit trail

## Extending

### Add Custom Metadata
```php
// In DocumentStorageService::extractMetadata()
$metadata['custom_field'] = $value;
```

### Custom Permission Logic
```php
// Extend DocumentShareService::checkPermission()
```

### Add Document Processors
```php
// Create new service for PDF processing, image manipulation, etc.
```

## Testing

```bash
# Run migrations
php artisan migrate

# Test upload
curl -X POST http://localhost/api/documents/documents \
  -H "Authorization: Bearer {token}" \
  -F "file=@document.pdf" \
  -F "folder_id=01HQXXX"

# Test search
curl http://localhost/api/documents/documents?search=contract
```

## Production Considerations

1. **Storage**: Use S3 or similar for scalability
2. **CDN**: Enable for public documents
3. **Virus Scanning**: Integrate ClamAV or similar
4. **Thumbnails**: Generate for images/PDFs
5. **Compression**: Compress large files
6. **Cleanup**: Schedule jobs for expired shares
7. **Backups**: Regular storage backups
8. **Monitoring**: Track storage usage

## License

Part of the Enterprise ERP/CRM SaaS Platform
