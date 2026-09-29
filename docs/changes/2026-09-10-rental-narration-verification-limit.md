# Rental narration verification limit — 2026-09-10

Baseline: `83ecbed1b1d074581b470fe297633b3b96460130`, latest verified `worktree-0.0.8`.

The local workspace reverted to an older snapshot during an earlier transcription attempt. Preserve its incomplete test file outside the checkout and recover the latest already-pushed fresh work. No removed legacy Rental code was restored. The interrupted transcription output was unavailable and was not treated as reviewed evidence.

Rebuild free whisper.cpp v1.9.2 locally and download the multilingual large-v3-turbo model from the upstream model location. Verify its published SHA-1. Complete a 60-second sample of `1.mp4` at 03:00–04:00 with explicit Sinhala and automatic-language decoding. Both outputs contained unusable repetition. Check the extracted audio level and run the bundled English reference recording; the reference transcription succeeds. These checks distinguish a working basic runtime from unreliable transcription of the business sample. They do not prove that every other transcription approach would fail.

Update the knowledge base and TODO with exact provenance and limitations. Accept no machine-generated sentence or new commercial policy. No complete audiovisual audit or complete module implementation is claimed.

No runtime, frontend, schema, relationship or module ownership changes. No new application tests were necessary for this documentation-only change; whitespace verification passed. The prior unchanged backend baseline has 709 tests / 8,034 assertions passing. Free local tools only; no paid transcription service, GitHub Actions or production deployment. Audio was processed locally.

Remaining work includes reliable full narration review, effective commercial versions, canonical driver identity, independent calculations/consumption, financial-owner handoffs, unresolved pricing/deposit/tax policies and database/browser/production acceptance. Do not present all of those as completed or silently replace missing rules with defaults.
