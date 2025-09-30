# Changelog

All notable changes to this project will be documented in this file.

The format loosely follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and Semantic Versioning.

## [2.0.0] - 2025-09-30 (Minimal Core Refactor)
### Added
- `final` keyword to concrete handlers (Archiver, Drafters, Readers) and structure classes (Field, Index, ForeignKey, Relation, Table, Schema, Procedure, Trigger, View) to reduce accidental extension surface.
- Minimal configuration excerpt in README.

### Changed
- Composer dev dependencies pruned: removed `codeigniter4/devkit`, `nexusphp/tachycardia`; added explicit `phpunit/phpunit`.
- Removed all logging checks from reader handlers; simplified exception handling (silent fail returns empty schema segments).
- README rewritten to reflect lean feature set and removed subsystems.

### Removed (Breaking)
- Async subsystem (jobs, manager, handlers).
- Plugin system, environment layering, runtime overrides, snapshots.
- Events subsystem & placeholder tests.
- Logging & metrics (SchemaLogger) and analyzer/performance components.
- Validation engine & advanced relation detectors.
- Export/import formats beyond cache; intelligent cache manager.
- Legacy tests referencing removed subsystems.

### Deprecated
- None. (Surface aggressively trimmed.)

### Security
- No security-affecting changes.

## [1.x] - Legacy Line
Feature-rich line including async, events, logging, validation, analyzers, relation detectors, multi-environment configuration. No longer maintained; pin to the latest 1.x tag if you depend on those subsystems.

---

## Migration Guide 1.x -> 2.0.0
1. Remove usage of async, events, logging, validation, export/import (non-cache) APIs.
2. Flatten multi-environment configuration to a single `Schemas` config instance.
3. If you extended classes now `final`, wrap them with composition rather than inheritance.
4. Re-implement any removed serialization or event features externally as add‑ons.

---

[2.0.0]: https://github.com/daycry/schemas/tree/v2.0.0
