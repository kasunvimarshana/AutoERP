# System Prompt Foundation Correction

Date: 2026-06-30

## Scope

Corrected the project system prompt text in `system_prompt.txt` and the matching `docs/system_prompt.txt` copy.

## Root causes

- The prompt required the agent to "ALWAYS ask before acting", which conflicted with autonomous execution and would slow clear, locally verifiable tasks.
- The prompt treated all raw literals as magic values, including harmless local mechanics and framework syntax.
- The mandatory response format required code blocks even when no code was needed.
- Some rules were expressed as absolute slogans without impact boundaries, making them harder to apply consistently.

## Changes

- Reframed the identity and workflow around evidence-first engineering: understand first, verify second, change third.
- Replaced unconditional asking with focused clarification only when requirements, ownership, or impact cannot be discovered from local context.
- Clarified magic-value handling by separating business values from self-evident local mechanics.
- Preserved security, data integrity, module ownership, no legacy patches, concurrency, auditability, UI clarity, and Laravel migration guidance.
- Replaced rigid output requirements with concise, task-appropriate response guidance.
- Kept both prompt copies synchronized to avoid policy drift.

## Verification

- Read the latest relevant `/docs/changes` records before editing.
- Confirmed `system_prompt.txt` and `docs/system_prompt.txt` are identical after the correction.
- Kept the change scoped to prompt policy text and this append-only change record.
