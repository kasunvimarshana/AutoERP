# Vehicle Rental Authorization Guard

## Summary

- Added an architecture guard that verifies every Vehicle Rental route action explicitly authorizes inside its controller method.
- Preserved the existing controller-owned dynamic authorization model for transitions that choose permissions from request state.
- Did not add static route middleware to dynamic transition routes, avoiding workflow regressions.

## Root cause

Vehicle Rental has runtime permission decisions for some transition endpoints. A direct route-level permission copy could block valid workflows or oversimplify authorization. At the same time, relying only on manual controller assertions needs a regression guard so future routes cannot be added without explicit authorization.

## Design notes

- The guard reads `app/Modules/VehicleRental/Routes/api.php` as the route source of truth.
- It extracts each routed controller action and checks the action body for `$this->authorization->assert(...)`.
- No production controller, route, service, model, migration, or frontend behavior was changed.
- This keeps module ownership clear: Vehicle Rental keeps its dynamic authorization rules inside its own controllers/services.

## Verification

- Source readback should confirm `VehicleRentalModuleBaselineTest` contains `test_vehicle_rental_route_actions_have_explicit_controller_authorization`.
- Full local `php artisan test`, frontend typecheck, lint, build, and Vitest should be run before merging.
