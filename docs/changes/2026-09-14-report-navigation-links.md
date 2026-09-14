# Reporting navigation links

Date: 2026-09-14

## What changed

- Added direct Employee Commission and Vehicle Service History links to the existing Reports sidebar module.
- Reused the Reporting module feature and reporting.reports.view permission boundary already enforced by the report pages and APIs.

## Why

The report pages, frontend routes, backend routes, and report catalog entries existed, but the Reports submenu exposed only Summary Reports, All Reports, and GRN Payables. The two new reports were therefore not discoverable directly from the sidebar.
