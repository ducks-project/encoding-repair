# Product Overview

## Project Purpose

CharsetHelper (encoding-repair) is a robust PHP library designed to handle charset encoding conversion,
detection, and repair with a focus on legacy database migrations.
It solves the common problem of corrupted character encodings when migrating
from ISO-8859-1/Windows-1252 to UTF-8, particularly addressing double-encoding
issues that plague legacy systems.

## Value Proposition

Unlike existing charset libraries, CharsetHelper provides:

- **Extensible Architecture**: Implements Chain of Responsibility pattern allowing custom transcoders and detectors
- **Multiple Fallback Strategies**: Automatically tries UConverter → iconv → mbstring for maximum compatibility
- **Smart Auto-Detection**: Multiple detection methods with configurable encoding lists
- **Double-Encoding Repair**: Automatically detects and fixes strings like "Café" → "CafÃ©" back to "Café"
- **Recursive Conversion**: Handles strings, arrays, AND objects (not just arrays like competitors)
- **Safe JSON Operations**: Prevents json_encode failures with automatic charset handling
- **Zero Dependencies**: Only requires ext-json and ext-mbstring (optional extensions for performance)
- **Modern PHP Standards**: Strict typing, PSR-12 compliant, PHP 7.4+ support

## Key Features

### Core Capabilities

1. **Robust Transcoding**
   - Chain of Responsibility pattern with prioritized providers
   - UConverter (ext-intl) for best performance (30% faster)
   - iconv for transliteration support
   - mbstring as universal fallback

2. **Encoding Detection**
   - Automatic encoding detection with AUTO constant
   - Multiple detection strategies (mbstring, fileinfo)
   - Customizable encoding candidate lists
   - Fast-path for valid UTF-8 strings

3. **Double-Encoding Repair**
   - Detects and reverses multiple encoding layers
   - Configurable max depth (default: 5 layers)
   - Handles legacy database corruption scenarios
   - Preserves data integrity during repair

4. **Recursive Processing**
   - Converts strings, arrays, and objects recursively
   - Immutable operations (objects are cloned)
   - Preserves data structure and types
   - Handles deeply nested structures

5. **Safe JSON Wrappers**
   - safeJsonEncode: Auto-repairs before encoding
   - safeJsonDecode: Converts after decoding
   - Throws clear RuntimeException on errors
   - Prevents silent failures

6. **Security Features**
   - Whitelisted encodings to prevent injection
   - Strict type checking throughout
   - Immutable design prevents side effects
   - Validates all encoding parameters

## Target Users

### Primary Audience

1. **Legacy System Maintainers**
   - Migrating old databases from Latin1 to UTF-8
   - Fixing double-encoded data from multiple migrations
   - Integrating with systems using mixed encodings

2. **Web Developers**
   - Processing CSV imports with unknown encodings
   - Sanitizing API responses for UTF-8 compliance
   - Web scraping with mixed charset sources
   - Handling user-uploaded files

3. **Data Engineers**
   - ETL pipelines with encoding conversions
   - Data cleaning and normalization
   - Cross-system data integration
   - Database migration projects

### Secondary Audience

- PHP library developers needing extensible charset handling
- DevOps teams automating legacy system migrations
- QA engineers testing internationalization features

## Use Cases

### 1. Database Migration (Latin1 → UTF-8)

Migrate entire database tables from ISO-8859-1 to UTF-8 with automatic detection and conversion.

### 2. CSV Import with Unknown Encoding

Auto-detect and convert CSV files to UTF-8 before parsing, handling various source encodings.

### 3. API Response Sanitization

Ensure all API responses are valid UTF-8, preventing json_encode failures and client-side errors.

### 4. Web Scraping

Convert scraped HTML content from various encodings to UTF-8 for consistent processing.

### 5. Legacy System Integration

Fix double-encoded data from old systems that have been migrated multiple times incorrectly.

### 6. Multi-Language Content Management

Handle content in multiple languages with different encoding requirements.

## Competitive Advantages

| Feature | CharsetHelper | ForceUTF8 | Symfony String | Portable UTF-8 |
| --------- | --------------- | ----------- | ---------------- | ---------------- |
| Multiple fallback strategies | ✅ | ❌ | ❌ | ❌ |
| Extensible (CoR pattern) | ✅ | ❌ | ❌ | ❌ |
| Object recursion | ✅ | ❌ | ❌ | ❌ |
| Double-encoding repair | ✅ | ✅ | ❌ | ⚠️ |
| Safe JSON helpers | ✅ | ❌ | ❌ | ❌ |
| Multi-encoding support | ✅ (7+) | ⚠️ (2) | ⚠️ | ⚠️ (3) |
| Modern PHP (7.4+, strict types) | ✅ | ❌ | ✅ | ⚠️ |
| Zero dependencies | ✅ | ✅ | ❌ | ❌ |

## Performance Characteristics

- **Simple UTF-8 conversion**: 45ms for 10,000 operations
- **Array conversion (100 items)**: 180ms for 10,000 operations
- **Auto-detection + conversion**: 92ms for 10,000 operations
- **Double-encoding repair**: 125ms for 10,000 operations
- **Safe JSON encode**: 67ms for 10,000 operations

Performance improves by 30% with ext-intl (UConverter) installed.

## Quality Standards

- PSR-12 / PER Coding Style
- PHPStan level 8
- 100% type coverage
- Minimum 90% code coverage
- Strict typing (declare(strict_types=1))
- Immutable design patterns
- Chain of Responsibility for extensibility
- yoda style
- OOP SOLID philosophy
- DRY (Don't Repeat Yourself) principes
