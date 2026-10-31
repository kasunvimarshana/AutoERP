# TACGL/video reupload identity verification — 2026-10-31

## Scope and authority

The requested source of truth remains the TACGL Visual FoxPro corpus and four uploaded recordings, with `worktree-0.0.8` as the authoritative engineering branch. This documentation-only verification was performed against the user-supplied 2026-10-31 uploads. It did **not** reimplement Vehicle Rental, decrypt the protected nested RAR, replay the full videos, or alter any financial/business rule.

## Executed verification

- SHA-256 of `TACGL.zip`: `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`.
- SHA-256 of `TACGL(20261031-171427).zip`: `79c240494943437978754169c3360bb7c6e35d911ef8263c4b2d6b6246384d77`.
- The canonical ZIP contains 452 non-directory files plus the outer `TACGL/` wrapper. The dated ZIP contains the same 452 files with no wrapper. The normalized names, CRCs, uncompressed lengths, and individually recomputed SHA-256 of **all** corresponding decompressed files matched.
- `TACGL.rar` SHA-256: `0001a91ab4dfdf31d60f669b30f1ccc2da475d7841b2ceea505ef97eff5bedbf`; unchanged from the knowledge-base evidence register. The nested RAR's protected contents remain unavailable.
- `1.mp4` SHA-256: `ac4ca8e632081c32cd2a1d2e6facb070acf4a1f5304a4dc7a468ca7073b953cf` (FFprobe 2450.132 s).
- `Recording 2026-06-21 132314.mp4` SHA-256: `11866d255dbb709055b43bb7428538a3e2f0858a8ee1d0144187bcdaf4616ffa` (2517.967 s).
- `2.mp4` SHA-256: `cd2ba1399f149003f19080327458e4bbe4619b88eed9416053c7f8d21431c36f` (1273.667 s).
- `ScreenVideo_03-04-2026_18-02-52.mp4` SHA-256: `c9853b7923e7cb95f1014cf598416faa550bfbd56f19da56b613f160d0528ce9` (744.477 s).

## Change

Added a dated verification subsection to `docs/knowledgebase.md` beside the pre-existing TACGL/video source register. Existing business rules, historical evidence, acceptance criteria and earlier append-only change records were not edited. No runtime, test, schema, financial or workflow code changed.

## Decision and limits

The 2026-10-31 upload is **duplicate evidence**, not proof of a newly resolved commercial rule. Unknown liabilities, adjustment document lifecycles, financial reconciliation, incident monetary rules, approvals and deployment gates remain unresolved.

Current repository verification found only PR #127 open (draft, unapproved, failed check runs), with open blockers #124 (required checks/account policy) and #128 (historical Purchase currency/Invoice/Finance/GL reconciliation). The separately tested `local-integration/release-20261031` candidate does not constitute an approval or a merged release. No candidate/target rewrite, PR merge, issue closure, GitHub Actions invocation, paid CI, or production deployment was performed.

Published to documentation branch `audit/tacgl-upload-identity-20261031` instead of `worktree-0.0.8`: the existing workflow triggers on pushes to `worktree-0.0.8` and on PRs targeting it, while the user prohibits GitHub Actions. This branch is intentionally outside the configured push triggers and has no PR.

## Verification level

The uploaded archive hashes, all 452 normalized decompressed file hashes, MP4 hashes and media durations were checked locally. Documentation insertion was checked for a unique matching heading. PHP, MySQL, frontend, migration, security and full media-frame test suites were **not run for this documentation-only change**. Existing test evidence is SHA-specific to the previously tested integration candidate and does not transfer to this documentation branch.
