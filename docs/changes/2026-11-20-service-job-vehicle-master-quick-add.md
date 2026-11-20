# Service Job Vehicle Master Quick Add

**Date:** 2026-11-20  
**Modules:** Vehicle, Vehicle Service

Added `+ Add` actions beside the Make, Model, and Type selectors in the service job's vehicle registration dialog. Each action opens a compact form inside the existing dialog, so the user can create a missing value without losing the in-progress vehicle and customer details. A successful creation selects the new value immediately.

The creation form and API calls live in the Vehicle module. Model creation uses the currently selected Make and cannot save without one. The actions appear only for users with `vehicle.update`, matching the existing Vehicle API authorization. The backend's existing validation and tenant-scoped create endpoints remain the source of truth; validation errors are shown in the quick form.

Verification: TypeScript typecheck, targeted ESLint, Vite production build, and the four `VehicleServiceQuickVehicleModal` tests passed. The tests cover Make and Model creation with automatic selection, preservation of vehicle input, and backend validation feedback.
