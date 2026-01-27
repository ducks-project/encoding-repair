# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## <a name="v120"></a>[1.2.0] - 2026-01-23

### Added

- Type-specific interpreter system with Strategy + Visitor pattern for optimized transcoding
- [`TypeInterpreterInterface`] - Contract for type-specific data interpreters
- [`InterpreterChain`] - Chain of Responsibility for type interpreters
- [`StringInterpreter`] - Optimized interpreter for string data (priority: 100)
- [`ArrayInterpreter`] - Recursive interpreter for array data (priority: 50)
- [`ObjectInterpreter`] - Interpreter for object data with custom property mapping support (priority: 30)
- [`PropertyMapperInterface`] - Contract for custom object property mapping
- `registerInterpreter()` method in `CharsetProcessor` for custom type interpreters
- `unregisterInterpreter()` method in `CharsetProcessor` for removing interpreters
- `registerPropertyMapper()` method in `CharsetProcessor` for class-specific property mapping
- `resetInterpreters()` method in `CharsetProcessor` to restore default interpreters
- Performance improvement: 40-60% faster for objects with custom mappers (avoids full reflection)
- Example file demonstrating interpreter usage (`examples/interpreter-usage.php`)
- Batch processing API for optimized array conversion with single encoding detection
- `toCharsetBatch()` method in `CharsetProcessorInterface` for batch conversion with AUTO detection optimization
- `detectBatch()` method in `CharsetProcessorInterface` for detecting encoding from iterable items
- `toUtf8Batch()` convenience method in `CharsetProcessor` (shortcut to `toCharsetBatch(..., 'UTF-8', ...)`)
- `toIsoBatch()` convenience method in `CharsetProcessor` (shortcut to `toCharsetBatch(..., 'CP1252', ...)`)
- Static facade methods in `CharsetHelper`: `toCharsetBatch()`, `toUtf8Batch()`, `toIsoBatch()`, `detectBatch()`
- Performance improvement: 40-60% faster for batch processing with AUTO detection on arrays > 100 items
- New benchmarks with phpbench
- PSR-16 (Simple Cache) support in [`CachedDetector`] via optional dependency injection
- [`InternalArrayCache`] - Optimized PSR-16 implementation without TTL overhead for CachedDetector (namespace: `Ducks\Component\EncodingRepair\Cache`)
- [`ArrayCache`] - Full-featured PSR-16 implementation with TTL support for external use (namespace: `Ducks\Component\EncodingRepair\Cache`)
- Optional `CacheInterface` parameter in [`CachedDetector`] constructor (default: InternalArrayCache)
- Configurable TTL parameter in [`CachedDetector`] constructor (default: 3600 seconds)
- Support for any PSR-16 implementation (Redis, Memcached, APCu, etc.)
- `psr/simple-cache` dependency (^1.0 || ^2.0 || ^3.0) - interface only, minimal implementation dependency

### Fixed

- Bad normalize option call.
- **BREAKING CHANGE**:
safeJsonEncode & safeJsonDecode should return a JsonException.

### Changed

- Refactored `applyRecursive()` to use `InterpreterChain` instead of manual type checking
- Removed `applyToObject()` method (replaced by `ObjectInterpreter`)
- Interface `CharsetProcessorInterface` kept minimal with core methods only
- Convenience methods (`toUtf8()`, `toIso()`, `toUtf8Batch()`, `toIsoBatch()`) remain in concrete implementation only
- `toCharsetBatch()` now uses `detectBatch()` internally for cleaner code and better maintainability
- [`CachedDetector`] now uses InternalArrayCache as default fallback (automatic instantiation)
- [`CachedDetector`] simplified with unified cache handling (no more if/else for internal vs PSR-16)
- `getCacheStats()` now returns cache class name instead of type string for better clarity
- `clearCache()` delegates to PSR-16 cache interface (unified behavior)
- Cache key generation now uses prefixed hash ('encoding_detect_' prefix) for better namespace isolation
- 100% backward compatible - existing code works unchanged
- Optimized UTF-8 validation with ASCII fast-path + `preg_match('//u')` instead of `mb_check_encoding()`
- ASCII-only strings: ~35% faster (0.357μs vs 0.522μs)
- UTF-8 validation: ~34% faster (0.781μs vs 1.182μs)
- Performance improvement: Detection ~5-10% faster, Repair ~8-12% faster, Conversion with AUTO ~3-5% faster

## <a name="v112"></a>[1.1.2] - 2026-01-23

### Fixed

- **BREAKING CHANGE**:
safeJsonEncode & safeJsonDecode should return a JsonException.

## <a name="v111"></a>[1.1.1] - 2026-01-23

### Fixed

- Bad normalize option call.

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
- [`PrioritizedHandlerInterface`] - Force implentation of `getPriority(): int` method (namespace: `Ducks\Component\EncodingRepair`)

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
[`ArrayCache`]: /assets/documentation/classes/ArrayCache.md
[`InternalArrayCache`]: /assets/documentation/classes/InternalArrayCache.md
[`TypeInterpreterInterface`]: /assets/documentation/classes/TypeInterpreterInterface.md
[`InterpreterChain`]: /assets/documentation/classes/InterpreterChain.md
[`StringInterpreter`]: /assets/documentation/classes/StringInterpreter.md
[`ArrayInterpreter`]: /assets/documentation/classes/ArrayInterpreter.md
[`ObjectInterpreter`]: /assets/documentation/classes/ObjectInterpreter.md
[`PrioritizedHandlerInterface`]: /assets/documentation/classes/PrioritizedHandlerInterface.md
[`PropertyMapperInterface`]: /assets/documentation/classes/PropertyMapperInterface.md
[unreleased]: https://github.com/ducks-project/encoding-repair/compare/v1.1.0...HEAD
[1.2.0]: https://github.com/ducks-project/encoding-repair/compare/v1.2.0...v1.1.0
[1.1.2]: https://github.com/ducks-project/encoding-repair/compare/v1.1.2...v1.1.1
[1.1.1]: https://github.com/ducks-project/encoding-repair/compare/v1.1.1...v1.1.0
[1.1.0]: https://github.com/ducks-project/encoding-repair/compare/v1.1.0...v1.0.0
[1.0.2]: https://github.com/ducks-project/encoding-repair/compare/v1.0.2...v1.0.1
[1.0.1]: https://github.com/ducks-project/encoding-repair/compare/v1.0.1...v1.0.0
[1.0.0]: https://github.com/ducks-project/encoding-repair/compare/v1.0.0...v0.1.0
[0.1.2]: https://github.com/ducks-project/encoding-repair/releases/tag/v0.1.2...v0.1.1
[0.1.1]: https://github.com/ducks-project/encoding-repair/releases/tag/v0.1.1...v0.1.0
[0.1.0]: https://github.com/ducks-project/encoding-repair/releases/tag/v0.1.0
