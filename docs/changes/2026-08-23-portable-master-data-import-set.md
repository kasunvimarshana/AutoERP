# Portable master-data import set

Date: 2026-08-23

## Purpose

Provide separately importable SQL files for the requested item catalogue and prices, customers and vehicles with their current owners, and suppliers.

## Outputs

- `database/imports/2026-08-23-items-with-prices.sql`
- `database/imports/2026-08-23-customers-vehicles-current-owners.sql`
- `database/imports/2026-08-23-suppliers.sql`

## Content

- The item import contains 699 items, 2,097 item-unit roles, 1,265 current purchase/sales prices, and 203 valid bundle lines.
- The customer and vehicle import contains 1,403 customers, 569 customer addresses, 1,976 vehicles, and 1,827 current customer ownerships.
- The supplier import contains all three supplier master records present in `taprjfji_autoerp (3).sql` and resolves organization, currency, and approver relationships without copying database IDs.

## Safety and verification

- Each import resolves target relationships through stable business keys instead of source database IDs.
- Imports use transactions, prerequisite and collision guards, and deterministic rerun behavior.
- Loaded all three files into a fresh isolated copy of the current AutoERP schema and then ran all three files a second time.
- Both runs produced the same master-data counts, including three suppliers and a supplier row-version total of three.
- Confirmed zero vehicles with multiple current owners, zero orphaned current customer ownerships, and zero duplicate current item-price scopes.
- The verification database was isolated from the project database; local `laravel` data was not changed.
