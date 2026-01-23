# Roadmap

## Future Enhancements

### 1. Custom Encoding Configuration Support

**Status:** Proposed
**Priority:** Low
**Version Target:** v1.3 or v2.0
**Effort:** Small (~20 lines of code)

#### Overview

Add optional configuration file support for registering custom encodings, transcoders, and detectors without modifying code.

#### Motivation

- **Use Case:** Asian encodings (Shift_JIS, EUC-JP, GB2312), legacy proprietary encodings
- **Benefit:** Simplifies custom encoding registration for specific projects
- **Alignment:** Consistent with existing Chain of Responsibility architecture

#### Current State

Already possible via programmatic API:

```php
// Current approach (works well)
CharsetHelper::registerTranscoder(new MyTranscoder(), 80);

$processor = new CharsetProcessor();
$processor->addEncodings('SHIFT_JIS')->queueTranscoders(new MyTranscoder());
```

#### Proposed Enhancement

Add optional configuration file loader:

```php
// config/encodings.php (optional)
return [
    'SHIFT_JIS' => [
        'aliases' => ['SJIS', 'MS932'],
        'transcoder' => App\Encoding\ShiftJISTranscoder::class,
        'priority' => 80,
    ],
    'EUC-JP' => [
        'aliases' => ['EUCJP'],
        'transcoder' => App\Encoding\EUCJPTranscoder::class,
        'detector' => App\Encoding\EUCJPDetector::class,
        'priority' => 75,
    ],
];

// Usage
$processor = CharsetProcessor::fromConfig(__DIR__ . '/config/encodings.php');
```

#### Implementation Plan

**Phase 1: Core Method** (~20 lines)

Add to `CharsetProcessor`:

```php
public static function fromConfig(string $configPath): self
{
    $config = require $configPath;
    $processor = new self();

    foreach ($config as $encoding => $settings) {
        $processor->addEncodings($encoding, ...($settings['aliases'] ?? []));

        if (isset($settings['transcoder'])) {
            $transcoder = new $settings['transcoder']();
            $processor->registerTranscoder($transcoder, $settings['priority'] ?? null);
        }

        if (isset($settings['detector'])) {
            $detector = new $settings['detector']();
            $processor->registerDetector($detector, $settings['priority'] ?? null);
        }
    }

    return $processor;
}
```

**Phase 2: Documentation:**

- Add "Advanced: Custom Encodings" section to README
- Document configuration file format
- Provide example implementations for common encodings

**Phase 3: Testing:**

- Unit tests for `fromConfig()` method
- Integration tests with sample config files
- Edge case handling (missing files, invalid classes)

#### Design Principles

✅ **Zero Dependencies:** Use native PHP config files (no YAML/JSON parsers)
✅ **Optional:** Doesn't affect existing API or users
✅ **Type-Safe:** Uses PHP classes with autoloading
✅ **Backward Compatible:** 100% compatible with current API
✅ **Simple:** Minimal code, maximum flexibility

#### Constraints

- Must maintain "zero dependencies" principle
- Must not break existing API
- Must follow PSR-12 and strict typing standards
- Must achieve 100% test coverage

#### Alternatives Considered

1. **JSON/YAML Configuration:** ❌ Requires dependencies
2. **Annotation-Based:** ❌ Too complex for simple use case
3. **Current API Only:** ✅ Already sufficient for most users
4. **Fluent Configuration:** ✅ Already available and documented

#### Decision

**Recommendation:** Implement `fromConfig()` as optional convenience method.

**Rationale:**

- Low effort, high value for specific use cases
- Maintains project principles (zero deps, simplicity)
- Doesn't complicate existing API
- Easy to document and test

#### Acceptance Criteria

- [ ] `CharsetProcessor::fromConfig()` method implemented
- [ ] Unit tests with 100% coverage
- [ ] Documentation in README and docs/
- [ ] Example config file in examples/
- [ ] PHPStan level 8 compliance
- [ ] Backward compatibility verified

---

## Other Potential Enhancements

### 2. Streaming Support

**Status:** Idea
**Priority:** Low
**Effort:** Medium

Support for streaming large files without loading entire content into memory.

```php
$processor->toUtf8Stream($inputStream, $outputStream, 'ISO-8859-1');
```

### 3. Encoding Confidence Scores

**Status:** Idea
**Priority:** Low
**Effort:** Small

Return confidence scores for encoding detection:

```php
$result = CharsetHelper::detectWithConfidence($string);
// ['encoding' => 'UTF-8', 'confidence' => 0.95]
```

### 4. Performance Profiling

**Status:** Idea
**Priority:** Low
**Effort:** Small

Built-in profiling for transcoder performance:

```php
$processor->enableProfiling();
$stats = $processor->getProfilingStats();
// ['UConverter' => 45ms, 'iconv' => 12ms, ...]
```

---

## Version History

- **v1.0:** Initial release with core functionality
- **v1.1:** Service-based architecture, batch processing
- **v1.2:** (Current) Stability improvements
- **v1.3:** (Proposed) Custom encoding configuration
- **v2.0:** (Future) Major enhancements TBD

---

## Contributing

Have ideas for the roadmap? Please:

1. Open an issue for discussion
2. Check alignment with project principles
3. Propose implementation approach
4. Submit PR with tests and documentation

**Project Principles:**

- Zero dependencies
- Strict typing (PHP 7.4+)
- PSR-12 compliance
- 100% test coverage
- SOLID architecture
- Simplicity over features
