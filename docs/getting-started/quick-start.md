# Quick Start

## Basic Conversion

```php
<?php

use Ducks\Component\EncodingRepair\CharsetHelper;

// Convert to UTF-8
$utf8String = CharsetHelper::toUtf8($latinString);

// Convert to ISO-8859-1
$isoString = CharsetHelper::toIso($utf8String);

// Convert to any encoding
$result = CharsetHelper::toCharset(
    $data,
    'UTF-8',           // Target encoding
    'ISO-8859-1'       // Source encoding
);
```

## Auto-Detection

```php
// Automatic encoding detection
$data = CharsetHelper::toCharset(
    $unknownData,
    'UTF-8',
    CharsetHelper::AUTO  // Will auto-detect source encoding
);

// Manual detection
$encoding = CharsetHelper::detect($string);
echo $encoding; // "UTF-8", "ISO-8859-1", etc.
```

## Repair Double-Encoded Strings

```php
// Fix corrupted strings (e.g., "CafÃ©" → "Café")
$fixed = CharsetHelper::repair($corruptedString);
```

## Safe JSON Operations

```php
// Safe encoding (auto-repairs before encoding)
$json = CharsetHelper::safeJsonEncode($data);

// Safe decoding with charset conversion
$data = CharsetHelper::safeJsonDecode($json);
```

## Recursive Conversion

```php
// Convert arrays
$data = [
    'name' => 'Café',
    'items' => ['entrée' => 'Crème brûlée']
];
$utf8Data = CharsetHelper::toUtf8($data);

// Convert objects
$user = new stdClass();
$user->name = 'José';
$utf8User = CharsetHelper::toUtf8($user);
```

## Next Steps

- [Basic Usage Guide](../guide/basic-usage.md)
- [Advanced Usage](../guide/advanced-usage.md)
- [API Reference](../api/CharsetHelper.md)
