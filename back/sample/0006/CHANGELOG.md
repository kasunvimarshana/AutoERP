# Changelog

All notable changes to `laravel-ddd-architect` are documented here.
This project adheres to [Semantic Versioning](https://semver.org/).

---

## [2.0.0] — 2026-03-20

### Added
- **Structure presets** (`structure_choices` in config): `ddd-layered` (default), `ddd-modular`, `ddd-hexagonal`
  — switch entire directory layout with a single `DDD_STRUCTURE=ddd-modular` env var
- **`ddd:make-model`** — scaffolds Eloquent Model + Factory + Seeder in one command
  (`--no-factory`, `--no-seeder` flags to skip optional files)
- **`ddd:make-listener`** — scaffolds an Infrastructure Event Listener with `ShouldQueue`,
  `failed()` handler, and optional `--event` flag for precise type-hint injection
- **`ddd:make-policy`** — scaffolds a framework-independent Domain Policy class
- **Three new stubs**: `infrastructure/factory.stub`, `infrastructure/seeder.stub`,
  `infrastructure/listener.stub`, `domain/policy.stub`
- **`DDD_STRUCTURE` environment variable** support — change preset without editing config
- **Structure-preset merging**: active preset keys deep-merge over root config defaults,
  so partial preset overrides work correctly
- **`ddd:info`** updated — new commands appear in the table

### Changed
- `config/ddd-architect.php` — extended with `structure`, `structure_choices` sections;
  existing keys are fully backward-compatible
- `DDDArchitectServiceProvider` — now registers 18 commands (was 15)
- `DDDInfoCommand` — table updated to include all 18 commands

### Fixed
- `ContextGenerator::resolveActiveLayers()` — correctly handles `custom` mode
  when `layers` key is missing from config (falls back to all four layers)
- `BaseCommand::reportCreated()` — normalises path separators on Windows

---

## [1.0.0] — 2026-03-01

### Added
- Initial release
- 15 Artisan commands: `ddd:make-context`, `ddd:make-entity`, `ddd:make-value-object`,
  `ddd:make-aggregate`, `ddd:make-use-case`, `ddd:make-repository`,
  `ddd:make-domain-service`, `ddd:make-domain-event`, `ddd:make-command-handler`,
  `ddd:make-query-handler`, `ddd:make-dto`, `ddd:make-specification`,
  `ddd:list`, `ddd:publish-stubs`, `ddd:info`
- 31 stub templates across all DDD layers
- Shared Kernel scaffolding: `AggregateRoot`, `EntityContract`, `RepositoryContract`,
  `DomainEvent`, `DomainException`, `Uuid`, `Email`, `Money`
- `DDDArchitectServiceProvider` with auto-discovery and container bindings
- `ContextRegistrar` contract + `ContextResolver` implementation
- `StubRenderer` with `{{ token }}` replacement and app-stub override priority
- `FileGenerator` with `.gitkeep` support and `--force` flag
- `DDDArchitect` facade
- Complete test suite: 57 test cases across 5 test files
- Example application: `Order` bounded context end-to-end
- `composer.json`, `phpunit.xml`, `pint.json`, `.github/workflows/ci.yml`, `README.md`
