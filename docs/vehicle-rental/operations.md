# Fresh Vehicle Rental operational contract

Implementation update: 2026-09-08, based on `worktree-0.0.8` at `7a9edc1acc1048ac73418ec1780c297241e7d976`, with the replacement/open-ended extension. This contract describes the newly written operational slice. It is not evidence that every video, pricing policy or production environment has been verified. Business authority and unresolved rules remain in [the knowledge base](../knowledgebase.md).

## Evidence and decision boundaries

Video anchors V01/V03/V04/V07 show customer and owner agreement context, physical vehicle selection and Running Chart observations. V02/V05 show two separate commercial consumers of that evidence. This slice captures physical observations and their provenance; it does not calculate either commercial side or create financial documents.

Draft/finalized/reversed chart states, optimistic versions, immutable audit snapshots and half-open physical intervals are integrity-derived engineering choices. They are not assertions about legacy status codes, mandatory approval stages, billable-day endpoints or tax rules. VR-U17 and VR-U28 remain open for additional business requirements. The only derived chart quantity is exact end odometer minus start odometer when both readings are known.

## Ownership and relationships

| Relationship | Purpose and integrity decision |
|---|---|
| Vehicle use → customer agreement and original revision | One customer commercial context for a physical-use period. A composite revision FK preserves the exact accepted terms. Agreement closure cannot strand planned or in-custody use. |
| Vehicle use → optional owner agreement and original revision | External supply is a separate commercial source for the same physical vehicle. Customer and owner amounts are never inferred from one another. |
| Vehicle use → canonical Vehicle | One physical identity across organizations. No duplicate rental vehicle or branch-specific copy. |
| Company supply → Vehicle-owned coverage service | Absence of an owner agreement is not proof of company ownership. Vehicle ownership must cover the selected period and is checked again at handover. Rental does not duplicate ownership rows. |
| Running Chart → vehicle use and original revision | Usage inherits vehicle/customer/owner context through one owning relationship. No redundant vehicle/customer/owner FKs on the chart. Finalization captures the then-current use revision. |
| Correction chart → reversed predecessor | A single directed relationship preserves provenance. Unique predecessor reference prevents competing correction children; further corrections form a chain. No redundant reverse pointer. |
| Aggregate → immutable history → actor | Every accepted command stores a full original snapshot, monotonically increasing version and tenant-safe actor reference. History cannot be edited or deleted through the models. |

The generic immutable-history base was renamed from `AgreementHistory` to `ImmutableRentalHistory` because the same invariant now applies to agreements, vehicle use and charts. No historical table or record was renamed. No removed Rental implementation was copied or restored.

## Vehicle-use workflow

1. Activate the customer agreement; for externally supplied vehicles activate the correct owner agreement.
2. In customer agreement review choose **Assigned vehicles → Assign vehicle**. Select a canonical vehicle and owner agreement through guided lookups, or explicitly select company supply. Supply planned start/end instants with offsets. Planned end may be omitted only when the customer agreement and selected supply are both open-ended.
3. Save the plan with the displayed customer-agreement version. The backend locks the physical vehicle, customer agreement and owner agreement, checks full agreement/source coverage and tenant-wide availability, then writes the plan and its history atomically.
4. Record actual handover with a reason, explicit timestamp and optional odometer. It must fall inside the planned period and cannot be in the future. Recheck active agreements, source and availability. Vehicle's status service records `Rented`; Rental owns custody evidence, not the Vehicle status implementation.
5. Record actual return after handover and no later than now. Known odometers cannot move backwards across the physical vehicle’s recorded custody and finalized-chart observations, including other uses or organizations in the tenant. Backfilled evidence is checked against later readings too; readings recorded at the same instant must agree. Return cannot precede finalized usage or contradict a known finalized finish reading. Release `Rented` to `Active` through Vehicle; preserve any different status such as an independently imposed hold.
6. A planned use can be cancelled with a reason. Cancelled/returned records retain their original plan and history. Invalid transitions and stale versions produce explicit errors.

Physical occupancy uses `[start, end)` instants. Exact adjacent handover boundaries do not overlap. Actual custody remains open-ended until actual return, regardless of expected return. Returned actual periods remain protected historical occupancy; cancelled plans do not block. Cross-organization conflicts disclose a generic reason rather than another branch's business details.

