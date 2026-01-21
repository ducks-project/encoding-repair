# Product Overview

## Project Identity

**CharsetHelper** (ducks-project/encoding-repair) - A robust, immutable,
 and extensible PHP library for charset conversion, detection,
 and repair with safe JSON wrappers.

## Purpose

Designed to solve legacy ISO-8859-1 to UTF-8 interoperability issues
 and handle corrupted double-encoded data commonly found in legacy databases
 and systems.
 Provides a production-ready solution for charset conversion with multiple
 fallback strategies.

## Core Value Proposition

Unlike existing libraries (ForceUTF8, Symfony String, Portable UTF-8),
 CharsetHelper provides:

- **Extensible architecture** using Chain of Responsibility pattern
- **Multiple fallback strategies** (UConverter → iconv → mbstring)
- **Smart auto-detection** with multiple detection methods
- **Double-encoding repair** for corrupted legacy data (e.g., "CafÃ©" → "Café")
- **Recursive conversion** for arrays AND objects (not just arrays)
- **Safe JSON encoding/decoding** with automatic charset handling
- **Zero dependencies** (only optional extensions for better performance)

## Key Features

### 1. Robust Transcoding

- Chain of Responsibility pattern with prioritized providers
- Automatic fallback: UConverter (best) → iconv (good) → mbstring (universal)
- Support for 7+ encodings: UTF-8, UTF-16, UTF-32, ISO-8859-1, Windows-1252, ASCII
- Configurable options: normalization, transliteration, ignore invalid sequences

### 2. Double-Encoding Repair

- Automatically detects and fixes strings encoded multiple times
- Peels encoding layers up to configurable depth (default: 5)
- Common use case: UTF-8 data misinterpreted as ISO-8859-1 and re-encoded

### 3. Recursive Processing

- Handles strings, arrays, and objects recursively
- Immutable: objects are cloned before modification
- Preserves data structure while converting all string values

### 4. Smart Detection

- Multiple detection strategies with fallback
- Fast path for valid UTF-8 strings
- Configurable encoding list for detection

### 5. Safe JSON Operations

- Prevents json_encode from returning false on bad charsets
- Automatic repair before encoding
- Clear error messages with RuntimeException on failure

### 6. Extensibility

- Register custom transcoders without modifying core
- Register custom detectors for specialized encodings
- Priority control (prepend/append to chain)

## Target Users

### Primary Users

- **Backend developers** migrating legacy databases (Latin1 → UTF-8)
- **Integration engineers** working with legacy systems
- **Web scrapers** handling unknown encodings
- **API developers** ensuring UTF-8 compliance

### Use Cases

1. **Database Migration**: Convert legacy ISO-8859-1 tables to UTF-8
2. **CSV Import**: Auto-detect and convert files with unknown encoding
3. **API Sanitization**: Ensure all responses are valid UTF-8
4. **Web Scraping**: Handle mixed encodings from external sources
5. **Legacy Integration**: Fix double-encoded data from old systems

## Technical Requirements

- **PHP**: 7.4, 8.0, 8.1, 8.2, or 8.3
- **Required Extensions**: ext-mbstring, ext-json
- **Recommended Extensions**: ext-intl (30% faster), ext-iconv, ext-fileinfo

## Performance Characteristics

Benchmarks on 10,000 conversions (PHP 8.2, i7-12700K):

- Simple UTF-8 conversion: 45ms, 2MB
- Array (100 items): 180ms, 5MB
- Auto-detection + conversion: 92ms, 3MB
- Double-encoding repair: 125ms, 4MB
- Safe JSON encode: 67ms, 3MB

## Quality Standards

- PSR-12 / PER Coding Style
- PHPStan level 8
- 100% type coverage
- Minimum 90% code coverage
- Strict typing (declare(strict_types=1))
- Immutable design patterns
