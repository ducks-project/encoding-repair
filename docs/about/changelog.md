# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `PregMatchDetector` - Fast encoding detector using preg_match for ASCII and UTF-8 detection (priority: 150)
- Performance improvement: ~70% faster than mb_detect_encoding for ASCII/UTF-8 detection
- `CharsetProcessorInterface` - Service contract for charset processing
- `CharsetProcessor` - Service implementation with fluent API
- `CallableAdapterTrait` - Common functionality for callable adapters
- `ChainOfResponsibilityTrait` - Generic Chain of Responsibility pattern
- Transcoder management methods: `registerTranscoder()`, `unregisterTranscoder()`, `queueTranscoders()`, `resetTranscoders()`
- Detector management methods: `registerDetector()`, `unregisterDetector()`, `queueDetectors()`, `resetDetectors()`
- Encoding management methods: `addEncodings()`, `removeEncodings()`, `getEncodings()`, `resetEncodings()`
- Fluent API support - all management methods return `$this` for method chaining
- Support for multiple independent processor instances with different configurations
- Service architecture documentation
- Example file demonstrating service usage

### Changed

- new tests structures
- Refactored code to use traits for better maintainability and DRY principles
- Extracted common functionality from `CallableDetector` and `CallableTranscoder` into `CallableAdapterTrait`
- Extracted common Chain of Responsibility logic from `DetectorChain` and `TranscoderChain` into `ChainOfResponsibilityTrait`
- Moved traits to dedicated `Traits/` directory
- Reduced code duplication by ~80 lines across 4 classes
- Improved type safety with generic trait annotations (`@template T`)
- Refactored `CharsetHelper` to act as a static facade delegating to `CharsetProcessor`
- All business logic moved from `CharsetHelper` to `CharsetProcessor` service
- `CharsetHelper` now uses lazy initialization for the processor instance
- 100% backward compatible

## [1.0.0] - 2026-01-20

### Added

- Initial release of CharsetHelper
- Chain of Responsibility pattern for extensible transcoding
- Multiple fallback strategies (UConverter → iconv → mbstring)
- Automatic encoding detection
- Double-encoding repair functionality
- Recursive conversion for arrays and objects
- Safe JSON encoding/decoding
- Custom transcoder and detector registration
- Comprehensive test suite with 90%+ coverage
- Performance benchmarks
- Full documentation

[unreleased]: https://github.com/ducks-project/encoding-repair/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/ducks-project/encoding-repair/releases/tag/v1.0.0