The plan is immutable: cancel and create a corrected plan. For actual exchange, choose **Replace vehicle**, select a different vehicle and its valid source, record the actual exchange time, optional old/new odometers and a reason. The backend locks both physical vehicles in ID order, returns the old use, plans and hands over the new use in one transaction. Failure at any stage rolls back both histories and both Vehicle status changes. The new use has a unique, tenant-safe `replaces_use_id` pointing to the returned predecessor; no reverse pointer or mutated vehicle identity is stored. Both use periods meet at the actual exchange instant and share the customer agreement. This establishes operational replacement only, not replacement charging (VR-U04).

Open-ended planned use blocks future conflicting occupancy. An open-ended request against finite agreement or supply coverage fails rather than being silently truncated.

## Running Chart workflow

- Open **Running Charts** on the selected vehicle use. Charts require actual in-custody or returned use.
- Record a completed usage interval inside actual custody. Original offset strings and second precision are retained alongside UTC storage.
- Record optional start/end odometers, garage/commercial distances, AC mode, normal/double/triple OT in explicit whole minutes, night-out count, driver observation and notes. Blank means unknown, zero means explicitly recorded zero. Dotted legacy duration text is not automatically parsed as hours.
- Known end odometer must not precede start. Each known distance component cannot exceed known total physical distance; there is no assumption that garage and commercial categories are disjoint or that either is billable. OT qualification, category interaction and night-out entitlement remain unproven; capture does not imply approved pay.
- Draft facts can be edited with the current chart version. Finalize rechecks custody, rejects overlap with any finalized chart on the same physical vehicle across the tenant and checks known odometers against the complete Rental custody/finalized-chart timeline, including across uses with missing measurements. There is no financial posting side effect.
- Finalized facts cannot be edited. Authorized reversal requires a reason. Create a new correction from the reversed chart; the predecessor remains intact. Reversal currently has no financial consumer to unwind because none is integrated. Any future consumer must add its reversal dependency before financial activation.
- Driver observation is source text, not an HR employee, assignment, payroll entitlement or verified external-driver identity. Driver resource availability and VR-U27 remain outstanding.
- Vehicle's current odometer master is not updated: a historical chart is not automatically the latest authoritative Vehicle observation. A future integration must establish Vehicle-owned chronological update semantics.

## API and authorization

All routes use the existing authenticated tenant/organization/feature middleware under `/api/v1/vehicle-rental`. Services independently authorize reads and writes. Trusted request context determines actor, tenant and organization; clients cannot select a different tenant through relationship fields.

| Method and relative path | Concurrency/permission |
|---|---|
| GET `vehicle-uses` | Use view; paginated register with search, state and planned-period overlap filters |
| GET/POST `customer/agreements/{agreement}/vehicles` | Use view/manage; POST requires customer agreement `expected_version` |
| GET `vehicles/{vehicle}/sources` | Use view; scoped active owner reference/name/date lookup, no owner rates |
| POST `vehicle-uses/{use}/{handover\|return\|cancel}` | Use manage; current use `expected_version`, reason and applicable actual observations |
| POST `vehicle-uses/{use}/replace` | Use manage; old use version, actual exchange time, new vehicle/source, reason and optional separate odometers |
| GET `vehicle-uses/{use}/history` | Use view; paginated readable history |
| GET/POST `vehicle-uses/{use}/running-charts` | Chart view/manage; POST requires current use version; optional guided correction predecessor |
| PUT `running-charts/{chart}` | Chart manage; current chart version; draft only |
| POST `running-charts/{chart}/{finalize\|reverse}` | Separate finalize/reverse permission; current chart version |
| GET `running-charts/{chart}/history` | Chart view; paginated facts and actor names |

The permission identifiers are centrally defined by `RentalAuthorization`; frontend references use descriptive permission constants. Schema options use enums; decimal precision/scale and timestamp formats use named constants. Financial values are never hardcoded into these workflows.

## Cross-module concurrency corrections

Vehicle owns the availability contract. Rental and Vehicle Service publish their own blockers; neither reads the other's tables directly. Workshop admission to Inspected/InProgress and admitted job period updates now recheck the shared contract. Changing the physical vehicle on an admitted job is rejected; correct its lifecycle first.

