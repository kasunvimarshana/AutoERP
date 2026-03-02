# Customization Module

Provides a metadata-driven custom fields engine allowing any entity type to have dynamic, configurable fields without schema migrations.

## Architecture

Controller → Service → Handler (Pipeline) → Repository → Entity

## API Endpoints

- `GET /api/v1/custom-fields?tenant_id=1&entity_type=product` — list custom fields
- `POST /api/v1/custom-fields` — create custom field
- `GET /api/v1/custom-fields/{id}?tenant_id=1` — get custom field
- `PUT /api/v1/custom-fields/{id}?tenant_id=1` — update custom field
- `DELETE /api/v1/custom-fields/{id}?tenant_id=1` — delete custom field
- `GET /api/v1/custom-field-values?tenant_id=1&entity_type=product&entity_id=5` — get values
- `POST /api/v1/custom-field-values` — set values (replace-all)

## Custom Field Types

text, number, boolean, date, select, multiselect, url, textarea
