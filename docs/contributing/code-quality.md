# Code Quality Standards

## Coding Style

- **Standard**: PSR-12 / PER (PHP Evolving Recommendation)
- **Strict Typing**: Always use `declare(strict_types=1)`
- **Type Declarations**: All parameters and return types must be declared

## Static Analysis

### PHPStan

- **Level**: 8 (maximum strictness)
- **Command**: `composer phpstan`

### Psalm

- **Error Level**: Strict
- **Command**: `composer psalm`

## Testing Requirements

- **Minimum Coverage**: 90%
- **Type Coverage**: 100%
- **Framework**: PHPUnit ^9.5 || ^10.0

## Code Style Rules

### Yoda Conditions

```php
// Correct
if (null !== $result) {
    return $result;
}

// Incorrect
if ($result !== null) {
    return $result;
}
```

### Spacing

```php
// Binary operators
$result = $a + $b;

// Concatenation
$message = 'Error: ' . $errorMsg;

// Cast
$string = (string) $value;
```

### PHPDoc

```php
/**
 * Convert data from one encoding to another.
 *
 * @param mixed $data Data to convert
 * @param string $to Target encoding
 * @param string $from Source encoding
 * @param array<string, mixed> $options Conversion options
 *
 * @return mixed Converted data
 *
 * @throws InvalidArgumentException If encoding is invalid
 */
public static function toCharset(
    $data,
    string $to = self::ENCODING_UTF8,
    string $from = self::ENCODING_ISO,
    array $options = []
) {
    // Implementation
}
```

## File Header

Every file must include:

```php
<?php

/**
 * Part of EncodingRepair package.
 *
 * (c) Adrien Loyant <donald_duck@team-df.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Ducks\Component\EncodingRepair;
```

## Quality Checks

```bash
# Run all checks
composer ci

# Individual checks
composer phpstan           # Static analysis
composer psalm             # Type checking
composer phpcsfixer-check  # Code style
composer phpmd             # Mess detection
```

## Next Steps

- [Development Guide](development.md)
- [API Reference](../api/CharsetHelper.md)