Writes acquire the physical vehicle mutex before dependent source/use/history rows. Vehicle ownership changes use the same vehicle-first order, fixing the previous ownership-then-vehicle inversion. Integrity queries use current locking reads after that mutex rather than potentially stale consistent reads. Owner agreement draft vehicle changes prelock old/new vehicle IDs in deterministic order. Agreement closure checks active use under locks. These are owner-specific fixes with no added circular module dependency.

SQLite regression coverage verifies command behavior and rollback, not InnoDB contention. MySQL/MariaDB concurrency execution, production migration rehearsal and authenticated browser/UAT are still required. These fresh migrations must not be blindly applied to an uninspected production schema containing historical Rental tables.

## Fresh-baseline migration boundary

The repository's mandatory migration architecture permits explicit fresh table creation only. Replacement provenance and nullable planned ends are therefore integrated into the original fresh `vehicle_rental_uses` creation migration. This is not an automatic upgrade for an already migrated database. No production schema was inspected or changed. If the earlier operational commit was applied, compare the actual schema and migration journal and prepare a deployment-specific, evidence-preserving upgrade before using this revision. Do not drop operational tables or erase history to satisfy the fresh baseline.

## Running Chart register — 2026-09-09

**Vehicle Rental → Running Chart Register** opens `/vehicle-rental/running-charts`. The UI and `GET /api/v1/vehicle-rental/running-charts` require `vehicle-rental.running-charts.view` with the existing tenant, organization and feature gates. All relationship context comes from the chart's existing use/agreement links; no duplicate identities or schema relationships are added.

Optional filters are `search`, `chart_status`, `from` and `until`; timestamps require explicit offsets and the UI preserves seconds. Period filtering selects overlapping charts without changing recorded quantities. Results are paginated and ordered by usage start then chart ID descending. Search predicates remain grouped inside tenant/organization scope. States are enum-validated; invalid dates, reversed/empty intervals and unauthorized access fail explicitly. Failed filter requests hide previous results rather than presenting them as a matching report.

Review expands recorded distances, OT minute counts, night-outs, AC mode, driver observation and notes. History loads only when requested. Context includes customer/owner names and references, company supply, correction predecessor and replacement vehicle. Rates are not returned through the contextual agreement objects. This is Rental-owned evidence browsing; cross-module financial reporting, aggregation, export and financial eligibility remain with their future owning workflows.

The shared Rental `OdometerContinuity` service checks known timestamped observations under the existing Vehicle lock using current locking reads. It neither fills missing values nor alters history. No new relationship, replicated Vehicle identity, financial rule or Vehicle-master write is introduced. MariaDB 10.11.14 initialization succeeded locally, but its socket was refused by the environment; real-engine execution and contention verification remain open.

## Vehicle Use register — 2026-09-10

**Vehicle Rental → Vehicle Use Register** opens `/vehicle-rental/vehicle-uses`. `GET /api/v1/vehicle-rental/vehicle-uses` and its UI require the existing use-view permission and tenant/organization/feature context. Agreement management permission is not implied. The read model uses the existing VehicleUse resource; it exposes agreement/party references and snapshots, not agreement rates.

Optional `search` matches vehicle labels or customer/owner agreement references and party-name snapshots. `use_status` uses the VehicleUse status enum. Optional `from`/`until` require explicit numeric-offset timestamps and filter the **original planned interval**, not actual custody. An assignment matches when its planned start is before `until` and its planned end is after `from`; a null planned end is unbounded. Exact touching endpoints do not overlap. Both bounds together must form a nonempty increasing interval. Search OR predicates and open-end OR predicates remain inside trusted tenant/organization scope. Results are ordered by planned start then ID descending and paginated.

The UI displays the complete original plan, actual handover/return, human-readable customer/owner or company supply and the predecessor vehicle for a replacement. Expand a row to review known/unknown odometers, notes and immutable history. Missing readings remain unknown; explicit zero stays zero. Failed searches hide stale rows. A planned-period search may exclude an overdue vehicle still in custody, so this register is not an availability or utilization calculation. Use status and actual custody remain visibly distinct from the planned period. No charges, physical distance totals, utilization percentages or financial eligibility are inferred.

Relationship review: no new tables, foreign keys, inverse links or duplicated identities. Rental owns this read model; the existing use-to-agreement and directed replacement links already carry its context. Vehicle and financial owners retain their current responsibilities.
