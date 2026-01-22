# Project Structure

## Directory Organization

```text
encoding-repair/
├── CharsetHelper.php              # Main facade class (static API)
├── Transcoder/                    # Encoding conversion strategies
│   ├── TranscoderInterface.php    # Transcoder contract
│   ├── TranscoderChain.php        # Chain of Responsibility coordinator
│   ├── UConverterTranscoder.php   # ext-intl implementation (priority: 100)
│   ├── IconvTranscoder.php        # iconv implementation (priority: 50)
│   ├── MbStringTranscoder.php     # mbstring implementation (priority: 10)
│   └── CallableTranscoder.php     # Wrapper for custom callables
├── Detector/                      # Encoding detection strategies
│   ├── DetectorInterface.php      # Detector contract
│   ├── DetectorChain.php          # Chain of Responsibility coordinator
│   ├── MbStringDetector.php       # mb_detect_encoding (priority: 100)
│   ├── FileInfoDetector.php       # finfo detection (priority: 50)
│   └── CallableDetector.php       # Wrapper for custom callables
├── tests/
│   ├── phpunit/                   # Unit tests (PHPUnit)
│   │   ├── CharsetHelperTest.php
│   │   ├── CallableTranscoderTest.php
│   │   ├── CallableDetectorTest.php
│   │   └── *Test.php              # Individual component tests
│   └── benchmark/                 # Performance benchmarks (PHPBench)
│       ├── ConversionBench.php
│       ├── DetectionBench.php
│       ├── JsonBench.php
│       └── RepairBench.php
├── docs/                          # MkDocs documentation source
│   ├── index.md
│   ├── getting-started/
│   ├── guide/
│   ├── api/
│   ├── contributing/
│   └── about/
├── site/                          # Generated MkDocs HTML output
├── assets/
│   └── documentation/             # Generated API documentation
│       ├── classes/
│       └── HowTo.md
├── .github/
│   └── workflows/                 # CI/CD pipelines
│       ├── ci.yml                 # Main CI (tests, static analysis)
│       ├── release.yml            # Release automation
│       └── security.yml           # Security scanning
├── composer.json                  # Dependencies and scripts
├── phpunit.xml.dist              # PHPUnit configuration
├── phpstan.neon.dist             # PHPStan level 8 configuration
├── psalm.xml.dist                # Psalm static analysis
├── .php-cs-fixer.dist.php        # PHP-CS-Fixer (PSR-12/PER)
├── phpcs.xml.dist                # PHP_CodeSniffer rules
├── phpmd.xml.dist                # PHP Mess Detector rules
├── mkdocs.yml                    # Documentation site config
└── README.md                     # Main documentation
```

## Core Components

### 1. CharsetHelper (Facade)

**Location**: `/CharsetHelper.php`

**Purpose**: Static utility class providing simple API for all encoding operations.

**Key Responsibilities**:

- Exposes public API methods (toUtf8, toIso, toCharset, repair, detect)
- Manages singleton instances of TranscoderChain and DetectorChain
- Provides registration methods for custom transcoders/detectors
- Handles recursive processing of arrays and objects
- Implements safe JSON encoding/decoding wrappers

**Design Pattern**: Facade + Singleton (for chains)

### 2. Transcoder System

**Location**: `/Transcoder/`

**Architecture**: Chain of Responsibility pattern

**Components**:

- **TranscoderInterface**: Contract defining transcode method, priority, and availability check
- **TranscoderChain**: Manages registered transcoders, sorts by priority, executes chain
- **UConverterTranscoder**: Uses ext-intl UConverter (fastest, priority 100)
- **IconvTranscoder**: Uses iconv with transliteration support (priority 50)
- **MbStringTranscoder**: Uses mb_convert_encoding (fallback, priority 10)
- **CallableTranscoder**: Wraps user-provided callable functions

**Flow**:

```text
Request → TranscoderChain
    ↓
UConverterTranscoder (if ext-intl available)
    ↓ (returns null if fails)
IconvTranscoder (if iconv available)
    ↓ (returns null if fails)
MbStringTranscoder (always available)
    ↓
Returns converted string or null
```

### 3. Detector System

**Location**: `/Detector/`

**Architecture**: Chain of Responsibility pattern

**Components**:

- **DetectorInterface**: Contract defining detect method, priority, and availability check
- **DetectorChain**: Manages registered detectors, sorts by priority, executes chain
- **MbStringDetector**: Uses mb_detect_encoding (priority 100)
- **FileInfoDetector**: Uses finfo class (priority 50)
- **CallableDetector**: Wraps user-provided callable functions

**Flow**:

```text
Request → DetectorChain
    ↓
MbStringDetector (if ext-mbstring available)
    ↓ (returns null if uncertain)
FileInfoDetector (if ext-fileinfo available)
    ↓
Returns detected encoding or null
```

