# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Object-oriented transcoder architecture with `TranscoderInterface`
- `UConverterTranscoder` class for ext-intl support (priority: 100)
- `IconvTranscoder` class for ext-iconv support (priority: 50)
- `MbStringTranscoder` class for ext-mbstring support (priority: 10)
- `CallableTranscoder` adapter for legacy callable support
- `TranscoderChain` class using `SplPriorityQueue` for priority management
- Priority-based transcoder registration with
`registerTranscoder($transcoder, ?int $priority)`
- Callable signature validation in `CallableTranscoder`
- Return type validation for callable transcoders
- Comprehensive documentation for all transcoder classes
- Unit tests for `CallableTranscoder` (6 tests)

### Changed

- `registerTranscoder()` now accepts `TranscoderInterface`
or callable with optional priority (int)
- Transcoder chain now uses `SplPriorityQueue` instead of manual sorting
- Improved extensibility with SOLID principles (Single Responsibility, DRY)
- Updated README with `TranscoderInterface` examples
- Updated documentation with priority-based transcoder system

### Deprecated

- Legacy boolean `$prepend` parameter in `registerTranscoder()`
(use `?int $priority` instead)

## <a name="v100"></a>[1.0.0] - 2026-01-21

## <a name="v010"></a>[0.1.0] - 2026-01-20

### Added

- [`CharsetHelper`]

[`CharsetHelper`]: /assets/documentation/classes/CharsetHelper.md
[unreleased]: https://github.com/ducks-project/encoding-repair/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/ducks-project/encoding-repair/compare/v1.0.0...v0.1.0
[0.1.0]: https://github.com/ducks-project/encoding-repair/releases/tag/v0.1.0
