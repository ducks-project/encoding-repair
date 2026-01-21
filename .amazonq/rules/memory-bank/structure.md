# Project Structure

## Directory Layout

```text
encoding-repair/
├── .amazonq/                    # Amazon Q IDE rules and memory bank
│   └── rules/
│       └── memory-bank/         # Project documentation for AI context
├── assets/                      # Documentation assets
│   └── documentation/
├── tests/                       # PHPUnit test suite
│   ├── phpunit/                 # Unit tests
│   └── benchmark/               # Performance benchmarks (PHPBench)
├── vendor/                      # Composer dependencies
├── .history/                    # Local history files (IDE)
├── CharsetHelper.php            # Main library class (single-file library)
├── composer.json                # Dependency and project configuration
├── README.md                    # Comprehensive documentation
├── CHANGELOG.md                 # Version history
├── LICENSE                      # MIT License
├── phpunit.xml.dist             # PHPUnit configuration
├── phpstan.neon.dist            # PHPStan static analysis config
├── psalm.xml.dist               # Psalm static analysis config
├── .php-cs-fixer.dist.php       # PHP CS Fixer configuration
├── phpcs.xml.dist               # PHP CodeSniffer configuration
├── phpmd.xml.dist               # PHP Mess Detector configuration
├── phpbench.json.dist           # PHPBench configuration
├── rector.php                   # Rector refactoring rules
├── .editorconfig                # Editor configuration
├── .gitignore                   # Git ignore rules
├── .scrutinizer.yml             # Scrutinizer CI configuration
├── .styleci.yml                 # StyleCI configuration
├── .readthedocs.yml             # ReadTheDocs configuration
└── appveyor.yml                 # AppVeyor CI configuration
```

## Core Components

### Single-File Library Architecture

The project uses a **single-file library** pattern with all functionality in `CharsetHelper.php`:

- **Namespace**: `Ducks\Component\Component\EncodingRepair`
- **Class**: `CharsetHelper` (final, static utility class)
- **Pattern**: Static utility class with private constructor (non-instantiable)

### CharsetHelper Class Structure

#### Public API (Static Methods)

1. **Conversion Methods**
   - `toCharset()` - Universal conversion method
   - `toUtf8()` - Convenience method for UTF-8 conversion
   - `toIso()` - Convenience method for ISO-8859-1/Windows-1252 conversion

2. **Detection Methods**
   - `detect()` - Auto-detect charset encoding

3. **Repair Methods**
   - `repair()` - Fix double-encoded strings

4. **JSON Methods**
   - `safeJsonEncode()` - JSON encode with charset repair
   - `safeJsonDecode()` - JSON decode with charset conversion

5. **Extensibility Methods**
   - `registerTranscoder()` - Add custom conversion strategies
   - `registerDetector()` - Add custom detection strategies

#### Internal Architecture

**Chain of Responsibility Pattern**:

- Static arrays hold provider chains: `$transcoders` and `$detectors`
- Providers are tried in priority order until one succeeds
- Returns null to pass to next provider in chain

**Transcoder Chain** (Priority Order):

1. `transcodeWithUConverter()` - Best precision (requires ext-intl)
2. `transcodeWithIconv()` - Good performance (requires ext-iconv)
3. `transcodeWithMbString()` - Universal fallback (always available)

**Detector Chain** (Priority Order):

1. `detectWithMbString()` - Fast and reliable
2. `detectWithFileInfo()` - Fallback for difficult cases

**Core Processing Methods**:

- `applyRecursive()` - Recursively process arrays/objects/scalars
- `applyToObject()` - Clone and process object properties
- `convertValue()` - Convert single value with encoding detection
- `convertString()` - Low-level string conversion
- `transcodeString()` - Transcode with fallback strategies
- `repairValue()` - Repair double-encoded value
- `peelEncodingLayers()` - Remove multiple encoding layers

**Utility Methods**:

- `resolveEncoding()` - Resolve AUTO to actual encoding
- `invokeProvider()` - Call provider (method or callable)
- `validateProvider()` - Validate provider before registration
- `validateEncoding()` - Validate encoding against whitelist
- `configureOptions()` - Merge options with defaults
- `normalize()` - Apply Unicode NFC normalization
- `isValidUtf8()` - Check UTF-8 validity
- `buildIconvSuffix()` - Build iconv flags string

## Architectural Patterns

### 1. Chain of Responsibility

- Multiple providers try to handle conversion/detection
- First successful provider returns result
- Enables extensibility without modifying core

### 2. Static Utility Class

- All methods are static
- Private constructor prevents instantiation
- No mutable state (psalm-immutable)
- Thread-safe by design

### 3. Immutable Operations

- Objects are cloned before modification
- Original data never mutated
- Functional programming style with callbacks

### 4. Recursive Processing

- Unified handling of strings, arrays, and objects
- Callback-based transformation
- Preserves data structure

### 5. Fail-Safe Fallbacks

- Multiple strategies for each operation
- Graceful degradation (UConverter → iconv → mbstring)
- Returns original data if all conversions fail

## Configuration Files

### Quality Assurance

- **phpstan.neon.dist**: Level 8 static analysis
- **psalm.xml.dist**: Type checking and immutability verification
- **.php-cs-fixer.dist.php**: PSR-12/PER code style enforcement
- **phpcs.xml.dist**: Additional code style checks
- **phpmd.xml.dist**: Mess detection rules

### Testing

- **phpunit.xml.dist**: Unit test configuration with coverage
- **phpbench.json.dist**: Performance benchmark configuration

### CI/CD

- **.scrutinizer.yml**: Code quality monitoring
- **.styleci.yml**: Automated style fixes
- **appveyor.yml**: Windows CI pipeline
- **.readthedocs.yml**: Documentation hosting

### Refactoring

- **rector.php**: Automated refactoring rules for PHP upgrades

## Namespace Structure

```text
Ducks\Component\Component\EncodingRepair\
└── CharsetHelper (final class)
```

## Autoloading

- **PSR-4**: `Ducks\Component\EncodingRepair\` → ``
- **Dev PSR-4**: `Ducks\Component\EncodingRepair\Tests\` → `tests/`

## Dependencies

### Production (Required)

- PHP >= 7.4
- ext-mbstring (required)
- ext-json (required)

### Production (Optional)

- ext-intl (30% performance boost)
- ext-iconv (transliteration support)
- ext-fileinfo (advanced detection)

### Development

- phpunit/phpunit: ^9.5 || ^10.0
- phpbench/phpbench: ^1.2
- phpstan/phpstan: ^1.10
- phpstan/phpstan-phpunit: ^1.4
- vimeo/psalm: ^4.30 || ^5.0
- friendsofphp/php-cs-fixer: ^3.0