### 4. Testing Infrastructure

**Unit Tests** (`tests/phpunit/`):

- 100% code coverage target
- Tests for each component and edge cases
- Separate edge case test files for complex scenarios
- Process isolation for PHPUnit runs

**Benchmarks** (`tests/benchmark/`):

- PHPBench performance tests
- Measures conversion, detection, JSON, and repair operations
- Retry threshold for consistent results

## Architectural Patterns

### 1. Chain of Responsibility

**Used in**: Transcoder and Detector systems

**Benefits**:

- Extensible: Add new strategies without modifying existing code
- Prioritized: Higher priority strategies execute first
- Fallback: Automatic fallback to next strategy on failure
- Decoupled: Each strategy is independent

**Implementation**:

```php
// Each handler checks if it can handle the request
public function transcode(string $data, string $to, string $from, array $options): ?string
{
    if (!$this->isAvailable()) {
        return null; // Pass to next handler
    }

    // Try to handle
    $result = $this->doTranscode($data, $to, $from, $options);

    return $result; // Return result or null to continue chain
}
```

### 2. Facade Pattern

**Used in**: CharsetHelper class

**Benefits**:

- Simple API: Single entry point for all operations
- Hides complexity: Users don't need to know about chains
- Consistent interface: All methods follow similar patterns

### 3. Strategy Pattern

**Used in**: Individual Transcoder/Detector implementations

**Benefits**:

- Interchangeable: Strategies can be swapped at runtime
- Testable: Each strategy can be tested independently
- Maintainable: Changes to one strategy don't affect others

### 4. Immutable Design

**Used in**: Object processing

**Benefits**:

- Thread-safe: No shared mutable state
- Predictable: Functions don't have side effects
- Safe: Original data is never modified

**Implementation**:

```php
private static function applyToObject(object $data, callable $callback): object
{
    $copy = clone $data; // Always clone before modification
    // ... process $copy
    return $copy;
}
```

## Component Relationships

```text
CharsetHelper (Facade)
    ├── Uses → TranscoderChain
    │           ├── Manages → UConverterTranscoder
    │           ├── Manages → IconvTranscoder
    │           ├── Manages → MbStringTranscoder
    │           └── Manages → CallableTranscoder (user-provided)
    │
    └── Uses → DetectorChain
                ├── Manages → MbStringDetector
                ├── Manages → FileInfoDetector
                └── Manages → CallableDetector (user-provided)
```

## Data Flow

### Conversion Flow

```text
User calls CharsetHelper::toUtf8($data)
    ↓
applyRecursive() - handles arrays/objects/strings
    ↓
convertValue() - validates and prepares
    ↓
convertString() - performs conversion
    ↓
transcodeString() - delegates to chain
    ↓
TranscoderChain::transcode() - tries each transcoder
    ↓
Returns converted data
```

### Repair Flow

```text
User calls CharsetHelper::repair($data)
    ↓
applyRecursive() - handles arrays/objects/strings
    ↓
repairValue() - repair logic
    ↓
peelEncodingLayers() - removes double-encoding
    ↓
toCharset() - final conversion
    ↓
Returns repaired data
```

## Extension Points

### 1. Custom Transcoders

```php
CharsetHelper::registerTranscoder(new MyTranscoder(), 150);
// or
CharsetHelper::registerTranscoder(function($data, $to, $from, $options) {
    // Custom logic
    return $converted;
}, 150);
```

### 2. Custom Detectors

```php
CharsetHelper::registerDetector(new MyDetector(), 200);
// or
CharsetHelper::registerDetector(function($string, $options) {
    // Custom detection
    return $encoding;
}, 200);
```

## Configuration Files

- **composer.json**: Dependencies, autoloading, scripts
- **phpunit.xml.dist**: Test configuration, coverage settings
- **phpstan.neon.dist**: Static analysis level 8, strict rules
- **psalm.xml.dist**: Type coverage, error levels
- **.php-cs-fixer.dist.php**: PSR-12/PER coding standards
- **phpcs.xml.dist**: CodeSniffer rules
- **mkdocs.yml**: Documentation site structure
- **.readthedocs.yml**: ReadTheDocs build configuration

## Namespace Structure

```text
Ducks\Component\EncodingRepair\
    ├── CharsetHelper (main class)
    ├── Transcoder\
    │   ├── TranscoderInterface
    │   ├── TranscoderChain
    │   ├── UConverterTranscoder
    │   ├── IconvTranscoder
    │   ├── MbStringTranscoder
    │   └── CallableTranscoder
    └── Detector\
        ├── DetectorInterface
        ├── DetectorChain
        ├── MbStringDetector
        ├── FileInfoDetector
        └── CallableDetector
```

PSR-4 autoloading maps `Ducks\Component\EncodingRepair\` to the root directory.
