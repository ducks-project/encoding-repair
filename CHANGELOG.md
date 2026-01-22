# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## <a name="v110"></a>[1.1.0] - 2026-01-22

### Added

- [`CachedDetector`] - Decorator for caching detection results with configurable size limit (namespace: `Ducks\Component\EncodingRepair\Detector`)
- Cache statistics via `getCacheStats()` method returning size and maxSize
- `clearCache()` method for manual cache invalidation
- Automatic cache integration in `CharsetProcessor::resetDetectors()` (wraps MbStringDetector with priority 200)
- Performance improvement: 50-80% faster detection in batch processing scenarios with repeated strings
- [`CharsetProcessorInterface`] - Service contract for charset processing (namespace: `Ducks\Component\EncodingRepair`)
- [`CharsetProcessor`] - Service implementation with fluent API (namespace: `Ducks\Component\EncodingRepair`)
- Transcoder management methods: `registerTranscoder()`, `unregisterTranscoder()`, `queueTranscoders()`, `resetTranscoders()`
- Detector management methods: `registerDetector()`, `unregisterDetector()`, `queueDetectors()`, `resetDetectors()`
- Encoding management methods: `addEncodings()`, `removeEncodings()`, `getEncodings()`, `resetEncodings()`
- Fluent API support - all management methods return `$this` for method chaining
- Support for multiple independent processor instances with different configurations
- Example file demonstrating service usage (`examples/service-usage.php`)
- `SERVICE_ARCHITECTURE.md` - Complete documentation of the new architecture

### Changed

- [`CharsetProcessor`] now uses [`CachedDetector`] by default for improved performance
- Refactored code to use traits for better maintainability and DRY principles
- Extracted common functionality from `CallableDetector` and `CallableTranscoder` into `CallableAdapterTrait`
- Extracted common Chain of Responsibility logic from `DetectorChain` and `TranscoderChain` into `ChainOfResponsibilityTrait`
- Moved traits to dedicated `Traits/` directory
- Reduced code duplication by ~80 lines across 4 classes
- Improved type safety with generic trait annotations (`@template T`)
- Refactored [`CharsetHelper`] to act as a static facade delegating to [`CharsetProcessor`]
- All business logic moved from [`CharsetHelper`] to `CharsetProcessor`] service
- [`CharsetHelper`] now uses lazy initialization for the processor instance
- 100% backward compatible

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

## <a name="v010"></a>[0.1.0] - 2026-01-20

### Added

- [`CharsetHelper`] Advanced charset encoding converter with **Chain of Responsibility** pattern

[`CharsetHelper`]: /assets/documentation/classes/CharsetHelper.md
[`CharsetProcessorInterface`]: /assets/documentation/classes/CharsetProcessorInterface.md
[`CharsetProcessor`]: /assets/documentation/classes/CharsetProcessor.md
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
[`CachedDetector`]: /assets/documentation/classes/CachedDetector.md
[unreleased]: https://github.com/ducks-project/encoding-repair/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/ducks-project/encoding-repair/compare/v1.1.0...v1.0.0
[1.0.0]: https://github.com/ducks-project/encoding-repair/compare/v1.0.0...v0.1.0
[0.1.0]: https://github.com/ducks-project/encoding-repair/releases/tag/v0.1.0
