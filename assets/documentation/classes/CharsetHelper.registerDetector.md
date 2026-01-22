# <a name="charsethelper__registerdetector"></a>[CharsetHelper::registerDetector](#charsethelper__registerdetector)

(PHP 7 >= 7.4.0, PHP 8)

CharsetHelper::registerDetector — Register custom detection strategy

## [Description](#description)

```php
public static CharsetHelper::registerDetector(
    DetectorInterface|callable $detector,
    ?int $priority = null
): void
```

Registers a custom encoding detection provider in the Chain of Responsibility.
Allows extending CharsetHelper with custom encoding detection strategies for
specialized or proprietary encodings.

## [Parameters](#parameters)

**detector**:

DetectorInterface instance or callable with signature:

```php
function(string $string, array $options): ?string
```

The detector must return the detected encoding (uppercase string) on success,
or null to pass to the next detector in the chain.

**priority**:

Optional priority override (null = use detector's default priority).
Higher values execute first. Default priorities: MbStringDetector (100), FileInfoDetector (50).

## [Return Values](#return-values)

No value is returned.

## <a name="errors-exceptions"></a>[Errors/Exceptions](#errors-exceptions)

Throws InvalidArgumentException if detector is not a DetectorInterface instance or callable.

## [Examples](#examples)

### Example #1 Register custom detector with DetectorInterface

```php
<?php
use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\Detector\DetectorInterface;

class Utf16BomDetector implements DetectorInterface
{
    public function detect(string $string, array $options): ?string
    {
        if (strlen($string) >= 2) {
            if (ord($string[0]) === 0xFF && ord($string[1]) === 0xFE) {
                return 'UTF-16LE';
            }
            if (ord($string[0]) === 0xFE && ord($string[1]) === 0xFF) {
                return 'UTF-16BE';
            }
        }
        return null;
    }
    
    public function getPriority(): int
    {
        return 150;
    }
    
    public function isAvailable(): bool
    {
        return true;
    }
}

CharsetHelper::registerDetector(new Utf16BomDetector());
CharsetHelper::registerDetector(new Utf16BomDetector(), 200); // Override priority
```

### Example #2 Register callable detector

```php
<?php
use Ducks\Component\EncodingRepair\CharsetHelper;

CharsetHelper::registerDetector(
    function (string $string, array $options): ?string {
        if (myCustomDetection($string)) {
            return 'MY-CUSTOM-ENCODING';
        }
        return null;
    },
    150  // Priority
);

$encoding = CharsetHelper::detect($unknownString);
```

## [Notes](#notes)

Detectors are executed in priority order (highest first):

- Priority 100+: Custom high-priority detectors
- Priority 100: MbStringDetector
- Priority 50: FileInfoDetector
- Priority 0-49: Custom low-priority detectors

## [See Also](#see-also)

- [CharsetHelper::registerTranscoder] — Register custom transcoder
- [CharsetHelper::detect] — Detect encoding with registered detectors

[CharsetHelper::registerTranscoder]: ./CharsetHelper.registerTranscoder.md#charsethelper__registertranscoder
[CharsetHelper::detect]: ./CharsetHelper.detect.md#charsethelper__detect
