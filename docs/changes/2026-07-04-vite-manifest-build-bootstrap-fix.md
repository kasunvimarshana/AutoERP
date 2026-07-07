# Vite manifest build bootstrap fix

Date: 2026-07-04

## Problem

Laravel browser requests failed with `Illuminate\Foundation\ViteManifestNotFoundException` because `public/build/manifest.json` did not exist.

The frontend dependencies were already installed, but the Vite production build had not been generated for the current workspace bootstrap.

## Correction

Ran the frontend production build so Vite emitted the compiled assets and manifest into `public/build`, matching Laravel's configured asset pipeline.

## Verification

- `npm run build`
- Confirmed `public/build/manifest.json` now exists
