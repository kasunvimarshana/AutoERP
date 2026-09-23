# Vehicle Service Workforce Assignment Panel — Finalized Plan

Date: 2026-09-13

## Goal

Make workforce assignment fast and clear by replacing compact employee dropdowns and line-by-line save actions with a visual employee picker and one atomic header action.

## Finalized user flow

1. Workforce lines that use the Job Card supervisor are shown first.
2. A supervisor line displays the Job Card supervisor as a locked, automatically selected employee. It does not open the employee picker.
3. Clicking an unassigned non-supervisor employee field opens a right-side employee panel.
4. The panel loads available non-supervisor employees, supports search and pagination, and presents each employee as a large, readable row.
5. Clicking an employee selects that employee for the line, closes the panel, and keeps the choice pending.
6. Pending choices can be changed before saving.
7. The Workforce header contains one **Assign selected employees** button. Individual lines do not contain Add buttons.
8. The header button submits every pending assignment in one request. The backend validates the complete selection and writes all assignments in one database transaction.
9. If any selection is invalid or the Job Card version is stale, no partial assignments are saved. The latest Job Card state is reloaded for a stale-version conflict.
10. Advanced assignment details remain available through Edit after an assignment is created.

## Business rules

- A supervisor-designated labour line must use the Job Card supervisor. An alternate supervisor cannot be assigned through either UI or API.
- A Job Card without a supervisor cannot submit a supervisor-designated line; the UI explains that a Job Card supervisor must be selected first.
- Non-supervisor lines accept only active, available, non-supervisor employees from the current tenant and organization scope.
- Existing assignment uniqueness rules remain enforced per line and employee.
- Batch writes lock the Job Card, check `expected_version`, validate every row before writing, recalculate affected lines and the Job Card, and increment the Job Card version once.

## API contract

`POST /api/v1/vehicle-service/jobs/{job}/employees/batch`

```json
{
  "tenant_id": 1,
  "organization_unit_id": 1,
  "expected_version": 9,
  "assignments": [
    { "line_id": 11, "employee_id": 31 },
    { "line_id": 12, "employee_id": 22 }
  ]
}
```

The response contains the created assignment resources. The client then refreshes the authoritative workforce and Job Card version.

## UX and accessibility

- The panel uses the shared accessible Drawer foundation, including focus containment, Escape/backdrop close, and mobile full-width behavior.
- Search results are buttons, so keyboard users can select employees without pointer-specific behavior.
- Employee code and name are always human-readable; raw relationship IDs are never exposed.
- The primary save button includes the pending count and is disabled when there is nothing to assign.
- Existing assignments remain visible with Edit and Remove actions.

## Acceptance criteria

- No line-by-line Add button remains.
- One header button saves all selected employees.
- Supervisor assignment fields appear before regular employee fields and are locked to the Job Card supervisor.
- Non-supervisor fields open a right-side searchable employee panel.
- A failed batch cannot leave partial assignments.
- Stale Job Card data is detected using `expected_version`.
- Backend and frontend focused tests, TypeScript validation, and production build pass.
