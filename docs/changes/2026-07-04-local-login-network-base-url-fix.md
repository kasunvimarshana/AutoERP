# Local login network base URL fix

Date: 2026-07-04

## Problem

Local tenant login failed in the browser with `ERR_NETWORK` even though the local application was running. The frontend API client was still configured to target the production domain instead of the local Laravel origin.

## Root cause

The local `.env` file set:

- `APP_URL=https://autoerp.tapromall.com`
- `PLATFORM_PUBLIC_URL=https://autoerp.tapromall.com`
- `VITE_API_BASE_URL="${APP_URL}"`

Because the React client uses `VITE_API_BASE_URL` for Axios, browser login requests were sent to the remote host with credentials enabled, which breaks the intended local authentication flow and surfaces as a browser-level network failure.

## Correction

Updated the local environment configuration to use the local application origin and same-origin API requests:

- `APP_URL=http://127.0.0.1:8000`
- `PLATFORM_PUBLIC_URL="${APP_URL}"`
- `VITE_API_BASE_URL=/`

This keeps local browser API calls on the same origin as the Laravel app while preserving a clean separation between local and hosted environments.

## Verification

- Confirmed the frontend API client reads `import.meta.env.VITE_API_BASE_URL`
- Confirmed the local `.env` no longer points login traffic at the hosted domain
