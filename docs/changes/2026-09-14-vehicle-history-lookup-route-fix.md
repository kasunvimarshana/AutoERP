# Vehicle History all-vehicle lookup route fix

Date: 2026-09-14

## What changed

- Corrected the Vehicle-owned frontend lookup loader so the existing all-vehicle mode calls the unqualified /vehicles/lookup endpoint.
- Kept active, by-customer, and service-available lookups on their existing qualified endpoints.

## Why

Vehicle Service History intentionally uses all vehicles so historical jobs remain accessible for inactive and non-service-available vehicles. The frontend previously generated /vehicles/lookup/all, while the route exposes all-vehicle lookup through the optional-kind /vehicles/lookup endpoint. The invalid URL prevented vehicle selection and therefore prevented the report from loading.
