# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## <a name="v102"></a>[1.0.2] - 2026-01-23

### Fixed

- **BREAKING CHANGE**:
safeJsonEncode & safeJsonDecode should return a JsonException.

## <a name="v101"></a>[1.0.1] - 2026-01-23

### Fixed

- Bad normalize option call.

## <a name="v100"></a>[1.0.0] - 2026-01-21

### Added

- Object-oriented transcoder architecture with [`TranscoderInterface`]
- [`UConverterTranscoder`] class for ext-intl support (priority: 100)
- [`IconvTranscoder`] class for ext-iconv support (priority: 50)
- [`MbStringTranscoder`] class for ext-mbstring support (priority: 10)
- [`CallableTranscoder`] adapter for legacy callable support
- [`TranscoderChain`] class using `SplPriorityQueue` for priority management
- Priority-based transcoder registration with `registerTranscoder($transcoder, ?int $priority)`
- Object-oriented detector architecture with [`DetectorInterface`]
- [`MbStringDetector`] class for ext-mbstring support (priority: 100)
- [`FileInfoDetector`] class for ext-fileinfo support (priority: 50)
- [`CallableDetector`] adapter for legacy callable support
- [`DetectorChain`] class using `SplPriorityQueue` for priority management
- Callable signature validation in [`CallableTranscoder`] and [`CallableDetector`]
- Return type validation for callable transcoders and detectors
- Comprehensive documentation for all transcoder and detector classes
- Unit tests for [`CallableTranscoder`] (6 tests) and [`CallableDetector`] (5 tests)

### Changed

- `registerTranscoder()` now accepts [`TranscoderInterface`] or callable with optional priority (int)
- `registerDetector()` now accepts [`DetectorInterface`] or callable with optional priority (int)
- Transcoder chain now uses `SplPriorityQueue` instead of manual sorting
- Detector chain now uses `SplPriorityQueue` instead of static array
- Improved extensibility with SOLID principles (Single Responsibility, DRY)
- Updated README with [`TranscoderInterface`] and [`DetectorInterface`] examples
- Updated documentation with priority-based transcoder and detector systems

### Deprecated

- Legacy boolean `$prepend` parameter in `registerTranscoder()` (use `?int $priority` instead)
- Legacy boolean `$prepend` parameter in `registerDetector()` (use `?int $priority` instead)

## <a name="v012"></a>[0.1.2] - 2026-01-23

### Fixed

- **BREAKING CHANGE**:
safeJsonEncode & safeJsonDecode should return a JsonException.

## <a name="v011"></a>[0.1.1] - 2026-01-23

### Fixed

- Bad normalize option call.

## <a name="v010"></a>[0.1.0] - 2026-01-20

### Added

- [`CharsetHelper`] Advanced charset encoding converter with **Chain of Responsibility** pattern

[`CharsetHelper`]: /assets/documentation/classes/CharsetHelper.md
[`TranscoderInterface`]: /assets/documentation/classes/TranscoderInterface.md
[`UConverterTranscoder`]: /assets/documentation/classes/UConverterTranscoder.md
[`IconvTranscoder`]: /assets/documentation/classes/IconvTranscoder.md
[`MbStringTranscoder`]: /assets/documentation/classes/MbStringTranscoder.md
[`CallableTranscoder`]: /assets/documentation/classes/CallableTranscoder.md
[`TranscoderChain`]: /assets/documentation/classes/TranscoderChain.md
[`DetectorInterface`]: /assets/documentation/classes/DetectorInterface.md
[`MbStringDetector`]: /assets/documentation/classes/MbStringDetector.md
[`FileInfoDetector`]: /assets/documentation/classes/FileInfoDetector.md
[`CallableDetector`]: /assets/documentation/classes/CallableDetector.md
[`DetectorChain`]: /assets/documentation/classes/DetectorChain.md
[unreleased]: https://github.com/ducks-project/encoding-repair/compare/v1.0.2...HEAD
[1.0.2]: https://github.com/ducks-project/encoding-repair/compare/v1.0.2...v1.0.1
[1.0.1]: https://github.com/ducks-project/encoding-repair/compare/v1.0.1...v1.0.0
[1.0.0]: https://github.com/ducks-project/encoding-repair/compare/v1.0.0...v0.1.0
[0.1.2]: https://github.com/ducks-project/encoding-repair/compare/v0.1.2...v0.1.0
[0.1.1]: https://github.com/ducks-project/encoding-repair/compare/v0.1.1...v0.1.0
[0.1.0]: https://github.com/ducks-project/encoding-repair/releases/tag/v0.1.0
