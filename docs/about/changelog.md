# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
