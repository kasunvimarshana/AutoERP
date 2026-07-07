# Vehicle service job lines no parent reload

Date: 2026-07-06

## Problem

The previous line-editor improvement still left one unwanted API pattern in place:

- adding or removing a job line updated the line list locally;
- but the parent draft job page still reloaded the full job resource afterward.

That meant the UI still made an extra job fetch on every line change, which was not the intended behavior.

## Correction

Removed the parent job reload path from the vehicle service job lines tab.

- line add, edit, and remove now only call the actual line mutation endpoint;
- the visible job line list is updated locally in frontend state;
- the line editor keeps its own local expected job version for continued line mutations without requiring a parent refetch;
- the parent job detail page no longer issues an extra reload request after each line change.

This keeps draft-job line work focused on the line API only and prevents the repeated full job fetch after every add/remove action.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/modules/vehicle-service/components/VehicleServiceLineEditor.tsx resources/js/modules/vehicle-service/pages/VehicleServiceJobDetailPage.tsx`
