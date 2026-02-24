# Setting Module
Provides per-tenant configurable settings stored in the database with caching.
## Features
- Get/set settings by key or group
- Cache invalidation on update
- Supports string, integer, boolean, json, enum types
## Routes
- GET /api/v1/settings/group/{group}
- GET /api/v1/settings/{key}
- PUT /api/v1/settings/{key}
