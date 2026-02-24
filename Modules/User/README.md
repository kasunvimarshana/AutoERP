# User Module
Manages users, roles, and permissions with RBAC support.
## Features
- User CRUD (with tenant isolation)
- Role and permission management
- User invitation flow
## Routes
- GET/POST /api/v1/users
- GET/PUT/DELETE /api/v1/users/{id}
- POST /api/v1/users/{id}/invite
- GET/POST /api/v1/roles
- GET/PUT/DELETE /api/v1/roles/{id}
- POST /api/v1/roles/{id}/assign/{userId}
