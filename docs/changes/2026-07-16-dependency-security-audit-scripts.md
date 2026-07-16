# Dependency security audit scripts

Date: 2026-07-16

## Problem

The repository did not expose a standard local command for checking known vulnerable PHP or JavaScript dependencies. Developers had to remember tool-specific commands, so dependency audits were easy to omit from release verification.

## Correction

Added free native package-manager scripts:

```bash
composer security:audit
npm run security:audit
```

The Composer command audits the locked dependency set without interaction. The npm command fails for high or critical advisories according to npm's audit policy.

No paid service, GitHub Actions workflow, dependency, or application runtime behavior was added.

## Scope

This closes only the dependency-advisory command gap. Secret scanning, PHP static analysis, browser security smoke, and production infrastructure verification remain separate gates.

## Relationships

No production code, schema, or relationship changed.
