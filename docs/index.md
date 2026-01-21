# CharsetHelper

[![Github Action Status](https://github.com/ducks-project/encoding-repair/actions/workflows/ci.yml/badge.svg)](https://github.com/ducks-project/encoding-repair)
[![Coverage Status](https://coveralls.io/repos/github/ducks-project/encoding-repair/badge.svg)](https://coveralls.io/github/ducks-project/encoding-repair)
[![License](https://img.shields.io/badge/license-MIT-green)](https://github.com/ducks-project/encoding-repair/blob/main/LICENSE)
[![Latest Stable Version](https://poser.pugx.org/ducks-project/encoding-repair/v/stable)](https://packagist.org/packages/ducks-project/encoding-repair)

Advanced charset encoding converter with **Chain of Responsibility** pattern, auto-detection, double-encoding repair, and JSON safety.

## Why CharsetHelper?

Unlike existing libraries, CharsetHelper provides:

- ✅ **Extensible architecture** with Chain of Responsibility pattern
- ✅ **Multiple fallback strategies** (UConverter → iconv → mbstring)
- ✅ **Smart auto-detection** with multiple detection methods
- ✅ **Double-encoding repair** for corrupted legacy data
- ✅ **Recursive conversion** for arrays AND objects (not just arrays!)
- ✅ **Safe JSON encoding/decoding** with automatic charset handling
- ✅ **Modern PHP** with strict typing (PHP 7.4+)
- ✅ **Zero dependencies** (only optional extensions for better performance)

## Features

- **Robust Transcoding:** Implements a Chain of Responsibility pattern trying best providers first (`Intl/UConverter` -> `Iconv` -> `MbString`)
- **Double-Encoding Repair:** Automatically detects and fixes strings like `Ã©tÃ©` back to `été`
- **Recursive Processing:** Handles `string`, `array`, and `object` recursively
- **Immutable:** Objects are cloned before modification to prevent side effects
- **Safe JSON Wrappers:** Prevents `json_encode` from returning `false` on bad charsets
- **Secure:** Whitelisted encodings to prevent injection
- **Extensible:** Register your own transcoders or detectors without modifying the core
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
