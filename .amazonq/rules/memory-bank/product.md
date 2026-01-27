# Product Overview

## Project Purpose

CharsetHelper (encoding-repair) is a robust, extensible PHP library
for charset encoding conversion, detection, and repair.
It specializes in handling legacy database migrations (ISO-8859-1/Windows-1252 to UTF-8)
and fixing double-encoded strings corrupted by multiple encoding passes.

## Value Proposition

Unlike basic encoding libraries, CharsetHelper provides:

- **Extensible Chain of Responsibility architecture** - Multiple fallback strategies (UConverter → iconv → mbstring)
with custom transcoder/detector registration
- **Double-encoding repair** - Automatically detects and fixes strings like "CafÃ©" → "Café" (common in legacy systems)
- **Type-specific interpreters** - Optimized processing for strings, arrays, and objects with custom property mapping
(60% faster for selective conversion)
- **Batch processing API** - 40-60% faster for large arrays with single encoding detection
- **PSR-16 cache support** - Optional Redis/Memcached/APCu integration for detection results
- **Safe JSON operations** - Prevents encoding errors in json_encode/decode with automatic repair
- **Service-based architecture** - Dependency injection support with multiple independent processor instances
- **Immutable operations** - Objects are cloned before modification to prevent side effects

## Key Features

### Core Capabilities

1. **Multi-Strategy Transcoding**
   - UConverter (ext-intl) - Best precision, 30% faster
   - iconv - Transliteration support
   - mbstring - Universal fallback
   - Custom transcoder registration with priority system

2. **Smart Encoding Detection**
   - Cached detection with PSR-16 support (50-80% faster in batch scenarios)
   - Multiple detection methods (mbstring, fileinfo)
   - Batch detection for homogeneous arrays
   - Custom detector registration

3. **Advanced Data Processing**
   - Recursive conversion for nested arrays and objects
   - Type-specific interpreters (Strategy + Visitor pattern)
   - Custom property mappers for selective object conversion
   - Unicode NFC normalization

4. **Double-Encoding Repair**
   - Automatic detection of encoding layers
   - Configurable max depth (default: 5 layers)
   - Fixes corrupted legacy data from multiple encoding passes

5. **Performance Optimizations**
   - Batch processing API (toCharsetBatch, detectBatch)
   - Custom property mappers (avoid full reflection)
   - Cached detection results
   - Optimized UTF-8 validation (35% faster for ASCII)

### Supported Encodings

- UTF-8, UTF-16, UTF-32
- ISO-8859-1
- Windows-1252 (CP1252) - Default for legacy systems
- ASCII
- AUTO detection

## Target Users

### Primary Use Cases

1. **Database Migration Engineers**
   - Migrating legacy Latin1/CP1252 databases to UTF-8
   - Batch processing thousands of records
   - Fixing double-encoded historical data

2. **API Developers**
   - Ensuring UTF-8 compliance in JSON responses
   - Handling mixed-encoding input from external systems
   - Safe JSON encoding/decoding

3. **Web Scraping Applications**
   - Auto-detecting encoding from HTML pages
   - Converting scraped content to UTF-8
   - Handling multiple source encodings

4. **Legacy System Integration**
   - Interfacing with old systems using CP1252/ISO-8859-1
   - Repairing corrupted data from encoding mismatches
   - Maintaining backward compatibility

5. **Enterprise Applications**
   - Multi-instance processors with different configurations
   - Dependency injection for testability
   - PSR-16 cache integration for high-performance scenarios

## Technical Highlights

- **PHP 7.4+ with strict typing**
- **PSR-12/PER coding standards**
- **yoda style**
- **DRY**: Don't Repeat Yourself philosophy
- **SOLID principles** (Single Responsibility, Open/Closed, Dependency Inversion)
- **Design patterns**: Chain of Responsibility, Strategy, Visitor, Facade, Decorator
- **Minimal dependencies**: Only PSR-16 interface (optional caching)
- **100% backward compatible** - Static facade preserved for existing code
- **Comprehensive test coverage** (906%+) with PHPUnit, PHPStan level 8, Psalm
