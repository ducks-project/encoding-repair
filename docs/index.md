# CharsetHelper

[![Github Action Status](https://github.com/ducks-project/encoding-repair/actions/workflows/ci.yml/badge.svg)](https://github.com/ducks-project/encoding-repair)
[![Coverage Status](https://coveralls.io/repos/github/ducks-project/encoding-repair/badge.svg)](https://coveralls.io/github/ducks-project/encoding-repair)
[![License](https://img.shields.io/badge/license-MIT-green)](https://github.com/ducks-project/encoding-repair/blob/main/LICENSE)
[![Latest Stable Version](https://poser.pugx.org/ducks-project/encoding-repair/v/stable)](https://packagist.org/packages/ducks-project/encoding-repair)

Advanced charset encoding converter with **Chain of Responsibility** pattern, auto-detection, double-encoding repair, and JSON safety.

## What's New in v1.2

### PSR-16 Cache Support

Optional external cache integration for improved performance:

```php
// Use built-in ArrayCache (PSR-16)
use Ducks\Component\EncodingRepair\Cache\ArrayCache;
use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

$cache = new ArrayCache();
$detector = new CachedDetector(new MbStringDetector(), $cache, 3600);

// Or use any PSR-16 implementation (Redis, Memcached, APCu)
// $redis = new \Symfony\Component\Cache\Psr16Cache($redisAdapter);
// $detector = new CachedDetector(new MbStringDetector(), $redis, 7200);
```

### Type Interpreter System

New optimized type-specific processing with Strategy + Visitor pattern:

```php
// Custom property mapper for selective processing (60% faster!)
use Ducks\Component\EncodingRepair\Interpreter\PropertyMapperInterface;

class UserMapper implements PropertyMapperInterface
{
    public function map(object $object, callable $transcoder, array $options): object
    {
        $copy = clone $object;
        $copy->name = $transcoder($object->name);
        $copy->email = $transcoder($object->email);
        // password NOT transcoded (security)
        return $copy;
    }
}

$processor = new CharsetProcessor();
$processor->registerPropertyMapper(User::class, new UserMapper());
```

### Batch Processing API

New optimized batch processing methods:

```php
// Batch conversion with single encoding detection (40-60% faster!)
$rows = $db->query("SELECT * FROM users")->fetchAll();
$utf8Rows = CharsetHelper::toCharsetBatch($rows, 'UTF-8', CharsetHelper::AUTO);
```

See [Type Interpreters](guide/type-interpreters.md) for details.

### Service-Based Architecture (v1.1)

CharsetHelper now uses a service-based architecture following SOLID principles:

- **`CharsetProcessor`**: Instantiable service with fluent API
- **`CharsetProcessorInterface`**: Service contract for dependency injection
- **Multiple instances**: Different configurations for different contexts
- **100% backward compatible**: Existing code works unchanged

```php
// New way: Service instance
$processor = new CharsetProcessor();
$processor->addEncodings('SHIFT_JIS')->resetDetectors();
$utf8 = $processor->toUtf8($data);

// Old way: Static facade (still works)
$utf8 = CharsetHelper::toUtf8($data);
```

See [Service Architecture](guide/service-architecture.md) for details.

## Why CharsetHelper?

Unlike existing libraries, CharsetHelper provides:

- ✅ **Extensible architecture** with Chain of Responsibility pattern
- ✅ **PSR-16 cache support** for Redis, Memcached, APCu (NEW in v1.2)
- ✅ **Type-specific interpreters** for optimized processing (NEW in v1.2)
- ✅ **Custom property mappers** for selective object conversion (NEW in v1.2)
- ✅ **Multiple fallback strategies** (UConverter → iconv → mbstring)
- ✅ **Smart auto-detection** with multiple detection methods
- ✅ **Double-encoding repair** for corrupted legacy data
- ✅ **Recursive conversion** for arrays AND objects (not just arrays!)
- ✅ **Safe JSON encoding/decoding** with automatic charset handling
- ✅ **Modern PHP** with strict typing (PHP 7.4+)
- ✅ **Minimal dependencies** (only PSR-16 interface for optional caching)

## Features

- **Robust Transcoding:** Implements a Chain of Responsibility pattern trying best providers first (`Intl/UConverter` -> `Iconv` -> `MbString`)
- **PSR-16 Cache Support:** Optional external cache (Redis, Memcached, APCu) for detection results (NEW in v1.2)
- **Type-Specific Interpreters:** Optimized processing strategies per data type (NEW in v1.2)
- **Custom Property Mappers:** Selective object property conversion for security and performance (NEW in v1.2)
- **Double-Encoding Repair:** Automatically detects and fixes strings like `Ã©tÃ©` back to `été`
- **Recursive Processing:** Handles `string`, `array`, and `object` recursively
- **Immutable:** Objects are cloned before modification to prevent side effects
- **Safe JSON Wrappers:** Prevents `json_encode` from returning `false` on bad charsets
- **Secure:** Whitelisted encodings to prevent injection
- **Extensible:** Register your own transcoders, detectors, interpreters, or cache providers without modifying the core
- **Modern Standards:** PSR-12 compliant, strictly typed, SOLID architecture

## Quick Start

```php
<?php

use Ducks\Component\EncodingRepair\CharsetHelper;

// Simple UTF-8 conversion
$utf8String = CharsetHelper::toUtf8($latinString);

// Automatic encoding detection
$data = CharsetHelper::toCharset($mixedData, 'UTF-8', CharsetHelper::AUTO);

// Repair double-encoded strings
$fixed = CharsetHelper::repair($corruptedString);

// Safe JSON with encoding handling
$json = CharsetHelper::safeJsonEncode($data);
```

## Requirements

- **PHP**: 7.4, 8.0, 8.1, 8.2, or 8.3
- **Extensions** (required): `ext-mbstring`, `ext-json`
- **Extensions** (recommended): `ext-intl`

## Installation

```bash
composer require ducks-project/encoding-repair
```

## Documentation

- [Installation Guide](getting-started/installation.md)
- [Quick Start Guide](getting-started/quick-start.md)
- [Basic Usage](guide/basic-usage.md)
- [Advanced Usage](guide/advanced-usage.md)
- [API Reference](api/CharsetHelper.md)

## License

This project is licensed under the MIT License - see the [LICENSE](about/license.md) file for details.
