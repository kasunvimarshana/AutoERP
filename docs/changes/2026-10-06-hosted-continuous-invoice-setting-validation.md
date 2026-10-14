# Hosted validation rejects continuous invoice layout

## Request

Diagnose the hosted Organization Unit settings error when saving the new continuous 8.5 × 5.5 inch invoice layout.

## Finding

- The hosted UI submits `continuous_8_5_by_5_5`, while its API validation response lists only `standard_a4` and `compact_a5` as allowed values.
- The current Invoice configuration definition in this checkout includes `Continuous85By55` in its allowed options.
- The Configuration module validates a setting against the registered server-side definition. Therefore the hosted API process is running an older Invoice definition than the UI, or a long-lived PHP process is still serving the old definition.

## Resolution direction

Deploy the backend release containing the continuous layout definition and print implementation, then reload/restart persistent PHP application workers so they boot the new service-provider registration. Verify the configuration endpoint accepts the new value before retrying the save in the UI.

No code changes were made for this diagnosis.
