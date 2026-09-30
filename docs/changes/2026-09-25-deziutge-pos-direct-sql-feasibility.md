# Deziutge POS direct SQL feasibility

Date: 2026-09-25

## Request

The user clarified that only old `service` records are in scope and asked whether they could be delivered in a SQL file for direct import into the new system database.

## Read-only target check

- The configured Laravel database is `laravel`, with tenant 1 holding 1,891 vehicles and the immutable vehicle service history tables present.
- The dump contains 1,155 service sales: 1,100 non-deleted and 55 soft-deleted.
- Of the 1,100 non-deleted service sales, 1,099 reference a source vehicle and 1 has no vehicle reference.
- Comparing normalized source registrations to tenant 1's vehicles found 996 uniquely matched service-sale rows, 103 rows without a target vehicle match, and no ambiguous target matches.
- No import SQL was generated or applied. Existing imported-history records were not changed.

## Conclusion

A database-specific SQL artifact is feasible for uniquely matched rows, but it will not avoid the source-to-target mapping work. A complete visible history requires resolving the 103 missing target vehicles and the one source sale without a vehicle, or explicitly accepting a partial import. Do not insert history without a valid target vehicle relationship.
